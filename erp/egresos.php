<?php
require_once 'includes/header.php';

$mensajeExito = '';
$mensajeError = '';

$dirComprobantesEgresos = __DIR__ . '/uploads/comprobantes_egresos/';

// 1. REGISTRAR EGRESO MANUAL (Gastos Operativos / Renta / Servicios / Pagos Directos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_egreso'])) {
    $concepto        = trim($_POST['concepto'] ?? '');
    $monto           = floatval($_POST['monto'] ?? 0);
    $proveedorId     = !empty($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;
    $metodoPago      = $_POST['metodo_pago'] ?? 'efectivo';
    $tipoComprobante = $_POST['tipo_comprobante'] ?? 'ninguno';
    $comprobanteUrl  = null;

    if (!empty($concepto) && $monto > 0) {
        // Subir comprobante si se adjunta
        if (!empty($_FILES['comprobante']['name'])) {
            $file = $_FILES['comprobante'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $extsPermitidas = ['pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $extsPermitidas)) {
                if (!is_dir($dirComprobantesEgresos)) {
                    mkdir($dirComprobantesEgresos, 0755, true);
                }

                $nombreArchivo = 'egreso_' . time() . '_' . uniqid() . '.' . $ext;
                $destino = $dirComprobantesEgresos . $nombreArchivo;

                if (move_uploaded_file($file['tmp_name'], $destino)) {
                    $comprobanteUrl = 'uploads/comprobantes_egresos/' . $nombreArchivo;
                } else {
                    $mensajeError = "No se pudo subir el comprobante del egreso.";
                }
            } else {
                $mensajeError = "Formato no permitido. Sube PDF, XML, JPG, PNG o WEBP.";
            }
        }

        if (empty($mensajeError)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO egresos 
                    (proveedor_id, concepto, monto, metodo_pago, tipo_comprobante, comprobante_url, fecha_pago) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$proveedorId, $concepto, $monto, $metodoPago, $tipoComprobante, $comprobanteUrl]);

                $mensajeExito = "Egreso registrado correctamente en caja/bancos.";
            } catch (\PDOException $e) {
                $mensajeError = "Error al registrar egreso: " . $e->getMessage();
            }
        }
    } else {
        $mensajeError = "Por favor ingresa un concepto válido y un monto mayor a cero.";
    }
}

// Cargar Proveedores para el select opcional
$proveedores = $pdo->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Cargar Historial de Egresos
$sqlEgresos = "SELECT e.*, pr.nombre AS proveedor_nombre 
               FROM egresos e 
               LEFT JOIN proveedores pr ON e.proveedor_id = pr.id 
               ORDER BY e.fecha_pago DESC LIMIT 50";
$egresos = $pdo->query($sqlEgresos)->fetchAll(PDO::FETCH_ASSOC);

// Calcular acumulados por vía de pago
$totalesMetodo = $pdo->query("SELECT metodo_pago, SUM(monto) AS total FROM egresos GROUP BY metodo_pago")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalEfectivo = $totalesMetodo['efectivo'] ?? 0.00;
$totalTransfer = $totalesMetodo['transferencia'] ?? 0.00;
$totalTarjeta  = $totalesMetodo['tarjeta'] ?? 0.00;
$totalEgresos  = array_sum($totalesMetodo);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-wallet2 text-danger me-2"></i> Control de Egresos y Salidas de Dinero</h2>
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

<!-- RESUMEN FINANCIERO DE EGRESOS -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-danger text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Total Egresos</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalEgresos, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Efectivo</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalEfectivo, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Transferencias</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalTransfer, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-dark shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Tarjeta / Otros</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalTarjeta, 2) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- FORMULARIO PARA REGISTRAR GASTO/EGRESO MANUAL -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white fw-bold">
        <i class="bi bi-plus-circle me-1"></i> Registrar Nuevo Egreso / Gasto
    </div>
    <div class="card-body">
        <form method="POST" action="egresos.php" enctype="multipart/form-data">
            <input type="hidden" name="registrar_egreso" value="1">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Concepto (*):</label>
                    <input type="text" name="concepto" class="form-control" required placeholder="Ej. Pago de Renta, Luz, Compra directa...">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Proveedor / Beneficiario:</label>
                    <select name="proveedor_id" class="form-select">
                        <option value="">-- Opcional --</option>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">Monto ($) (*):</label>
                    <input type="number" step="0.01" min="0.01" name="monto" class="form-control" required placeholder="0.00">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Vía de Pago (*):</label>
                    <select name="metodo_pago" class="form-select" required>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="tarjeta">Tarjeta Débito/Crédito</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Tipo Comprobante:</label>
                    <select name="tipo_comprobante" class="form-select">
                        <option value="ninguno">Sin Comprobante / Nota Interna</option>
                        <option value="factura">Factura (CFDI)</option>
                        <option value="ticket">Ticket / Voucher</option>
                        <option value="transferencia">Comprobante SPEI</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-bold">Adjuntar Archivo Comprobante:</label>
                    <input type="file" name="comprobante" class="form-control" accept=".pdf,.xml,.jpg,.jpeg,.png,.webp">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-dash-circle me-1"></i> Guardar Egreso
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- HISTORIAL DE EGRESOS -->
<div class="card shadow-sm">
    <div class="card-header bg-secondary text-white fw-bold">
        <i class="bi bi-journal-text me-1"></i> Historial de Salidas de Dinero
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Proveedor</th>
                        <th class="text-center">Vía de Pago</th>
                        <th class="text-end">Monto Total</th>
                        <th class="text-center">Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($egresos)): ?>
                        <tr><td colspan="6" class="text-center py-3 text-muted">No hay salidas de dinero registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($egresos as $eg): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($eg['fecha_pago'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($eg['concepto']) ?></td>
                                <td><?= htmlspecialchars($eg['proveedor_nombre'] ?? 'N/A') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= ucfirst($eg['metodo_pago']) ?></span>
                                </td>
                                <td class="text-end fw-bold text-danger">-$<?= number_format($eg['monto'], 2) ?></td>
                                <td class="text-center">
                                    <?php if (!empty($eg['comprobante_url']) && file_exists(__DIR__ . '/' . $eg['comprobante_url'])): ?>
                                        <a href="<?= htmlspecialchars($eg['comprobante_url']) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-file-earmark-pdf"></i> Ver Archivo
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
