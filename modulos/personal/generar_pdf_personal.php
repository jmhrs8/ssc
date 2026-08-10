<?php
require_once "../../config/conexion.php";

try {
    $total = $pdo->query("SELECT COUNT(*) FROM personal")->fetchColumn();
    $base = $pdo->query("SELECT COUNT(*) FROM personal WHERE tipo_contratacion = 'BASE'")->fetchColumn();
    $eventual = $pdo->query("SELECT COUNT(*) FROM personal WHERE tipo_contratacion = 'EVENTUAL'")->fetchColumn();

    $statsAreas = $pdo->query("SELECT area_adscripcion, COUNT(*) as total FROM personal WHERE area_adscripcion != '' GROUP BY area_adscripcion ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $statsPuestos = $pdo->query("SELECT puesto, COUNT(*) as total FROM personal WHERE puesto != '' GROUP BY puesto ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error en base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>INFORME DE PERSONAL | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        body { background-color: #ffffff; font-size: 11px; text-transform: uppercase; font-family: 'Segoe UI', sans-serif; color: #333; }
        .header-report { background: #1a1a1a; color: white; padding: 15px 20px; border-bottom: 4px solid #212529; }
        .card { border: 1px solid #dee2e6; border-radius: 10px; box-shadow: none; margin-bottom: 20px; break-inside: avoid; background: #fff; }
        .card-header { background: #f8f9fa !important; border-bottom: 1px solid #dee2e6 !important; font-weight: bold; color: #333; padding: 10px 15px; }
        .stat-card { border-top: 4px solid #212529; text-align: center; padding: 12px; }
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
            <h4 class="mb-0 fw-bold" style="font-size: 16px;"><i class="fas fa-users me-2 text-dark"></i> SECRETARÍA DE SEGURIDAD CIUDADANA</h4>
            <span class="text-secondary opacity-75" style="font-size: 11px;">INFORME GENERAL DE PERSONAL</span>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-dark btn-sm fw-bold text-white"><i class="fas fa-print me-1"></i> IMPRIMIR / GUARDAR PDF</button>
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-undo me-1"></i> REGRESAR</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <div class="row g-2 mb-4 text-center">
        <div class="col-md-4">
            <div class="card stat-card" style="border-top-color: #212529;">
                <small class="text-muted" style="font-size:9px;">TOTAL DE PERSONAL</small>
                <span class="stat-value text-dark"><?= number_format($total) ?></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card" style="border-top-color: #198754;">
                <small class="text-muted" style="font-size:9px;">PERSONAL DE BASE</small>
                <span class="stat-value text-success"><?= number_format($base) ?></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card" style="border-top-color: #0dcaf0;">
                <small class="text-muted" style="font-size:9px;">PERSONAL EVENTUAL</small>
                <span class="stat-value text-info"><?= number_format($eventual) ?></span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header text-dark"><i class="fas fa-id-card me-2"></i> TIPO DE CONTRATACIÓN</div>
                <div class="chart-container" style="height: 200px;"><canvas id="chartContratacion"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-info"><i class="fas fa-building me-2"></i> TOP 10 ÁREAS DE ADSCRIPCIÓN</div>
                <div class="chart-container"><canvas id="chartAreas"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-success"><i class="fas fa-briefcase me-2"></i> TOP 10 PUESTOS</div>
                <div class="chart-container"><canvas id="chartPuestos"></canvas></div>
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

    new Chart(document.getElementById('chartContratacion'), {
        type: 'bar',
        data: {
            labels: ['BASE', 'EVENTUAL'],
            datasets: [{ data: [<?= $base ?>, <?= $eventual ?>], backgroundColor: ['#198754', '#0dcaf0'], borderRadius: 6 }]
        },
        options: masterOptions
    });

    new Chart(document.getElementById('chartAreas'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsAreas as $a) echo "'".$a['area_adscripcion']."',"; ?>],
            datasets: [{ data: [<?php foreach($statsAreas as $a) echo $a['total'].","; ?>], backgroundColor: '#0dcaf0', borderRadius: 4 }]
        },
        options: {
            ...masterOptions, indexAxis: 'y',
            plugins: { ...masterOptions.plugins, datalabels: { align: 'right', anchor: 'end' } },
            scales: { x: { grace: '15%' }, y: { grid: { display: false } } }
        }
    });

    new Chart(document.getElementById('chartPuestos'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsPuestos as $p) echo "'".$p['puesto']."',"; ?>],
            datasets: [{ data: [<?php foreach($statsPuestos as $p) echo $p['total'].","; ?>], backgroundColor: '#198754', borderRadius: 4 }]
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
