<?php
session_start();
require_once "../../config/conexion.php";

$tot_armas = 0;
$monto_convenio = 0;
$total_personal = 0;
$total_siniestros = 0;
$top_areas = [];
$top_aseguradoras = [];
$top_armas_tipo = [];

try {
    $tot_armas = $pdo->query("SELECT SUM(CAST(armas AS UNSIGNED)) FROM inventario_armas")->fetchColumn() ?: 0;
    $monto_convenio = $pdo->query("SELECT SUM(importe_convenio) FROM inventario_armas")->fetchColumn() ?: 0;
    $top_armas_tipo = $pdo->query("SELECT tipo_bien, COUNT(*) as total FROM inventario_armas WHERE tipo_bien != '' GROUP BY tipo_bien ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

try {
    $total_personal = $pdo->query("SELECT COUNT(*) FROM personal")->fetchColumn() ?: 0;
    $top_areas = $pdo->query("SELECT area_adscripcion, COUNT(*) as total FROM personal GROUP BY area_adscripcion ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

try {
    $total_siniestros = $pdo->query("SELECT COUNT(*) FROM siniestros")->fetchColumn() ?: 0;
    $top_aseguradoras = $pdo->query("SELECT aseguradora, COUNT(*) as total FROM siniestros WHERE aseguradora IS NOT NULL AND aseguradora != '' GROUP BY aseguradora ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>INFORME GERENCIAL CONSOLIDADO DE GRÁFICAS | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        body { background-color: #f4f6f9; font-size: 11px; text-transform: uppercase; font-family: 'Segoe UI', sans-serif; }
        .header-report { background: #1a1a1a; color: white; padding: 20px; border-bottom: 4px solid #8b0000; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; background: #fff; }
        .card-header { background: #fff !important; font-weight: bold; color: #333; padding: 12px 15px; border-bottom: 1px solid #eee; }
        .stat-box { background: linear-gradient(135deg, #0d6efd, #0043a8); color: white; border-radius: 12px; padding: 20px; text-align: center; }
        .stat-value { font-size: 26px; font-weight: 800; display: block; margin-top: 5px; }
        .chart-container { position: relative; height: 260px; width: 100%; padding: 10px; }
        .pie-container { position: relative; height: 220px; width: 100%; padding: 10px; }
        @media print {
            .no-print { display: none !important; }
            body { background-color: #fff; }
            .card { break-inside: avoid; border: 1px solid #ddd; box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="header-report mb-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2 text-danger"></i> SECRETARÍA DE SEGURIDAD CIUDADANA</h4>
            <span class="text-light opacity-75">INFORME GERENCIAL CONSOLIDADO DE GRÁFICAS (3 MÓDULOS)</span>
        </div>
        <div class="no-print d-flex gap-2">
            <button onclick="window.print()" class="btn btn-danger btn-sm fw-bold"><i class="fas fa-file-pdf me-1"></i> GUARDAR PDF / IMPRIMIR</button>
            <a href="configurar.php" class="btn btn-outline-light btn-sm"><i class="fas fa-undo me-1"></i> REGRESAR</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <!-- MÓDULO 1: SINIESTROS (Con Gráfica de Barras y de Pastel/Dona) -->
    <div class="row mb-3"><div class="col-12"><h5 class="fw-bold text-dark border-bottom pb-2"><i class="fas fa-car-crash text-danger me-2"></i> 1. MÓDULO DE VEHÍCULOS SINIESTRADOS</h5></div></div>
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">DISTRIBUCIÓN DE SINIESTROS POR ASEGURADORA</div>
                <div class="chart-container"><canvas id="chartSiniestrosBar"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="stat-box mb-3">
                <small class="fw-bold opacity-75">TOTAL REGISTROS SINIESTROS</small>
                <span class="stat-value"><?= number_format($total_siniestros) ?></span>
            </div>
            <div class="card p-3">
                <h6 class="fw-bold text-muted mb-3" style="font-size:10px;">TOP ASEGURADORAS</h6>
                <?php foreach($top_aseguradoras as $s): ?>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span><?= htmlspecialchars($s['aseguradora'] ?: 'S/D') ?></span>
                    <span class="badge bg-dark rounded-pill"><?= number_format($s['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-5">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">PORCENTAJE POR ASEGURADORA (DONA)</div>
                <div class="pie-container"><canvas id="chartSiniestrosPie"></canvas></div>
            </div>
        </div>
    </div>

    <!-- MÓDULO 2: PERSONAL -->
    <div class="row mb-3"><div class="col-12"><h5 class="fw-bold text-dark border-bottom pb-2"><i class="fas fa-users text-primary me-2"></i> 2. MÓDULO DE PADRÓN DE PERSONAL</h5></div></div>
    <div class="row g-3 mb-5">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">TOP ÁREAS DE ADSCRIPCIÓN</div>
                <div class="chart-container"><canvas id="chartPersonalBar"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="stat-box mb-3" style="background: linear-gradient(135deg, #343a40, #212529);">
                <small class="fw-bold opacity-75">TOTAL PERSONAL ACTIVO</small>
                <span class="stat-value"><?= number_format($total_personal) ?></span>
            </div>
            <div class="card p-3">
                <h6 class="fw-bold text-muted mb-3" style="font-size:10px;">TOP ÁREAS</h6>
                <?php foreach($top_areas as $p): ?>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span class="text-truncate" style="max-width: 170px;"><?= htmlspecialchars($p['area_adscripcion']) ?></span>
                    <span class="badge bg-secondary rounded-pill"><?= number_format($p['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- MÓDULO 3: ARMAMENTO -->
    <div class="row mb-3"><div class="col-12"><h5 class="fw-bold text-dark border-bottom pb-2"><i class="fas fa-shield-alt text-success me-2"></i> 3. MÓDULO DE ARMAMENTO Y EQUIPAMIENTO</h5></div></div>
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">DISTRIBUCIÓN POR TIPO DE BIEN</div>
                <div class="chart-container"><canvas id="chartArmasBar"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="stat-box mb-3" style="background: linear-gradient(135deg, #198754, #145c32);">
                <small class="fw-bold opacity-75">MONTO TOTAL CONVENIO</small>
                <span class="stat-value" style="font-size: 20px;">$ <?= number_format($monto_convenio, 2) ?></span>
                <small class="d-block mt-1">Total Armas: <?= number_format($tot_armas) ?></small>
            </div>
            <div class="card p-3">
                <h6 class="fw-bold text-muted mb-3" style="font-size:10px;">PRINCIPALES BIENES</h6>
                <?php foreach($top_armas_tipo as $a): ?>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span><?= htmlspecialchars($a['tipo_bien']) ?></span>
                    <span class="badge bg-success rounded-pill"><?= number_format($a['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    Chart.register(ChartDataLabels);
    const barOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, datalabels: { anchor: 'end', align: 'top', font: { weight: 'bold', size: 9 } } },
        scales: { y: { beginAtZero: true, grace: '15%' }, x: { grid: { display: false } } }
    };
    const pieOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 9 } } }, datalabels: { display: false } }
    };

    new Chart(document.getElementById('chartSiniestrosBar'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($top_aseguradoras as $s) echo "'".addslashes($s['aseguradora'] ?: 'S/D')."',"; ?>],
            datasets: [{ data: [<?php foreach($top_aseguradoras as $s) echo $s['total'].","; ?>], backgroundColor: '#8b0000', borderRadius: 6 }]
        },
        options: barOptions
    });

    new Chart(document.getElementById('chartSiniestrosPie'), {
        type: 'doughnut',
        data: {
            labels: [<?php foreach($top_aseguradoras as $s) echo "'".addslashes($s['aseguradora'] ?: 'S/D')."',"; ?>],
            datasets: [{ data: [<?php foreach($top_aseguradoras as $s) echo $s['total'].","; ?>], backgroundColor: ['#8b0000','#0dcaf0','#ffc107','#198754','#6c757d'] }]
        },
        options: pieOptions
    });

    new Chart(document.getElementById('chartPersonalBar'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($top_areas as $p) echo "'".addslashes(substr($p['area_adscripcion'], 0, 18)).'...',"; ?>],
            datasets: [{ data: [<?php foreach($top_areas as $p) echo $p['total'].","; ?>], backgroundColor: '#0d6efd', borderRadius: 6 }]
        },
        options: barOptions
    });

    new Chart(document.getElementById('chartArmasBar'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($top_armas_tipo as $a) echo "'".addslashes($a['tipo_bien'])."',"; ?>],
            datasets: [{ data: [<?php foreach($top_armas_tipo as $a) echo $a['total'].","; ?>], backgroundColor: '#198754', borderRadius: 6 }]
        },
        options: barOptions
    });
</script>
</body>
</html>
