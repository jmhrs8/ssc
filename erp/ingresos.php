<?php
require_once 'includes/header.php';

$mensajeError = '';

// Cargar Historial de Ingresos de forma segura
try {
    $sqlIngresos = "SELECT * FROM ingresos ORDER BY fecha_ingreso DESC LIMIT 50";
    $ingresos = $pdo->query($sqlIngresos)->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $ingresos = [];
    $mensajeError = "Error al consultar la tabla ingresos: " . $e->getMessage();
}

// Totales por Método de Pago
try {
    $totalesMetodo = $pdo->query("SELECT metodo_pago, SUM(monto_total) AS total FROM ingresos GROUP BY metodo_pago")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (\PDOException $e) {
    $totalesMetodo = [];
}

$totalEfectivo = $totalesMetodo['efectivo'] ?? 0.00;
$totalTransfer = $totalesMetodo['transferencia'] ?? 0.00;
$totalTarjeta  = $totalesMetodo['tarjeta'] ?? 0.00;
$totalIngresos = array_sum($totalesMetodo);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cash-coin text-success me-2"></i> Reporte de Ingresos y Cobros</h2>
</div>

<?php if ($mensajeError): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($mensajeError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- TARJETAS DE RESUMEN DE INGRESOS -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Total Ingresos</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalIngresos, 2) ?></h3>
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
                <h6 class="card-title text-uppercase mb-1">Transferencias (SPEI)</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalTransfer, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-dark shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Tarjetas</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalTarjeta, 2) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- TABLA DE DETALLE DE INGRESOS -->
<div class="card shadow-sm">
    <div class="card-header bg-dark text-white fw-bold">
        <i class="bi bi-journal-text me-1"></i> Entradas de Dinero Registradas
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th class="text-center">Vía de Cobro</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">IVA (16%)</th>
                        <th class="text-end">Total Cobrado</th>
                        <th class="text-center">Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ingresos)): ?>
                        <tr><td colspan="7" class="text-center py-3 text-muted">No se han registrado ingresos aún.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ingresos as $ing): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($ing['fecha_ingreso'] ?? $ing['fecha_registro'] ?? 'now')) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($ing['concepto'] ?? 'Ingreso por Venta') ?></td>
                                <td class="text-center"><span class="badge bg-secondary"><?= ucfirst($ing['metodo_pago'] ?? 'Efectivo') ?></span></td>
                                <td class="text-end">$<?= number_format($ing['monto_subtotal'] ?? 0, 2) ?></td>
                                <td class="text-end">$<?= number_format($ing['monto_iva'] ?? 0, 2) ?></td>
                                <td class="text-end fw-bold text-success">$<?= number_format($ing['monto_total'] ?? $ing['monto'] ?? 0, 2) ?></td>
                                <td class="text-center">
                                    <?php if (!empty($ing['comprobante_url']) && file_exists(__DIR__ . '/' . $ing['comprobante_url'])): ?>
                                        <a href="<?= htmlspecialchars($ing['comprobante_url']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Ver Comprobante
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin adjunto</span>
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
