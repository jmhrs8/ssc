<?php
// controllers/VentaController.php

namespace Controllers;

use Config\Database;
use Models\Caja;
use PDO;
use Exception;

class VentaController 
{
    private PDO $db;

    public function __construct() 
    {
        $this->db = Database::getInstance();
    }

    /**
     * Búsqueda AJAX de productos por Código de Barras o Nombre (Retail)
     */
    public function buscarProducto(): void 
    {
        header('Content-Type: application/json');
        $query = trim($_GET['q'] ?? '');

        if (empty($query)) {
            echo json_encode([]);
            exit;
        }

        $sql = "SELECT id, codigo_barras, nombre, precio_venta, tipo_producto, stock_actual 
                FROM productos 
                WHERE activo = 1 
                  AND (codigo_barras = :exacto OR nombre LIKE :like_query)
                LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':exacto'     => $query,
            ':like_query' => '%' . $query . '%'
        ]);

        echo json_encode($stmt->fetchAll());
        exit;
    }

    /**
     * Cargar la comanda activa de una mesa (Cafetería)
     */
    public function obtenerComandaMesa(): void 
    {
        header('Content-Type: application/json');
        $mesaId = filter_input(INPUT_GET, 'mesa_id', FILTER_VALIDATE_INT);

        if (!$mesaId) {
            echo json_encode(['success' => false, 'message' => 'Mesa no válida.']);
            exit;
        }

        $sql = "SELECT c.id AS comanda_id, c.mesa_id, m.numero_mesa, dc.id AS detalle_id, 
                       p.id AS producto_id, p.nombre, dc.cantidad, p.precio_venta, dc.notas_preparacion
                FROM comandas c
                INNER JOIN mesas m ON c.mesa_id = m.id
                INNER JOIN detalle_comandas dc ON c.id = dc.comanda_id
                INNER JOIN productos p ON dc.producto_id = p.id
                WHERE c.mesa_id = :mesa_id AND c.estado IN ('abierta', 'servida')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':mesa_id' => $mesaId]);
        $items = $stmt->fetchAll();

        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    }

    /**
     * Procesar cobro completo (Retail o Cafetería) con transacción atómica SQL
     */
    public function procesarVenta(): void 
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Petición no permitida.']);
            exit;
        }

        $input = json_decode(file_get_contents('php_input') ?: '{}', true);

        $sucursalId  = $_SESSION['sucursal_id'] ?? 1;
        $usuarioId   = $_SESSION['usuario_id']  ?? 1;
        $cajaId      = $input['caja_id'] ?? null;
        $comandaId   = $input['comanda_id'] ?? null; // NULL si es Retail
        $montoPago   = (float) ($input['monto_pago'] ?? 0);
        $metodoPago  = $input['metodo_pago'] ?? 'efectivo';
        $carrito     = $input['carrito'] ?? []; // Array de {producto_id, cantidad, precio}

        if (!$cajaId || empty($carrito) || $montoPago <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos de la venta incompletos.']);
            exit;
        }

        try {
            $this->db->beginTransaction();

            // 1. Calcular Totales
            $subtotal = 0.0;
            foreach ($carrito as $item) {
                $subtotal += $item['precio'] * $item['cantidad'];
            }
            $impuesto = $subtotal * 0.16; // IVA 16%
            $total    = $subtotal + $impuesto;

            if ($montoPago < $total) {
                throw new Exception("El monto pagado es insuficiente para cubrir el total ($" . number_format($total, 2) . ").");
            }

            $cambio = $montoPago - $total;
            $folio  = 'V-' . strtoupper(dechex(time())) . '-' . rand(100, 999);

            // 2. Insertar Encabezado de Venta
            $sqlVenta = "INSERT INTO ventas (sucursal_id, caja_id, usuario_id, comanda_id, folio, subtotal, impuesto, total, estado) 
                         VALUES (:sucursal_id, :caja_id, :usuario_id, :comanda_id, :folio, :subtotal, :impuesto, :total, 'completada')";
            
            $stmtVenta = $this->db->prepare($sqlVenta);
            $stmtVenta->execute([
                ':sucursal_id' => $sucursalId,
                ':caja_id'     => $cajaId,
                ':usuario_id'  => $usuarioId,
                ':comanda_id'  => $comandaId,
                ':folio'       => $folio,
                ':subtotal'    => $subtotal,
                ':impuesto'    => $impuesto,
                ':total'       => $total
            ]);
            $ventaId = $this->db->lastInsertId();

            // 3. Procesar Detalle de Venta y Descuento de Inventario
            $sqlDetalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, costo_unitario, subtotal) 
                           VALUES (:venta_id, :producto_id, :cantidad, :precio, :costo, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);

            $sqlCheckStock = "SELECT tipo_producto, precio_compra, stock_actual, permitir_venta_sin_stock FROM productos WHERE id = :id FOR UPDATE";
            $stmtCheckStock = $this->db->prepare($sqlCheckStock);

            $sqlStockRetail = "UPDATE productos SET stock_actual = stock_actual - :cantidad WHERE id = :id";
            $stmtStockRetail = $this->db->prepare($sqlStockRetail);

            // Descuento para recetas/insumos de Cafetería
            $sqlStockInsumo = "UPDATE insumos i
                               INNER JOIN recetas r ON i.id = r.insumo_id
                               SET i.stock_actual = i.stock_actual - (r.cantidad * :cantidad_prod)
                               WHERE r.producto_id = :producto_id";
            $stmtStockInsumo = $this->db->prepare($sqlStockInsumo);

            foreach ($carrito as $item) {
                $stmtCheckStock->execute([':id' => $item['producto_id']]);
                $prodData = $stmtCheckStock->fetch();

                if (!$prodData) {
                    throw new Exception("El producto ID {$item['producto_id']} no existe.");
                }

                // Control estricto de Stock para Retail
                if ($prodData['tipo_producto'] === 'directo' && !$prodData['permitir_venta_sin_stock']) {
                    if ($prodData['stock_actual'] < $item['cantidad']) {
                        throw new Exception("Stock insuficiente para el producto ID {$item['producto_id']}.");
                    }
                }

                // Registramos el detalle guardando el costo histórico
                $itemSubtotal = $item['precio'] * $item['cantidad'];
                $stmtDetalle->execute([
                    ':venta_id'    => $ventaId,
                    ':producto_id' => $item['producto_id'],
                    ':cantidad'    => $item['cantidad'],
                    ':precio'      => $item['precio'],
                    ':costo'       => $prodData['precio_compra'],
                    ':subtotal'    => $itemSubtotal
                ]);

                // Descuento de Inventario según la naturaleza del módulo
                if ($prodData['tipo_producto'] === 'directo') {
                    $stmtStockRetail->execute([':cantidad' => $item['cantidad'], ':id' => $item['producto_id']]);
                } else {
                    $stmtStockInsumo->execute([':cantidad_prod' => $item['cantidad'], ':producto_id' => $item['producto_id']]);
                }
            }

            // 4. Registrar Pago
            $sqlPago = "INSERT INTO pagos_venta (venta_id, metodo_pago, monto, monto_recibido, cambio) 
                        VALUES (:venta_id, :metodo, :monto, :recibido, :cambio)";
            $stmtPago = $this->db->prepare($sqlPago);
            $stmtPago->execute([
                ':venta_id' => $ventaId,
                ':metodo'   => $metodoPago,
                ':monto'    => $total,
                ':recibido' => $montoPago,
                ':cambio'   => $cambio
            ]);

            // 5. Cierre de Comanda si es módulo Cafetería
            if ($comandaId) {
                $sqlCloseComanda = "UPDATE comandas SET estado = 'cerrada' WHERE id = :comanda_id";
                $stmtCloseComanda = $this->db->prepare($sqlCloseComanda);
                $stmtCloseComanda->execute([':comanda_id' => $comandaId]);

                $sqlFreeMesa = "UPDATE mesas m INNER JOIN comandas c ON m.id = c.mesa_id 
                                SET m.estado = 'disponible' WHERE c.id = :comanda_id";
                $stmtFreeMesa = $this->db->prepare($sqlFreeMesa);
                $stmtFreeMesa->execute([':comanda_id' => $comandaId]);
            }

            $this->db->commit();

            // Construir Ticket ESC/POS
            $rawEscPos = $this->generarComandosEscPosTicket([
                'folio'       => $folio,
                'fecha'       => date('Y-m-d H:i:s'),
                'subtotal'    => $subtotal,
                'impuesto'    => $impuesto,
                'total'       => $total,
                'monto_pago'  => $montoPago,
                'cambio'      => $cambio,
                'metodo_pago' => $metodoPago,
                'items'       => $carrito
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Venta completada con éxito.',
                'folio'   => $folio,
                'escpos_base64' => base64_encode($rawEscPos), // Listo para enviar al spooler de impresión
                'ticket_data' => [
                    'folio'      => $folio,
                    'fecha'      => date('Y-m-d H:i:s'),
                    'subtotal'   => $subtotal,
                    'impuesto'   => $impuesto,
                    'total'      => $total,
                    'monto_pago' => $montoPago,
                    'cambio'     => $cambio,
                    'items'      => $carrito
                ]
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Genera la secuencia binaria del protocolo ESC/POS EPSON (80mm/58mm)
     */
    private function generarComandosEscPosTicket(array $data): string 
    {
        $ESC = "\x1b";
        $GS  = "\x1d";

        $ticket  = $ESC . "@";                  // Inicializar impresora
        $ticket .= $ESC . "a\x01";              // Centrar alineación
        $ticket .= $ESC . "!\x30";              // Texto Doble Alto / Doble Ancho
        $ticket .= "MI TIENDA / CAFETERIA\n";
        $ticket .= $ESC . "!\x00";              // Modos normales
        $ticket .= "RFC: ABC123456XYZ\n";
        $ticket .= "Sucursal Matriz Hibrida\n";
        $ticket .= "--------------------------------\n";
        $ticket .= $ESC . "a\x00";              // Alineación izquierda
        $ticket .= "Folio: " . $data['folio'] . "\n";
        $ticket .= "Fecha: " . $data['fecha'] . "\n";
        $ticket .= "--------------------------------\n";

        foreach ($data['items'] as $item) {
            $nombre = str_pad(substr($item['nombre'], 0, 18), 18, " ");
            $cant   = str_pad((string)$item['cantidad'], 3, " ", STR_PAD_LEFT);
            $precio = str_pad(number_format($item['precio'] * $item['cantidad'], 2), 8, " ", STR_PAD_LEFT);
            $ticket .= "{$cant} {$nombre} {$precio}\n";
        }

        $ticket .= "--------------------------------\n";
        $ticket .= $ESC . "a\x02";              // Alineación Derecha
        $ticket .= "SUBTOTAL: $" . number_format($data['subtotal'], 2) . "\n";
        $ticket .= "IVA (16%): $" . number_format($data['impuesto'], 2) . "\n";
        $ticket .= $ESC . "!\x10";              // Texto Ancho Negrita
        $ticket .= "TOTAL: $" . number_format($data['total'], 2) . "\n";
        $ticket .= $ESC . "!\x00";
        $ticket .= "PAGO (" . strtoupper($data['metodo_pago']) . "): $" . number_format($data['monto_pago'], 2) . "\n";
        $ticket .= "CAMBIO: $" . number_format($data['cambio'], 2) . "\n";
        
        $ticket .= $ESC . "a\x01";              // Centrar
        $ticket .= "\n¡Gracias por su compra!\n\n\n";
        $ticket .= $GS . "V\x41\x03";          // Comando de Corte de Papel (Full Cut)

        return $ticket;
    }
}
