<?php
// controllers/CocinaController.php

namespace Controllers;

use Config\Database;
use PDO;
use Exception;

class CocinaController 
{
    private PDO $db;

    public function __construct() 
    {
        $this->db = Database::getInstance();
    }

    /**
     * Devuelve las comandas activas con sus productos desglosados (AJAX Poll / SSE)
     */
    public function obtenerComandasActivas(): void 
    {
        header('Content-Type: application/json');

        try {
            // Obtener comandas no cerradas ni canceladas
            $sqlComandas = "SELECT 
                                c.id AS comanda_id, 
                                c.estado AS estado_comanda, 
                                m.numero_mesa, 
                                u.nombre AS mesero,
                                DATE_FORMAT(c.creado_en, '%H:%i:%s') AS hora_ingreso,
                                TIMESTAMPDIFF(MINUTE, c.creado_en, NOW()) AS minutos_transcurridos
                            FROM comandas c
                            INNER JOIN mesas m ON c.mesa_id = m.id
                            INNER JOIN usuarios u ON c.usuario_id = u.id
                            WHERE c.estado IN ('abierta', 'en_preparacion')
                            ORDER BY c.creado_en ASC";

            $stmt = $this->db->query($sqlComandas);
            $comandas = $stmt->fetchAll();

            $resultado = [];

            foreach ($comandas as $comanda) {
                // Obtener el detalle de cada comanda
                $sqlDetalle = "SELECT 
                                  dc.id AS detalle_id, 
                                  p.nombre AS producto, 
                                  dc.cantidad, 
                                  dc.notas_preparacion, 
                                  dc.estado_preparacion
                               FROM detalle_comandas dc
                               INNER JOIN productos p ON dc.producto_id = p.id
                               WHERE dc.comanda_id = :comanda_id
                               ORDER BY dc.id ASC";

                $stmtDetalle = $this->db->prepare($sqlDetalle);
                $stmtDetalle->execute([':comanda_id' => $comanda['comanda_id']]);
                $comanda['items'] = $stmtDetalle->fetchAll();

                $resultado[] = $comanda;
            }

            echo json_encode(['success' => true, 'comandas' => $resultado]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Actualiza el estado de preparación de un ítem individual o de toda la comanda
     */
    public function cambiarEstadoItem(): void 
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Petición no permitida.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input') ?: '{}', true);

        $detalleId   = filter_var($input['detalle_id'] ?? null, FILTER_VALIDATE_INT);
        $nuevoEstado = $input['nuevo_estado'] ?? ''; // 'pendiente', 'en_cocina', 'listo', 'entregado'

        $estadosValidos = ['pendiente', 'en_cocina', 'listo', 'entregado'];

        if (!$detalleId || !in_array($nuevoEstado, $estadosValidos, true)) {
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
            exit;
        }

        try {
            $this->db->beginTransaction();

            // 1. Actualizar el estado del producto en el detalle
            $sql = "UPDATE detalle_comandas SET estado_preparacion = :nuevo_estado WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nuevo_estado' => $nuevoEstado,
                ':id'           => $detalleId
            ]);

            // 2. Obtener el ID de la comanda padre para sincronizar su estado general
            $sqlComandaId = "SELECT comanda_id FROM detalle_comandas WHERE id = :id";
            $stmtCId = $this->db->prepare($sqlComandaId);
            $stmtCId->execute([':id' => $detalleId]);
            $comandaId = $stmtCId->fetchColumn();

            if ($comandaId) {
                // Verificar si todos los ítems están en estado "listo" o "entregado"
                $sqlCheck = "SELECT COUNT(*) FROM detalle_comandas WHERE comanda_id = :comanda_id AND estado_preparacion IN ('pendiente', 'en_cocina')";
                $stmtCheck = $this->db->prepare($sqlCheck);
                $stmtCheck->execute([':comanda_id' => $comandaId]);
                $pendientes = (int) $stmtCheck->fetchColumn();

                if ($pendientes === 0) {
                    $sqlUpdateComanda = "UPDATE comandas SET estado = 'servida' WHERE id = :comanda_id";
                } else {
                    $sqlUpdateComanda = "UPDATE comandas SET estado = 'en_preparacion' WHERE id = :comanda_id";
                }

                $stmtUpdateC = $this->db->prepare($sqlUpdateComanda);
                $stmtUpdateC->execute([':comanda_id' => $comandaId]);
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
