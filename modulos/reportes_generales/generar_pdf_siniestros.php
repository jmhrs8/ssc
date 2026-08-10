<?php
require_once "../../config/conexion.php";

try {
    $total_siniestros = $pdo->query("SELECT COUNT(*) FROM siniestros")->fetchColumn();
    
    $statsMes = $pdo->query("SELECT mes as categoria, COUNT(*) as total FROM siniestros WHERE mes IS NOT NULL AND mes != '' GROUP BY mes ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    $statsMarca = $pdo->query("SELECT marca as categoria, COUNT(*) as total FROM siniestros WHERE marca IS NOT NULL AND marca != '' GROUP BY marca ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $statsAdscripcion = $pdo->query("SELECT adscripcion as categoria, COUNT(*) as total FROM siniestros WHERE adscripcion IS NOT NULL AND adscripcion != '' GROUP BY adscripcion ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $statsAseguradora = $pdo->query("SELECT aseguradora as categoria, COUNT(*) as total FROM siniestros WHERE aseguradora IS NOT NULL AND aseguradora != '' GROUP BY aseguradora ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $statsVistoBueno = $pdo->query("SELECT visto_bueno as categoria, COUNT(*) as total FROM siniestros WHERE visto_bueno IS NOT NULL AND visto_bueno != '' GROUP BY visto_bueno ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>INFORME DE SINIESTROS | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        body { background-color: #ffffff; font-size: 11px; text-transform: uppercase; font-family: 'Segoe UI', sans-serif; color: #333; }
        .header-report { background: #1a1a1a; color: white; padding: 15px 20px; border-bottom: 4px solid #198754; }
        .card { border: 1px solid #dee2e6; border-radius: 10px; box-shadow: none; margin-bottom: 20px; break-inside: avoid; background: #fff; }
        .card-header { background: #f8f9fa !important; border-bottom: 1px solid #dee2e6 !important; font-weight: bold; color: #333; padding: 10px 15px; }
        .stat-card { border-top: 4px solid #198754; text-align: center; padding: 12px; }
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
            <h4 class="mb-0 fw-bold" style="font-size: 16px;"><i class="fas fa-car-crash me-2 text-success"></i> SECRETARÍA DE SEGURIDAD CIUDADANA</h4>
            <span class="text-success opacity-75" style="font-size: 11px;">INFORME GENERAL DE SINIESTROS</span>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-success btn-sm fw-bold text-white"><i class="fas fa-print me-1"></i> IMPRIMIR / GUARDAR PDF</button>
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-undo me-1"></i> REGRESAR</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <div class="row g-2 mb-4 text-center">
        <div class="col-md-12">
            <div class="card stat-card" style="border-top-color: #198754;">
                <small class="text-muted" style="font-size:9px;">TOTAL DE SINIESTROS REGISTRADOS</small>
                <span class="stat-value text-success"><?= number_format($total_siniestros) ?></span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-success"><i class="fas fa-calendar-alt me-2"></i> ANÁLISIS MENSUAL</div>
                <div class="chart-container"><canvas id="chartMes"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-primary"><i class="fas fa-building me-2"></i> VISTO BUENO</div>
                <div class="chart-container"><canvas id="chartVistoBueno"></canvas></div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header text-dark"><i class="fas fa-car me-2"></i> TOP 10 MARCAS</div>
                <div class="chart-container" style="height: 280px;"><canvas id="chartMarca"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-danger"><i class="fas fa-map-marker-alt me-2"></i> TOP 10 ADSCRIPCIÓN</div>
                <div class="chart-container"><canvas id="chartAdscripcion"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-info"><i class="fas fa-shield-alt me-2"></i> TOP 10 ASEGURADORAS</div>
                <div class="chart-container"><canvas id="chartAseguradora"></canvas></div>
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

    new Chart(document.getElementById('chartMes'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsMes as $s) echo "'".$s['categoria']."',"; ?>],
            datasets: [{ data: [<?php foreach($statsMes as $s) echo $s['total'].","; ?>], backgroundColor: '#198754', borderRadius: 6 }]
        },
        options: masterOptions
    });

    new Chart(document.getElementById('chartVistoBueno'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsVistoBueno as $s) echo "'".$s['categoria']."',"; ?>],
            datasets: [{ data: [<?php foreach($statsVistoBueno as $s) echo $s['total'].","; ?>], backgroundColor: '#0d6efd', borderRadius: 6 }]
        },
        options: masterOptions
    });

    new Chart(document.getElementById('chartMarca'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsMarca as $s) echo "'".$s['categoria']."',"; ?>],
            datasets: [{ data: [<?php foreach($statsMarca as $s) echo $s['total'].","; ?>], backgroundColor: '#ffc107', borderRadius: 4 }]
        },
        options: masterOptions
    });

    new Chart(document.getElementById('chartAdscripcion'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsAdscripcion as $s) echo "'".$s['categoria']."',"; ?>],
            datasets: [{ data: [<?php foreach($statsAdscripcion as $s) echo $s['total'].","; ?>], backgroundColor: '#dc3545', borderRadius: 4 }]
        },
        options: {
            ...masterOptions, indexAxis: 'y',
            plugins: { ...masterOptions.plugins, datalabels: { align: 'right', anchor: 'end' } },
            scales: { x: { grace: '15%' }, y: { grid: { display: false } } }
        }
    });

    new Chart(document.getElementById('chartAseguradora'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($statsAseguradora as $s) echo "'".$s['categoria']."',"; ?>],
            datasets: [{ data: [<?php foreach($statsAseguradora as $s) echo $s['total'].","; ?>], backgroundColor: '#0dcaf0', borderRadius: 4 }]
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
