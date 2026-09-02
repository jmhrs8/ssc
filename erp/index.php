<?php 
require_once 'includes/header.php'; 

// Inicializar variables
$totalProductos = 0;
$alertasStock = 0;
$ingresosNetos = 0;
$cuentasPend = 0;

// 1. Total Productos
try {
    $totalProductos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn() ?? 0;
} catch (\PDOException $e) {}

// 2. Alertas de Stock Bajo
try {
    $alertasStock = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock_actual <= stock_minimo")->fetchColumn() ?? 0;
} catch (\PDOException $e) {}

// 3. Ingresos Netos Acumulados (calculado con cantidad * costo_unitario)
try {
    $ingresosNetos = $pdo->query("SELECT SUM(cantidad * costo_unitario) FROM ingresos")->fetchColumn() ?? 0;
} catch (\PDOException $e) {}

// 4. Cuentas por Pagar Pendientes
try {
    $cuentasPend = $pdo->query("SELECT SUM(monto_total - monto_pagado) FROM cuentas_por_pagar WHERE estado != 'liquidado'")->fetchColumn() ?? 0;
} catch (\PDOException $e) {}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Panel de Control General</h1>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Total Productos/Insumos</h6>
                <h2 class="m-0"><?= $totalProductos ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-dark shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Alertas de Stock Bajo</h6>
                <h2 class="m-0"><?= $alertasStock ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Ingresos Netos Acumulados</h6>
                <h2 class="m-0">$<?= number_format($ingresosNetos, 2) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-danger text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Por Pagar a Proveedores</h6>
                <h2 class="m-0">$<?= number_format($cuentasPend, 2) ?></h2>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
