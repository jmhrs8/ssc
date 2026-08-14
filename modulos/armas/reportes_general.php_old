<?php
require_once "../../config/conexion.php";

try {
    // 1. Estadísticas por Tipo de Bien (General)
    $tipoBien = $pdo->query("SELECT tipo_bien, COUNT(*) as total FROM inventario_armas WHERE tipo_bien != '' GROUP BY tipo_bien ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

    // 2. Estadísticas de Semovientes (Caninos y Equinos)
    $statsSemovientes = $pdo->query("SELECT tipo_bien, COUNT(*) as total FROM inventario_armas WHERE tipo_bien LIKE '%CANINO%' OR tipo_bien LIKE '%EQUINO%' GROUP BY tipo_bien")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Estadísticas por Aseguradora
    $statsAseguradora = $pdo->query("SELECT aseguradora, COUNT(*) as total FROM inventario_armas WHERE aseguradora != '' GROUP BY aseguradora ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Estadísticas por Despacho
    $statsDespacho = $pdo->query("SELECT despacho, COUNT(*) as total FROM inventario_armas WHERE despacho != '' GROUP BY despacho ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // 5. Estatus de Seguro (En barras)
    $statusSeguro = $pdo->query("SELECT status_seguro, COUNT(*) as total FROM inventario_armas WHERE status_seguro != '' GROUP BY status_seguro ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

    // 6. Totales Globales (Cálculos específicos)
    $totales = $pdo->query("SELECT 
        SUM(CAST(armas AS UNSIGNED)) as total_armas,
        SUM(CAST(cartuchos AS UNSIGNED)) as total_cartuchos,
        SUM(importe_convenio) as gran_total_dinero,
        (SELECT COUNT(*) FROM inventario_armas WHERE tipo_bien LIKE '%CANINO%') as total_caninos,
        (SELECT COUNT(*) FROM inventario_armas WHERE tipo_bien LIKE '%EQUINO%') as total_equinos
        FROM inventario_armas")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes Globales | SSC SYSTEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        body { background-color: #f8f9fa; font-size: 12px; text-transform: uppercase; font-family: 'Segoe UI', sans-serif; }
        .header-report { background: #1a1a1a; color: white; padding: 20px; border-bottom: 4px solid #0dcaf0; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .card-header { background: #fff !important; border-bottom: 1px solid #f0f0f0 !important; font-weight: bold; color: #333; padding: 15px; }
        .stat-card { border-top: 4px solid #0dcaf0; text-align: center; }
        .stat-value { font-size: 24px; font-weight: 800; display: block; }
        .bg-money { background: linear-gradient(45deg, #198754, #2ecc71); color: white; }
        .bg-purple { background: linear-gradient(45deg, #6610f2, #a55eea); color: white; }
        .chart-container { position: relative; height: 400px; width: 100%; padding: 10px; }
    </style>
</head>
<body>

<div class="header-report mb-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-info"></i> PANEL INTEGRAL DE CONTROL</h4>
            <span class="text-info opacity-75">Armamento, Caninos y Equinos</span>
        </div>
        <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-undo me-1"></i> REGRESAR</a>
    </div>
</div>

<div class="container-fluid px-4">
    <div class="row g-3 mb-4 text-center">
        <div class="col-md-2">
            <div class="card stat-card p-3">
                <small class="text-muted">ARMAS</small>
                <span class="stat-value text-dark"><?= number_format($totales['total_armas'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3 bg-purple">
                <small class="text-white-50">CANINOS</small>
                <span class="stat-value text-white"><?= number_format($totales['total_caninos'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3 bg-purple">
                <small class="text-white-50">EQUINOS</small>
                <span class="stat-value text-white"><?= number_format($totales['total_equinos'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3" style="border-top-color: #fd7e14;">
                <small class="text-muted">CARTUCHOS</small>
                <span class="stat-value text-warning"><?= number_format($totales['total_cartuchos'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-money p-3 shadow text-center">
                <small class="fw-bold">MONTO TOTAL CONVENIO</small>
                <div class="stat-value">$ <?= number_format($totales['gran_total_dinero'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-primary"><i class="fas fa-paw me-2"></i> DETALLE DE SEMOVIENTES</div>
                <div class="chart-container">
                    <canvas id="chartSemovientes"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-success"><i class="fas fa-tasks me-2"></i> ESTATUS DE SEGURO</div>
                <div class="chart-container">
                    <canvas id="chartSeguro"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><i class="fas fa-boxes me-2"></i> DISTRIBUCIÓN POR TIPO DE BIEN</div>
                <div class="chart-container" style="height: 500px;">
                    <canvas id="chartBienes"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-danger"><i class="fas fa-building me-2"></i> TOP 10 ASEGURADORAS</div>
                <div class="chart-container">
                    <canvas id="chartAseguradoras"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-info"><i class="fas fa-briefcase me-2"></i> TOP 10 DESPACHOS</div>
                <div class="chart-container">
                    <canvas id="chartDespachos"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    Chart.register(ChartDataLabels);

    // CONFIGURACIÓN MAESTRA PARA EVITAR ENCIMAMIENTOS
    const masterOptions = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 40, right: 25, left: 25, bottom: 20 } },
        plugins: {
            legend: { display: false },
            datalabels: {
                anchor: 'end',
                align: 'top',
                offset: 8,
                font: { weight: 'bold', size: 12 },
                formatter: (value) => value.toLocaleString()
            }
        },
        scales: {
            y: { 
                beginAtZero: true, 
                grace: '20%', // Da espacio extra arriba de la barra más alta
                grid: { color: '#f0f0f0' }
            },
            x: { grid: { display: false } }
        }
    };

    // 1. SEMOVIENTES
    new Chart(document.getElementById('chartSemovientes'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsSemovientes as $s) echo "'".$s['tipo_bien']."',"; ?>],
            datasets: [{
                data: [<?php foreach($statsSemovientes as $s) echo $s['total'].","; ?>],
                backgroundColor: '#6610f2',
                borderRadius: 8,
                barThickness: 50
            }]
        },
        options: masterOptions
    });

    // 2. ESTATUS (BARRAS)
    new Chart(document.getElementById('chartSeguro'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statusSeguro as $s) echo "'".($s['status_seguro']?:'S/D')."',"; ?>],
            datasets: [{
                data: [<?php foreach($statusSeguro as $s) echo $s['total'].","; ?>],
                backgroundColor: '#1cc88a',
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: masterOptions
    });

    // 3. BIENES
    new Chart(document.getElementById('chartBienes'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($tipoBien as $b) echo "'".$b['tipo_bien']."',"; ?>],
            datasets: [{
                data: [<?php foreach($tipoBien as $b) echo $b['total'].","; ?>],
                backgroundColor: '#4e73df',
                borderRadius: 5
            }]
        },
        options: masterOptions
    });

    // 4. ASEGURADORAS (HORIZONTAL)
    new Chart(document.getElementById('chartAseguradoras'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsAseguradora as $a) echo "'".$a['aseguradora']."',"; ?>],
            datasets: [{
                data: [<?php foreach($statsAseguradora as $a) echo $a['total'].","; ?>],
                backgroundColor: '#e74a3b',
                borderRadius: 5
            }]
        },
        options: {
            ...masterOptions,
            indexAxis: 'y',
            layout: { padding: { right: 60, top: 10, bottom: 10 } },
            plugins: {
                ...masterOptions.plugins,
                datalabels: { ...masterOptions.plugins.datalabels, align: 'right', anchor: 'end' }
            },
            scales: { x: { grace: '15%' }, y: { grid: { display: false } } }
        }
    });

    // 5. DESPACHOS (HORIZONTAL)
    new Chart(document.getElementById('chartDespachos'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsDespacho as $d) echo "'".$d['despacho']."',"; ?>],
            datasets: [{
                data: [<?php foreach($statsDespacho as $d) echo $d['total'].","; ?>],
                backgroundColor: '#36b9cc',
                borderRadius: 5
            }]
        },
        options: {
            ...masterOptions,
            indexAxis: 'y',
            layout: { padding: { right: 60, top: 10, bottom: 10 } },
            plugins: {
                ...masterOptions.plugins,
                datalabels: { ...masterOptions.plugins.datalabels, align: 'right', anchor: 'end' }
            },
            scales: { x: { grace: '15%' }, y: { grid: { display: false } } }
        }
    });
</script>
</body>
</html>
