<?php
require_once "../../config/conexion.php";

$rubro = $_GET['rubro'] ?? 'mes';
$validos = ['mes', 'marca', 'adscripcion', 'aseguradora', 'visto_bueno'];
if (!in_array($rubro, $validos)) { $rubro = 'mes'; }

try {
    // Consulta para las gráficas y las tarjetas
    $sql = "SELECT $rubro as categoria, COUNT(*) as total 
            FROM siniestros 
            WHERE $rubro IS NOT NULL AND $rubro != ''
            GROUP BY $rubro ORDER BY total DESC";
    $res = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Preparar datos para JavaScript
    $labels = [];
    $counts = [];
    foreach ($res as $row) {
        $labels[] = $row['categoria'];
        $counts[] = $row['total'];
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .chart-container { position: relative; height: 400px; width: 100%; }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 fw-bold text-dark"><i class="fas fa-chart-pie text-primary"></i> Análisis por <?= ucfirst($rubro) ?></h1>
            <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver al Listado</a>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-dark dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                Seleccionar Rubro
            </button>
            <ul class="dropdown-menu shadow">
                <li><a class="dropdown-item" href="?rubro=mes">Análisis Mensual</a></li>
                <li><a class="dropdown-item" href="?rubro=marca">Análisis por Marca</a></li>
                <li><a class="dropdown-item" href="?rubro=adscripcion">Análisis por Adscripción</a></li>
                <li><a class="dropdown-item" href="?rubro=aseguradora">Análisis por Aseguradora</a></li>
                <li><a class="dropdown-item" href="?rubro=visto_bueno">Análisis Visto Bueno</a></li>
            </ul>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card p-4">
                <h5 class="card-title text-muted mb-4">Distribución de Siniestros</h5>
                <div class="chart-container">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 mb-3 bg-primary text-white text-center">
                <h6>Total Registros</h6>
                <h1 class="display-4 fw-bold"><?= array_sum($counts) ?></h1>
            </div>
            
            <div class="card p-3">
                <h6 class="text-muted mb-3">Top Categorías</h6>
                <ul class="list-group list-group-flush">
                    <?php 
                    $top = array_slice($res, 0, 5); 
                    foreach($top as $t): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <?= $t['categoria'] ?>
                            <span class="badge bg-secondary rounded-pill"><?= $t['total'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card p-4">
                <h5 class="card-title text-muted mb-3">Porcentaje por Rubro</h5>
                <div style="height: 300px;">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Datos desde PHP
const labels = <?= json_encode($labels) ?>;
const data = <?= json_encode($counts) ?>;

// Configuración Gráfica de Barras
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Cantidad de Siniestros',
            data: data,
            backgroundColor: '#3498db',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
    }
});

// Configuración Gráfica de Dona
new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: [
                '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6', '#34495e', '#1abc9c', '#e67e22'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

</body>
</html>
