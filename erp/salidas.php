<?php
require_once 'includes/header.php';

$mensajeExito = '';
$mensajeError = '';

$dirComprobantes = __DIR__ . '/uploads/facturas_ventas/';

$usuarioId = $_SESSION['usuario_id'] ?? 1; 

// =========================================================================
// REGISTRAR NUEVA SALIDA / VENTA
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_salida'])) {
    $productoId     = intval($_POST['producto_id'] ?? 0);
    $cliente        = trim($_POST['cliente'] ?? 'Público General');
    if (empty($cliente)) { $cliente = 'Público General'; }
    
    $cantidad       = floatval($_POST['cantidad'] ?? 0);
    $precioVenta    = floatval($_POST['precio_venta'] ?? 0);
    $tipoPago       = $_POST['tipo_pago'] ?? 'contado';
    $metodoPago     = $_POST['metodo_pago'] ?? 'efectivo';
    $conFactura     = isset($_POST['con_factura']) ? 1 : 0;
    
    $montoTotal     = $cantidad * $precioVenta;
    
    if ($conFactura) {
        $subtotal = $montoTotal / 1.16;
        $iva      = $montoTotal - $subtotal;
    } else {
        $subtotal = $montoTotal;
        $iva      = 0.00;
    }

    $facturaUrl = null;

    if ($productoId > 0 && $cantidad > 0 && $precioVenta >= 0) {
        try {
            $pdo->beginTransaction();

            // 1. Validar stock del producto
            $stmtStock = $pdo->prepare("SELECT nombre, stock_actual FROM productos WHERE id = ? FOR UPDATE");
            $stmtStock->execute([$productoId]);
            $prodData = $stmtStock->fetch(PDO::FETCH_ASSOC);

            if (!$prodData) {
                throw new Exception("El producto seleccionado no existe en el inventario.");
            }

            if ($prodData['stock_actual'] < $cantidad) {
                throw new Exception("Stock insuficiente para '{$prodData['nombre']}'. Disponible: " . number_format($prodData['stock_actual'], 2));
            }

            // 2. Subida del comprobante/factura si existe
            if (!empty($_FILES['factura_file']['name'])) {
                if (!is_dir($dirComprobantes)) {
                    mkdir($dirComprobantes, 0755, true);
                }
                $ext = strtolower(pathinfo($_FILES['factura_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp'])) {
                    $nomComp = 'venta_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['factura_file']['tmp_name'], $dirComprobantes . $nomComp)) {
                        $facturaUrl = 'uploads/facturas_ventas/' . $nomComp;
                    }
                }
            }

            // 3. Insertar encabezado en 'salidas'
            $stmtSalida = $pdo->prepare("INSERT INTO salidas (usuario_id, cliente, total, subtotal, iva, monto_total, tipo_pago, estado_cobro, metodo_pago, metodo_cobro, con_factura, requiere_factura, factura_url, fecha, fecha_salida) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            
            $estadoCobro = ($tipoPago === 'contado') ? 'cobrado' : 'credito';
            $stmtSalida->execute([
                $usuarioId, $cliente, $montoTotal, $subtotal, $iva, $montoTotal, 
                $tipoPago, $estadoCobro, $metodoPago, $metodoPago, 
                $conFactura, $conFactura, $facturaUrl
            ]);
            $salidaId = $pdo->lastInsertId();

            // 4. Insertar detalle en 'detalle_salidas'
            $stmtDetalle = $pdo->prepare("INSERT INTO detalle_salidas (salida_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmtDetalle->execute([$salidaId, $productoId, $cantidad, $precioVenta, $montoTotal]);

            // 5. Actualizar stock en 'productos'
            $stmtUpd = $pdo->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?");
            $stmtUpd->execute([$cantidad, $productoId]);

            // 6. Contabilidad (Ingresos / Cuentas por Cobrar)
            if ($tipoPago === 'contado') {
                $concepto = "Venta contado ID #{$salidaId}: {$cantidad}x {$prodData['nombre']} - Cliente: {$cliente}";
                
                $stmtIng = $pdo->prepare("INSERT INTO ingresos 
                    (salida_id, producto_id, usuario_id, cantidad, costo_unitario, concepto, monto_subtotal, monto_iva, monto_total, metodo_pago, comprobante_url, fecha, fecha_ingreso) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                
                $stmtIng->execute([
                    $salidaId, $productoId, $usuarioId, $cantidad, $precioVenta, 
                    $concepto, $subtotal, $iva, $montoTotal, $metodoPago, $facturaUrl
                ]);
            } else {
                // Inserción adaptada a la estructura de 'cuentas_cobrar'
                $stmtCxC = $pdo->prepare("INSERT INTO cuentas_cobrar 
                    (salida_id, cliente, monto, estatus, fecha_emision) 
                    VALUES (?, ?, ?, 'pendiente', NOW())");
                
                $stmtCxC->execute([$salidaId, $cliente, $montoTotal]);
            }

            $pdo->commit();
            $mensajeExito = "Venta y salida de almacén #{$salidaId} registrada exitosamente.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensajeError = "Error al procesar la salida: " . $e->getMessage();
        }
    } else {
        $mensajeError = "Por favor complete los campos obligatorios.";
    }
}

// Cargar catálogo de productos
try {
    $productos = $pdo->query("SELECT id, codigo, nombre, precio_venta, stock_actual FROM productos ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $productos = [];
}

// Cargar historial reciente
try {
    $sqlHistorial = "SELECT s.*, ds.cantidad, ds.precio_unitario, p.nombre AS producto_nombre, p.codigo AS producto_codigo 
                     FROM salidas s
                     LEFT JOIN detalle_salidas ds ON s.id = ds.salida_id
                     LEFT JOIN productos p ON ds.producto_id = p.id
                     ORDER BY s.id DESC LIMIT 20";
    $historial = $pdo->query($sqlHistorial)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $historial = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cart-check text-primary me-2"></i> Registro de Ventas / Salidas</h2>
</div>

<?php if ($mensajeExito): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($mensajeExito) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($mensajeError): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($mensajeError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white fw-bold">
        <i class="bi bi-plus-circle me-1"></i> Nueva Salida de Inventario
    </div>
    <div class="card-body">
        <form method="POST" action="salidas.php" enctype="multipart/form-data">
            <input type="hidden" name="registrar_salida" value="1">
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Producto (*):</label>
                    <select name="producto_id" id="productoSelect" class="form-select" required onchange="actualizarPrecioProducto()">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio_venta'] ?>">
                                <?= htmlspecialchars($p['codigo'] . ' - ' . $p['nombre']) ?> (Stock: <?= number_format($p['stock_actual'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Cliente:</label>
                    <input type="text" name="cliente" class="form-control" value="Público General">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">Cantidad (*):</label>
                    <input type="number" step="0.01" min="0.01" name="cantidad" class="form-control" placeholder="0.00" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">Precio Unitario ($):</label>
                    <input type="number" step="0.01" min="0" name="precio_venta" id="inputPrecio" class="form-control" placeholder="0.00" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Condición de Pago:</label>
                    <select name="tipo_pago" class="form-select">
                        <option value="contado">Contado</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Método de Pago:</label>
                    <select name="metodo_pago" class="form-select">
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-center mt-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="con_factura" id="checkFactura" value="1">
                        <label class="form-check-label fw-bold" for="checkFactura">¿Requiere Factura? (16% IVA)</label>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Adjuntar Factura/Ticket:</label>
                    <input type="file" name="factura_file" class="form-control" accept=".pdf,.xml,.jpg,.png">
                </div>

                <div class="col-12 text-end mt-3">
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-cart-plus me-1"></i> Guardar Salida
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white fw-bold">
        <i class="bi bi-clock-history me-1"></i> Ventas Recientes
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Cliente</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Factura</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historial)): ?>
                        <tr><td colspan="8" class="text-center py-3 text-muted">Sin registros recientes.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historial as $h): ?>
                            <tr>
                                <td class="fw-bold">#<?= $h['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($h['fecha_salida'] ?? $h['fecha'])) ?></td>
                                <td><?= htmlspecialchars(($h['producto_codigo'] ?? '') . ' - ' . ($h['producto_nombre'] ?? 'N/A')) ?></td>
                                <td><?= htmlspecialchars($h['cliente']) ?></td>
                                <td class="text-center"><?= number_format($h['cantidad'] ?? 0, 2) ?></td>
                                <td class="text-end fw-bold text-success">$<?= number_format($h['monto_total'] ?? $h['total'], 2) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= ($h['tipo_pago'] === 'contado') ? 'success' : 'warning' ?>">
                                        <?= ucfirst($h['tipo_pago']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($h['factura_url'])): ?>
                                        <a href="<?= htmlspecialchars($h['factura_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                                    <?php else: ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function actualizarPrecioProducto() {
    const select = document.getElementById('productoSelect');
    const selectedOption = select.options[select.selectedIndex];
    const precio = selectedOption.getAttribute('data-precio');
    if (precio) {
        document.getElementById('inputPrecio').value = parseFloat(precio).toFixed(2);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
