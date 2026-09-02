<?php
require_once 'includes/header.php';

// Variables por defecto
$totalFacturado = 0;
$totalSinFactura = 0;
$totalIVA = 0;
$ingresosNetosTotales = 0;
$inventario = [];

// Consultas seguras a ingresos
try {
    $ingresosNetosTotales = $pdo->query("SELECT SUM(cantidad * costo_unitario) FROM ingresos")->fetchColumn() ?? 0;
} catch (\PDOException $e) {}

try {
    $totalFacturado = $pdo->query("SELECT SUM(cantidad * costo_unitario) FROM ingresos WHERE con_factura = 1")->fetchColumn() ?? 0;
    $totalSinFactura = $pdo->query("SELECT SUM(cantidad * costo_unitario) FROM ingresos WHERE con_factura = 0")->fetchColumn() ?? 0;
    $totalIVA = $pdo->query("SELECT SUM(iva) FROM ingresos")->fetchColumn() ?? 0;
} catch (\PDOException $e) {
    // Si no existen las columnas de facturación/IVA, el total sin factura toma el ingreso neto
    $totalSinFactura = $ingresosNetosTotales;
}

// Consulta de productos para reporte de almacén
try {
    $inventario = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC")->fetchAll();
} catch (\PDOException $e) {}
?>

<style>
@media print {
    .sidebar, .btn-print, header, nav { display: none !important; }
    main { width: 100% !important; margin: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Reportes Generales y Balances Empresariales</h2>
    <button onclick="window.print();" class="btn btn-secondary btn-print"><i class="bi bi-printer"></i> Imprimir Reporte / PDF</button>
</div>

<!-- Header Impreso con Logo -->
<?php if (isset($empresa)): ?>
<div class="d-none d-print-block text-center mb-4 border-bottom pb-3">
    <?php if (!empty($empresa['logo_url'])): ?>
        <img src="<?= htmlspecialchars($empresa['logo_url']) ?>" style="max-height: 80px;">
    <?php endif; ?>
    <h3><?= htmlspecialchars($empresa['nombre_empresa'] ?? 'Sistema ERP') ?></h3>
    <p>Reporte Generado el <?= date('d/m/Y H:i:s') ?></p>
</div>
<?php endif; ?>

<!-- Balance de Ingresos desglosado -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary text-center p-3">
            <h6>Ingresos Con Factura</h6>
            <h4 class="text-primary">$<?= number_format($totalFacturado, 2) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info text-center p-3">
            <h6>Ingresos Sin Factura</h6>
            <h4 class="text-info">$<?= number_format($totalSinFactura, 2) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning text-center p-3">
            <h6>Total IVA Recaudado</h6>
            <h4 class="text-warning">$<?= number_format($totalIVA, 2) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success text-center p-3 bg-light">
            <h6>Ingreso Neto Real</h6>
            <h4 class="text-success">$<?= number_format($ingresosNetosTotales, 2) ?></h4>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="m-0">Existencias Actuales en Bodega</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th>Código</th>
                        <th>Producto / Insumo</th>
                        <th>Tipo de Unidad</th>
                        <th>Costo Unitario</th>
                        <th>Stock Actual</th>
                        <th>Valor Total en Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $valorTotalBodega = 0;
                    if (empty($inventario)):
                    ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay datos en el inventario.</td></tr>
                    <?php
                    else:
                        foreach ($inventario as $p):
                            $valorFila = $p['stock_actual'] * $p['costo_unitario'];
                            $valorTotalBodega += $valorFila;
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['codigo']) ?></strong></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= ucfirst($p['tipo_unidad']) ?></td>
                        <td>$<?= number_format($p['costo_unitario'], 2) ?></td>
                        <td><?= $p['stock_actual'] ?></td>
                        <td>$<?= number_format($valorFila, 2) ?></td>
                    </tr>
                    <?php 
                        endforeach; 
                    endif;
                    ?>
                    <tr class="table-secondary fw-bold">
                        <td colspan="5" class="text-end">Valor Total del Inventario:</td>
                        <td>$<?= number_format($valorTotalBodega, 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
