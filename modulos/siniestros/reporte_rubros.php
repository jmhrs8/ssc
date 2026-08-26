<?php
require_once "../../config/conexion.php";

try {
    // 1. Análisis Mensual ordenado estrictamente de Enero a Diciembre
    $sql_mes = "SELECT mes as categoria, COUNT(*) as total 
                FROM siniestros 
                WHERE mes IS NOT NULL AND mes != '' 
                GROUP BY mes 
                ORDER BY FIELD(mes, 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE')";
    $res_mes = $pdo->query($sql_mes)->fetchAll(PDO::FETCH_ASSOC);

    // 2. Demás rubros
    $res_marca = $pdo->query("SELECT marca as categoria, COUNT(*) as total FROM siniestros WHERE marca IS NOT NULL AND marca != '' GROUP BY marca ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Todas las adscripciones ordenadas de mayor a menor para la tabla con scroll
    $res_adscripcion_completo = $pdo->query("SELECT adscripcion as categoria, COUNT(*) as total FROM siniestros WHERE adscripcion IS NOT NULL AND adscripcion != '' GROUP BY adscripcion ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top 20 para la gráfica de adscripción para evitar saturación
    $res_adscripcion_top20 = array_slice($res_adscripcion_completo, 0, 20);

    $res_aseguradora = $pdo->query("SELECT aseguradora as categoria, COUNT(*) as total FROM siniestros WHERE aseguradora IS NOT NULL AND aseguradora != '' GROUP BY aseguradora ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    $res_visto_bueno = $pdo->query("SELECT visto_bueno as categoria, COUNT(*) as total FROM siniestros WHERE visto_bueno IS NOT NULL AND visto_bueno != '' GROUP BY visto_bueno ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

    // Obtener el total general de registros
    $total_general = $pdo->query("SELECT COUNT(*) as total FROM siniestros")->fetch()['total'] ?? 0;

} catch (PDOException $e) {
    die("Error en el sistema: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte General Consolidado de Siniestros | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js y Plugin para mostrar etiquetas/totales -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; text-transform: uppercase; font-family: sans-serif; font-size: 11px; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 12px; }
        .chart-container { position: relative; height: 220px; width: 100%; }
        .chart-container-lg { position: relative; height: 420px; width: 100%; }
        
        /* Contenedor con scroll elegante para la tabla completa de adscripciones */
        .table-scroll-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
        .table-scroll-container thead th {
            position: sticky;
            top: 0;
            background-color: #343a40;
            color: white;
            z-index: 1;
        }

        /* Estilos optimizados para impresión limpia */
        @media print {
            body { background-color: white !important; font-size: 8px !important; }
            .no-print { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ccc !important; break-inside: avoid; margin-bottom: 6px !important; }
            .container-fluid { max-width: 100% !important; width: 100% !important; padding: 0 !important; margin: 0 !important; }
            .chart-container { height: 140px !important; }
            .chart-container-lg { height: 250px !important; }
            .table-scroll-container { max-height: none !important; overflow: visible !important; }
        }
    </style>
</head>
<body class="p-3">

<div class="container-fluid">
    <!-- Encabezado y botón de impresión (Se ocultan al imprimir) -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div>
            <h1 class="h3 fw-bold text-dark mb-0"><i class="fas fa-chart-bar text-primary"></i> Reporte Consolidado por Rubros</h1>
            <a href="index.php" class="btn btn-sm btn-outline-secondary mt-1"><i class="fas fa-arrow-left"></i> Volver al Listado</a>
        </div>
        <button onclick="window.print();" class="btn btn-secondary shadow-sm">
            <i class="fas fa-print"></i> Imprimir Reporte Completo
        </button>
    </div>

    <!-- Encabezado visible únicamente al imprimir -->
    <div class="text-center d-none d-print-block mb-2">
        <h4 class="fw-bold">SECRETARÍA DE SEGURIDAD CIUDADANA - REPORTE GENERAL DE SINIESTROS</h4>
        <p class="mb-0">ESTADÍSTICAS CONSOLIDADAS POR RUBROS | TOTAL DE REGISTROS: <?= $total_general ?></p>
        <hr class="my-1">
    </div>

    <!-- Tarjeta de Resumen General -->
    <div class="row mb-2">
        <div class="col-12">
            <div class="card p-2 bg-dark text-white text-center">
                <span class="fs-6 fw-bold">TOTAL GENERAL DE SINIESTROS REGISTRADOS EN EL SISTEMA: <?= $total_general ?></span>
            </div>
        </div>
    </div>

    <!-- Cuadrícula de Gráficas -->
    <div class="row">
        <!-- 1. Análisis Mensual -->
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="card-title text-muted fw-bold mb-2">Análisis Mensual (Enero a Diciembre)</h6>
                <div class="chart-container">
                    <canvas id="chartMes"></canvas>
                </div>
            </div>
        </div>

        <!-- 2. Análisis por Marca -->
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="card-title text-muted fw-bold mb-2">Análisis por Marca</h6>
                <div class="chart-container">
                    <canvas id="chartMarca"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. Análisis por Adscripción (Top 20 en gráfica + Tabla completa con scroll) -->
        <div class="col-12">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title text-muted fw-bold mb-0">Análisis por Adscripción (Top 20 con Mayor Incidencia)</h6>
                    <span class="badge bg-secondary">Mostrando Top 20 en gráfica de <?= count($res_adscripcion_completo) ?> totales</span>
                </div>
                <div class="chart-container-lg mb-3">
                    <canvas id="chartAdscripcion"></canvas>
                </div>

                <!-- Listado con scroll estilo tabla para ver absolutamente todas las adscripciones -->
                <h6 class="text-muted fw-bold mb-2"><i class="fas fa-list"></i> Detalle Completo de Adscripciones (Con Barra de Desplazamiento)</h6>
                <div class="table-scroll-container">
                    <table class="table table-striped table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Adscripción</th>
                                <th class="text-end">Total de Siniestros</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($res_adscripcion_completo as $index => $row): ?>
                            <tr>
                                <td class="fw-bold text-center" style="width: 50px;"><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($row['categoria']) ?></td>
                                <td class="text-end fw-bold"><?= $row['total'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. Análisis por Aseguradora -->
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="card-title text-muted fw-bold mb-2">Análisis por Aseguradora</h6>
                <div class="chart-container">
                    <canvas id="chartAseguradora"></canvas>
                </div>
            </div>
        </div>

        <!-- 5. Análisis Visto Bueno -->
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="card-title text-muted fw-bold mb-2">Análisis Visto Bueno</h6>
                <div class="chart-container">
                    <canvas id="chartVistoBueno"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Registrar el plugin de ChartDataLabels globalmente
Chart.register(ChartDataLabels);

// Función auxiliar para renderizar gráficas con totales
function crearGrafica(canvasId, tipo, datosRes, colorFondo, esHorizontal = false) {
    const labels = datosRes.map(item => item.categoria);
    const data = datosRes.map(item => item.total);

    return new Chart(document.getElementById(canvasId), {
        type: tipo,
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colorFondo,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: esHorizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    display: tipo === 'doughnut' || tipo === 'pie',
                    position: 'right',
                    labels: { boxWidth: 10, font: { size: 9 } }
                },
                datalabels: {
                    anchor: esHorizontal ? 'end' : (tipo === 'bar' ? 'end' : 'center'),
                    align: esHorizontal ? 'right' : (tipo === 'bar' ? 'top' : 'center'),
                    color: '#333',
                    font: {
                        weight: 'bold',
                        size: 9
                    },
                    formatter: function(value) {
                        return value > 0 ? value : '';
                    }
                }
            },
            scales: {
                x: esHorizontal 
                    ? { beginAtZero: true, grace: '15%' } 
                    : (tipo === 'bar' ? { grid: { display: false } } : {}),
                y: esHorizontal 
                    ? { 
                        reverse: true, // Mantiene el mayor arriba
                        grid: { display: false }, 
                        ticks: { font: { size: 9 } } 
                      } 
                    : (tipo === 'bar' ? { beginAtZero: true, grace: '10%' } : {})
            }
        }
    });
}

// Cargar cada gráfica con sus datos correspondientes
crearGrafica('chartMes', 'bar', <?= json_encode($res_mes) ?>, '#3498db', false);
crearGrafica('chartMarca', 'bar', <?= json_encode($res_marca) ?>, '#2ecc71', false);
// Gráfica de Adscripción limitada al Top 20 limpio
crearGrafica('chartAdscripcion', 'bar', <?= json_encode($res_adscripcion_top20) ?>, '#e67e22', true);
crearGrafica('chartAseguradora', 'bar', <?= json_encode($res_aseguradora) ?>, '#9b59b6', false);
crearGrafica('chartVistoBueno', 'doughnut', <?= json_encode($res_visto_bueno) ?>, ['#1abc9c', '#e74c3c', '#f1c40f', '#34495e', '#d35400'], false);
</script>

</body>
</html>
