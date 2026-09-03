<?php
require_once 'includes/header.php';

$mensajeExito = '';
$mensajeError = '';

$dirFacturas = __DIR__ . '/uploads/facturas_compras/';

// REGISTRAR ENTRADA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_entrada'])) {
    $productoId      = intval($_POST['producto_id'] ?? 0);
    $proveedorId     = !empty($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;
    $cantidad        = floatval($_POST['cantidad'] ?? 0);
    $costoUnitario   = floatval($_POST['costo_unitario'] ?? 0);
    $estatusPago     = $_POST['estatus_pago'] ?? 'pagado';
    
    // Mapeo seguro para evitar "Data truncated" si la columna es un ENUM corto o VARCHAR reducido
    $rawTipoComp     = $_POST['tipo_comprobante'] ?? 'sin_comprobante';
    $mapaComprobante = [
        'sin_comprobante' => 'ninguno',
        'factura'         => 'factura',
        'remision'        => 'remision'
    ];
    $tipoComprobante = $mapaComprobante[$rawTipoComp] ?? 'ninguno';

    $montoTotal      = $cantidad * $costoUnitario;
    $fechaPago       = ($estatusPago === 'pagado') ? date('Y-m-d H:i:s') : null;
    $comprobanteUrl  = null;

    if ($productoId > 0 && $cantidad > 0 && $costoUnitario >= 0) {

        // Validar subida de comprobante
        if (!empty($_FILES['comprobante']['name']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['comprobante'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            $extsPermitidas = ['pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp'];
            $mimesPermitidos = [
                'application/pdf',
                'text/xml',
                'application/xml',
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (in_array($ext, $extsPermitidas) && in_array($mimeType, $mimesPermitidos)) {
                if (!is_dir($dirFacturas)) {
                    mkdir($dirFacturas, 0755, true);
                }

                $nombreArchivo = 'compra_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destino = $dirFacturas . $nombreArchivo;

                if (move_uploaded_file($file['tmp_name'], $destino)) {
                    $comprobanteUrl = 'uploads/facturas_compras/' . $nombreArchivo;
                } else {
                    $mensajeError = "No se pudo subir la factura o comprobante.";
                }
            } else {
                $mensajeError = "Formato no permitido. Sube PDF, XML, JPG, PNG o WEBP.";
            }
        }

        if (empty($mensajeError)) {
            try {
                $pdo->beginTransaction();

                // a) Insertar Entrada
                $stmt = $pdo->prepare("INSERT INTO entradas_inventario
                    (producto_id, proveedor_id, cantidad, costo_unitario, monto_total, estatus_pago, tipo_comprobante, comprobante_url, fecha_pago)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$productoId, $proveedorId, $cantidad, $costoUnitario, $montoTotal, $estatusPago, $tipoComprobante, $comprobanteUrl, $fechaPago]);
                $entradaId = $pdo->lastInsertId();

                // b) Sumar a stock_actual y actualizar costo
                $stmtStock = $pdo->prepare("UPDATE productos SET stock_actual = stock_actual + ?, costo_unitario = ? WHERE id = ?");
                $stmtStock->execute([$cantidad, $costoUnitario, $productoId]);

                // c) Obtener nombre de producto con bloqueo de fila para consistencia
                $stmtP = $pdo->prepare("SELECT nombre FROM productos WHERE id = ? FOR UPDATE");
                $stmtP->execute([$productoId]);
                $prodNom = $stmtP->fetchColumn() ?: 'Producto #' . $productoId;

                if ($estatusPago === 'pagado') {
                    // d) Registrar Egreso si fue Pagado
                    $concepto = "Compra de {$cantidad} unid. de {$prodNom}";
                    $stmtEgr = $pdo->prepare("INSERT INTO egresos
                        (entrada_id, proveedor_id, concepto, monto, metodo_pago, tipo_comprobante, comprobante_url, fecha_pago)
                        VALUES (?, ?, ?, ?, 'efectivo', ?, ?, NOW())");
                    $stmtEgr->execute([$entradaId, $proveedorId, $concepto, $montoTotal, $tipoComprobante, $comprobanteUrl]);
                } else {
                    // e) Registrar en Cuentas por Pagar
                    $concepto = "Compra a crédito de {$cantidad} unid. de {$prodNom}";
                    $stmtCxP = $pdo->prepare("INSERT INTO cuentas_pagar
                        (entrada_id, proveedor_id, concepto, monto, estatus, comprobante_url)
                        VALUES (?, ?, ?, ?, 'pendiente', ?)");
                    $stmtCxP->execute([$entradaId, $proveedorId, $concepto, $montoTotal, $comprobanteUrl]);
                }

                $pdo->commit();
                $mensajeExito = "Entrada registrada correctamente." . ($estatusPago === 'pendiente' ? " Se envió a Cuentas por Pagar." : " Marcada como Pagada e ingresada a Egresos.");
            } catch (\PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $mensajeError = "Error en la base de datos: " . $e->getMessage();
            }
        }
    } else {
        $mensajeError = "Selecciona un producto y especifica cantidad y costo válidos.";
    }
}

// Cargar catálogo de productos
try {
    $productos = $pdo->query("SELECT id, nombre, stock_actual, tipo_unidad FROM productos ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $productos = [];
}

try {
    $proveedores = $pdo->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $proveedores = [];
}

// Cargar historial
try {
    $sqlEntradas = "SELECT e.*, p.nombre AS producto_nombre, pr.nombre AS proveedor_nombre
                    FROM entradas_inventario e
                    JOIN productos p ON e.producto_id = p.id
                    LEFT JOIN proveedores pr ON e.proveedor_id = pr.id
                    ORDER BY e.fecha_registro DESC LIMIT 50";
    $entradas = $pdo->query($sqlEntradas)->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $entradas = [];
    $mensajeError = "Error al consultar historial de entradas: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-arrow-in-down text-success me-2"></i> Entradas / Compras de Producto</h2>
</div>

<?php if ($mensajeExito): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($mensajeExito) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($mensajeError): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($mensajeError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- FORMULARIO DE REGISTRO DE ENTRADA -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white fw-bold">
        <i class="bi bi-plus-circle me-1"></i> Registrar Nueva Entrada
    </div>
    <div class="card-body">
        <form method="POST" action="entradas.php" enctype="multipart/form-data">
            <input type="hidden" name="registrar_entrada" value="1">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Producto (*):</label>
                    <select name="producto_id" class="form-select" required>
                        <option value="">-- Seleccionar Producto --</option>
                        <?php foreach ($productos as $prod): ?>
                            <option value="<?= $prod['id'] ?>">
                                <?= htmlspecialchars($prod['nombre']) ?> (Stock actual: <?= number_format($prod['stock_actual'], 2) ?> <?= htmlspecialchars($prod['tipo_unidad']) ?>s)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Proveedor:</label>
                    <select name="proveedor_id" class="form-select">
                        <option value="">-- Sin Proveedor / Mostrador --</option>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">Cantidad (*):</label>
                    <input type="number" step="0.01" min="0.01" name="cantidad" class="form-control" required placeholder="0.00">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">Costo Unitario ($) (*):</label>
                    <input type="number" step="0.01" min="0" name="costo_unitario" class="form-control" required placeholder="0.00">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Estatus del Pago (*):</label>
                    <select name="estatus_pago" class="form-select" required>
                        <option value="pagado">Pagado (Descuenta hoy / Egresos)</option>
                        <option value="pendiente">Pendiente (A Cuentas x Pagar)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Tipo Comprobante:</label>
                    <select name="tipo_comprobante" class="form-select">
                        <option value="sin_comprobante">Sin Comprobante / Nota</option>
                        <option value="factura">Factura Fiscal (CFDI)</option>
                        <option value="remision">Nota de Remisión</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Factura / Ticket (PDF/XML/Imagen):</label>
                    <input type="file" name="comprobante" class="form-control" accept=".pdf,.xml,.jpg,.jpeg,.png,.webp">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-save me-1"></i> Guardar Entrada
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- HISTORIAL DE ENTRADAS -->
<div class="card shadow-sm">
    <div class="card-header bg-secondary text-white fw-bold">
        <i class="bi bi-journal-text me-1"></i> Historial de Entradas Recientes
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Proveedor</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-end">Costo U.</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Estatus Pago</th>
                        <th class="text-center">Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entradas)): ?>
                        <tr><td colspan="8" class="text-center py-3 text-muted">No hay registros de entradas aún.</td></tr>
                    <?php else: ?>
                        <?php foreach ($entradas as $ent): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($ent['fecha_registro'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($ent['producto_nombre']) ?></td>
                                <td><?= htmlspecialchars($ent['proveedor_nombre'] ?? 'N/A') ?></td>
                                <td class="text-center"><span class="badge bg-info text-dark">+<?= number_format($ent['cantidad'], 2) ?></span></td>
                                <td class="text-end">$<?= number_format($ent['costo_unitario'], 2) ?></td>
                                <td class="text-end fw-bold">$<?= number_format($ent['monto_total'], 2) ?></td>
                                <td class="text-center">
                                    <?php if (($ent['estatus_pago'] ?? '') === 'pagado'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Pagado</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> Cuentas x Pagar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($ent['comprobante_url']) && file_exists(__DIR__ . '/' . $ent['comprobante_url'])): ?>
                                        <a href="<?= htmlspecialchars($ent['comprobante_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-earmark-arrow-down me-1"></i> Ver Factura
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin archivo</span>
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

<?php require_once 'includes/footer.php'; ?>
