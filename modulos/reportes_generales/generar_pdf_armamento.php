<?php
require_once "../../config/conexion.php";

try {
    $tipoBien = $pdo->query("SELECT tipo_bien, COUNT(*) as total FROM inventario_armas WHERE tipo_bien != '' GROUP BY tipo_bien ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    $statsSemovientes = $pdo->query("SELECT tipo_bien, COUNT(*) as total FROM inventario_armas WHERE tipo_bien LIKE '%CANINO%' OR tipo_bien LIKE '%EQUINO%' GROUP BY tipo_bien")->fetchAll(PDO::FETCH_ASSOC);
    $statsAseguradora = $pdo->query("SELECT aseguradora, COUNT(*) as total FROM inventario_armas WHERE aseguradora != '' GROUP BY aseguradora ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $statsDespacho = $pdo->query("SELECT despacho, COUNT(*) as total FROM inventario_armas WHERE despacho != '' GROUP BY despacho ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $statusSeguro = $pdo->query("SELECT status_seguro, COUNT(*) as total FROM inventario_armas WHERE status_seguro != '' GROUP BY status_seguro ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    
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
    <title>INFORME DE ARMAMENTO Y SEMOVIENTES | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        body { background-color: #ffffff; font-size: 11px; text-transform: uppercase; font-family: 'Segoe UI', sans-serif; color: #333; }
        .header-report { background: #1a1a1a; color: white; padding: 15px 20px; border-bottom: 4px solid #0dcaf0; }
        .card { border: 1px solid #dee2e6; border-radius: 10px; box-shadow: none; margin-bottom: 20px; break-inside: avoid; background: #fff; }
        .card-header { background: #f8f9fa !important; border-bottom: 1px solid #dee2e6 !important; font-weight: bold; color: #333; padding: 10px 15px; }
        .stat-card { border-top: 4px solid #0dcaf0; text-align: center; padding: 12px; }
        .stat-value { font-size: 18px; font-weight: 800; display: block; }
        .chart-container { position: relative; height: 260px; width: 100%; padding: 10px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .container-fluid { width: 100%; padding: 0; }
        }
    </style>
</head>
<body onload="setTimeout(() => { window.print(); }, 800);">

<div class="header-report mb-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold" style="font-size: 16px;"><i class="fas fa-shield-alt me-2 text-info"></i> SECRETARÍA DE SEGURIDAD CIUDADANA</h4>
            <span class="text-info opacity-75" style="font-size: 11px;">INFORME DE ARMAMENTO Y SEMOVIENTES</span>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-info btn-sm fw-bold text-dark"><i class="fas fa-print me-1"></i> IMPRIMIR / GUARDAR PDF</button>
            <a href="configurar.php" class="btn btn-outline-light btn-sm"><i class="fas fa-undo me-1"></i> REGRESAR</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <!-- TARJETAS DE TOTALES -->
    <div class="row g-2 mb-4 text-center">
        <div class="col">
            <div class="card stat-card">
                <small class="text-muted" style="font-size:9px;">ARMAS</small>
                <span class="stat-value text-dark"><?= number_format($totales['total_armas'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card" style="border-top-color: #6610f2;">
                <small class="text-muted" style="font-size:9px;">CANINOS</small>
                <span class="stat-value" style="color: #6610f2;"><?= number_format($totales['total_caninos'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card" style="border-top-color: #6610f2;">
                <small class="text-muted" style="font-size:9px;">EQUINOS</small>
                <span class="stat-value" style="color: #6610f2;"><?= number_format($totales['total_equinos'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card" style="border-top-color: #fd7e14;">
                <small class="text-muted" style="font-size:9px;">CARTUCHOS</small>
                <span class="stat-value text-warning"><?= number_format($totales['total_cartuchos'] ?? 0) ?></span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-2 text-center" style="background: #198754; color: white; border-radius: 10px;">
                <small class="fw-bold" style="font-size:9px;">MONTO TOTAL CONVENIO</small>
                <div class="stat-value" style="font-size: 15px;">$ <?= number_format($totales['gran_total_dinero'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- BLOQUE DE GRÁFICAS -->
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-primary"><i class="fas fa-paw me-2"></i> DETALLE DE SEMOVIENTES</div>
                <div class="chart-container"><canvas id="chartSemovientes"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-success"><i class="fas fa-tasks me-2"></i> ESTATUS DE SEGURO</div>
                <div class="chart-container"><canvas id="chartSeguro"></canvas></div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><i class="fas fa-boxes me-2"></i> DISTRIBUCIÓN POR TIPO DE BIEN</div>
                <div class="chart-container" style="height: 280px;"><canvas id="chartBienes"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-danger"><i class="fas fa-building me-2"></i> TOP 10 ASEGURADORAS</div>
                <div class="chart-container"><canvas id="chartAseguradoras"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-info"><i class="fas fa-briefcase me-2"></i> TOP 10 DESPACHOS</div>
                <div class="chart-container"><canvas id="chartDespachos"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script>
    Chart.register(ChartDataLabels);
    const masterOptions = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 25, right: 15, left: 15, bottom: 10 } },
        plugins: {
            legend: { display: false },
            datalabels: { anchor: 'end', align: 'top', font: { weight: 'bold', size: 10 }, formatter: (value) => value.toLocaleString() }
        },
        scales: { y: { beginAtZero: true, grace: '15%', grid: { color: '#f0f0f0' } }, x: { grid: { display: false } } }
    };

    new Chart(document.getElementById('chartSemovientes'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsSemovientes as $s) echo "'".$s['tipo_bien']."',"; ?>],
            datasets: [{ data: [<?php foreach($statsSemovientes as $s) echo $s['total'].","; ?>], backgroundColor: '#6610f2', borderRadius: 6 }]
        },
        options: masterOptions
    });

    new Chart(document.getElementById('chartSeguro'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statusSeguro as $s) echo "'".($s['status_seguro']?:'S/D')."',"; ?>],
            datasets: [{ data: [<?php foreach($statusSeguro as $s) echo $s['total'].","; ?>], backgroundColor: '#1cc88a', borderRadius: 6 }]
        },
        options: masterOptions
    });

    new Chart(document.getElementById('chartBienes'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($tipoBien as $b) echo "'".$b['tipo_bien']."',"; ?>],
            datasets: [{ data: [<?php foreach($tipoBien as $b) echo $b['total'].","; ?>], backgroundColor: '#4e73df', borderRadius: 4 }]
        },
        options: masterOptions
    });

    new Chart(document.getElementById('chartAseguradoras'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsAseguradora as $a) echo "'".$a['aseguradora']."',"; ?>],
            datasets: [{ data: [<?php foreach($statsAseguradora as $a) echo $a['total'].","; ?>], backgroundColor: '#e74a3b', borderRadius: 4 }]
        },
        options: {
            ...masterOptions, indexAxis: 'y',
            plugins: { ...masterOptions.plugins, datalabels: { align: 'right', anchor: 'end' } },
            scales: { x: { grace: '15%' }, y: { grid: { display: false } } }
        }
    });

    new Chart(document.getElementById('chartDespachos'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsDespacho as $d) echo "'".$d['despacho']."',"; ?>],
            datasets: [{ data: [<?php foreach($statsDespacho as $d) echo $d['total'].","; ?>], backgroundColor: '#36b9cc', borderRadius: 4 }]
        },
        options: {
            ...masterOptions, indexAxis: 'y',
            plugins: { ...masterOptions.plugins, datalabels: { align: 'right', anchor: 'end' } },
            scales: { x: { grace: '15%' }, y: { grid: { display: false } } }
        }
    });
</script>
</body>
</html>
