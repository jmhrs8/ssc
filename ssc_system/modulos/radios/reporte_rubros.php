<?php
require_once "../../config/conexion.php";

$rubro = $_GET['rubro'] ?? 'estatus';
$columnas_validas = ['estatus', 'aseguradora', 'tipo_siniestro', 'anio'];
if (!in_array($rubro, $columnas_validas)) { $rubro = 'estatus'; }

try {
    $stmt = $pdo->prepare("SELECT $rubro as etiqueta, COUNT(*) as total FROM inventario_radio GROUP BY $rubro ORDER BY total DESC");
    $stmt->execute();
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $counts = [];
    foreach ($datos as $d) {
        $labels[] = $d['etiqueta'] ?: 'SIN DATO';
        $counts[] = $d['total'];
    }
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte - SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        body { background-color: #f4f6f9; }
        .chart-container { background: white; padding: 30px; border-radius: 15px; shadow: 0 4px 15px rgba(0,0,0,0.2); }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-chart-bar"></i> Distribución por <?= strtoupper($rubro) ?></h4>
        <div class="btn-group">
            <a href="?rubro=estatus" class="btn btn-sm btn-outline-dark">Estatus</a>
            <a href="?rubro=aseguradora" class="btn btn-sm btn-outline-dark">Aseguradora</a>
            <a href="index.php" class="btn btn-sm btn-danger">Cerrar</a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10 chart-container">
            <canvas id="graficaRubros"></canvas>
        </div>
    </div>
</div>

<script>
// Registrar el plugin de etiquetas de datos
Chart.register(ChartDataLabels);

const ctx = document.getElementById('graficaRubros').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Cantidad de Registros',
            data: <?= json_encode($counts) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            // CONFIGURACIÓN DE LOS TOTALES SOBRE LAS BARRAS
            datalabels: {
                anchor: 'end',
                align: 'top',
                formatter: Math.round,
                font: { weight: 'bold', size: 14 },
                color: '#2c3e50'
            }
        },
        scales: {
            y: { 
                beginAtZero: true,
                grace: '10%' // Da espacio arriba para que el número no se corte
            }
        }
    }
});
</script>
</body>
</html>
