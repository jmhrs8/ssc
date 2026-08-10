<?php
session_start();
require_once "../../config/conexion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

try {
    // 1. Total de registros
    $total_reg = $pdo->query("SELECT COUNT(*) FROM inventario_armas")->fetchColumn();

    // 2. Datos para Status Seguro (Gráfica de Barras)
    $query_estatus = "SELECT status_seguro as etiqueta, COUNT(*) as total 
                      FROM inventario_armas 
                      GROUP BY status_seguro ORDER BY total DESC";
    $res_estatus = $pdo->query($query_estatus)->fetchAll(PDO::FETCH_ASSOC);

    // 3. Datos para Tipo de Bien (Gráfica de Barras)
    $query_tipo = "SELECT tipo_bien as etiqueta, COUNT(*) as total 
                   FROM inventario_armas 
                   GROUP BY tipo_bien ORDER BY total DESC";
    $res_tipo = $pdo->query($query_tipo)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("ERROR EN REPORTE: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ANÁLISIS DE ARMAMENTO | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js + Plugin para números en barras -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    
    <style>
        body { font-size: 13px; background-color: #f0f2f5; text-transform: uppercase; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-header { background: white; border-bottom: 1px solid #eee; font-weight: bold; border-radius: 15px 15px 0 0 !important; }
        .stat-card { background: #0d6efd; color: white; text-align: center; padding: 30px; border-radius: 15px; }
        .stat-number { font-size: 3rem; font-weight: 800; }
        .list-group-item { border: none; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; }
        .badge-count { background: #6c757d; border-radius: 20px; padding: 5px 12px; }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white p-2 rounded-circle me-3" style="width: 45px; height: 45px; display: grid; place-items: center;">
                <i class="fas fa-chart-bar fs-4"></i>
            </div>
            <h3 class="mb-0 fw-bold">Análisis de Armamento</h3>
        </div>
        <a href="index.php" class="btn btn-outline-dark rounded-pill"><i class="fas fa-arrow-left"></i> Volver al Listado</a>
    </div>

    <div class="row">
        <!-- Columna de Gráficas -->
        <div class="col-md-8">
            <div class="card mb-4 p-3">
                <div class="card-header px-1">Distribución por Status Seguro</div>
                <div class="card-body" style="height: 350px;">
                    <canvas id="chartEstatus"></canvas>
                </div>
            </div>

            <div class="card p-3">
                <div class="card-header px-1">Distribución por Tipo de Bien</div>
                <div class="card-body" style="height: 350px;">
                    <canvas id="chartTipo"></canvas>
                </div>
            </div>
        </div>

        <!-- Columna Lateral de Resumen -->
        <div class="col-md-4">
            <div class="stat-card shadow mb-4">
                <div class="text-white-50 fw-bold mb-2">TOTAL REGISTROS</div>
                <div class="stat-number"><?= number_format($total_reg) ?></div>
            </div>

            <div class="card">
                <div class="card-header"><i class="fas fa-list-ol me-2 text-primary"></i>Top Categorías (Status)</div>
                <ul class="list-group list-group-flush p-2">
                    <?php foreach(array_slice($res_estatus, 0, 5) as $r): ?>
                    <li class="list-group-item fw-bold">
                        <?= $r['etiqueta'] ?>
                        <span class="badge badge-count text-white"><?= $r['total'] ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    // Registrar el plugin de etiquetas de datos globalmente
    Chart.register(ChartDataLabels);

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            datalabels: {
                anchor: 'end',
                align: 'top',
                color: '#444',
                font: { weight: 'bold', size: 12 },
                formatter: (value) => value
            }
        },
        scales: {
            y: { 
                beginAtZero: true, 
                grid: { drawBorder: false },
                ticks: { display: true } 
            },
            x: { grid: { display: false } }
        }
    };

    // Gráfica de Status
    new Chart(document.getElementById('chartEstatus'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($res_estatus as $r) echo "'".$r['etiqueta']."',"; ?>],
            datasets: [{
                data: [<?php foreach($res_estatus as $r) echo $r['total'].","; ?>],
                backgroundColor: '#3498db',
                borderRadius: 8,
                barThickness: 50
            }]
        },
        options: commonOptions
    });

    // Gráfica de Tipo de Bien
    new Chart(document.getElementById('chartTipo'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($res_tipo as $r) echo "'".$r['etiqueta']."',"; ?>],
            datasets: [{
                data: [<?php foreach($res_tipo as $r) echo $r['total'].","; ?>],
                backgroundColor: '#2ecc71',
                borderRadius: 8,
                barThickness: 50
            }]
        },
        options: commonOptions
    });
</script>
</body>
</html>
