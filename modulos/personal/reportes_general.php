<?php
session_start();
require_once "../../config/conexion.php";

// Seguridad básica de sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$nivel_actual = strtoupper(trim($_SESSION['nivel'] ?? ''));
$permiso_siniestros = $_SESSION['permiso_siniestros'] ?? 0;

// Validación de roles administrativos
$es_admin_general = ($nivel_actual === 'ADMIN_GENERAL');
$es_administrador = ($es_admin_general || $nivel_actual === 'ADMINISTRADOR');

// ==========================================
// PROCESAR LIMPIEZA DE LA BITÁCORA
// ==========================================
if (isset($_POST['vaciar_bitacora'])) {
    if (!$es_administrador) {
        header("Location: reportes_general.php?error=sin_permiso");
        exit();
    }
    try {
        $pdo->exec("TRUNCATE TABLE personal_eliminados");
        header("Location: reportes_general.php?limp=1");
        exit();
    } catch (PDOException $e) {
        die("Error al vaciar: " . $e->getMessage());
    }
}

try {
    // 1. Estadísticas principales
    $total = $pdo->query("SELECT COUNT(*) FROM personal")->fetchColumn();
    $base = $pdo->query("SELECT COUNT(*) FROM personal WHERE tipo_contratacion = 'BASE'")->fetchColumn();
    $eventual = $pdo->query("SELECT COUNT(*) FROM personal WHERE tipo_contratacion = 'EVENTUAL'")->fetchColumn();

    // 2. Desglose completo para la tabla de Áreas
    $stmt_areas = $pdo->query("SELECT area_adscripcion, COUNT(*) as total FROM personal GROUP BY area_adscripcion ORDER BY total DESC");
    $por_area = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

    // 3. Desglose completo para la tabla de Puestos
    $stmt_puestos = $pdo->query("SELECT puesto, COUNT(*) as total FROM personal GROUP BY puesto ORDER BY total DESC");
    $por_puesto = $stmt_puestos->fetchAll(PDO::FETCH_ASSOC);

    // Top 10 para las gráficas
    $top_areas = array_slice($por_area, 0, 10);
    $top_puestos = array_slice($por_puesto, 0, 10);

    // 4. Desglose de Altas / Registros por Quincena
    $stmt_quincenas = $pdo->query("SELECT quincena, COUNT(*) as total FROM personal WHERE quincena IS NOT NULL AND quincena != '' GROUP BY quincena ORDER BY quincena DESC");
    $por_quincena = $stmt_quincenas->fetchAll(PDO::FETCH_ASSOC);

    // 5. Total y resumen agrupado de la bitácora de eliminados
    $total_eliminados = $pdo->query("SELECT COUNT(*) FROM personal_eliminados")->fetchColumn();
    $stmt_eliminados = $pdo->query("
        SELECT 
            DATE(fecha_eliminacion) as fecha_baja, 
            tipo_contratacion, 
            COUNT(*) as total_bajas 
        FROM personal_eliminados 
        GROUP BY DATE(fecha_eliminacion), tipo_contratacion 
        ORDER BY fecha_baja DESC
    ");
    $eliminados = $stmt_eliminados->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en reporte: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTES Y AUDITORÍA | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Plugin DataLabels -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <style>
        body { background: #f4f6f9; text-transform: uppercase; font-size: 11px; }
        .card { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-radius: 8px; margin-bottom: 1rem; }
        .card-header { background-color: #212529; color: white; font-weight: 600; font-size: 12px; }
        .table th { background: #343a40 !important; color: white !important; text-align: center; white-space: nowrap; }
        .table td { vertical-align: middle; }
        .no-print { display: inline-block; }

        /* Estilos estrictos para impresión y PDF limpio */
        @media print {
            body { background: white !important; font-size: 9px; color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print, .btn, form, hr { display: none !important; }
            
            .card { border: 1px solid #ccc !important; box-shadow: none !important; margin-bottom: 10px !important; }
            .container-fluid { width: 100% !important; padding: 0 !important; margin: 0 !important; }

            .bloque-impresion {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                break-after: page !important;
                page-break-after: always !important;
            }

            .grafica-contenedor {
                height: 200px !important;
                max-height: 200px !important;
                position: relative !important;
                overflow: hidden !important;
            }

            .grafica-contenedor-circular {
                height: 200px !important;
                max-height: 200px !important;
                position: relative !important;
                overflow: hidden !important;
            }

            table { font-size: 9px !important; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 mt-4 mb-5">
    <!-- Cabecera Gerencial -->
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm no-print">
        <div>
            <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line text-primary"></i> PANEL GERENCIAL Y ESTADÍSTICAS</h4>
            <small class="text-muted">CONTROL GENERAL DE PERSONAL, DISTRIBUCIÓN Y BITÁCORA DE AUDITORÍA</small>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-3"><i class="fas fa-print"></i> IMPRIMIR / PDF</button>
            <a href="index.php" class="btn btn-dark btn-sm px-3"><i class="fas fa-arrow-left"></i> VOLVER AL PADRÓN</a>
        </div>
    </div>

    <!-- Alertas de Estado -->
    <?php if (isset($_GET['limp']) && $_GET['limp'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 no-print" role="alert">
            <i class="fas fa-check-circle"></i> La bitácora de eliminados ha sido limpiada exitosamente.
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] == 'sin_permiso'): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 no-print" role="alert">
            <i class="fas fa-exclamation-triangle"></i> Acceso denegado: No cuentas con permisos de administrador para realizar esta acción.
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- BLOQUE 1: Tarjetas y Gráfica Circular -->
    <div class="bloque-impresion">
        <div class="row text-center mb-3 g-3">
            <div class="col-md-4 col-4">
                <div class="card p-3 border-start border-4 border-dark">
                    <span class="text-muted fw-bold">TOTAL DE PERSONAL ACTIVO</span>
                    <h2 class="display-6 fw-bold text-dark mt-2 mb-0"><?= number_format($total) ?></h2>
                    <small class="text-secondary mt-1">REGISTROS VIGENTES</small>
                </div>
            </div>
            <div class="col-md-4 col-4">
                <div class="card p-3 border-start border-4 border-success">
                    <span class="text-success fw-bold">PERSONAL DE BASE</span>
                    <h2 class="display-6 fw-bold text-success mt-2 mb-0"><?= number_format($base) ?></h2>
                    <small class="text-muted mt-1"><?= ($total > 0) ? round(($base / $total) * 100, 1) : 0 ?>% DEL TOTAL</small>
                </div>
            </div>
            <div class="col-md-4 col-4">
                <div class="card p-3 border-start border-4 border-info">
                    <span class="text-info fw-bold">PERSONAL EVENTUAL</span>
                    <h2 class="display-6 fw-bold text-info mt-2 mb-0"><?= number_format($eventual) ?></h2>
                    <small class="text-muted mt-1"><?= ($total > 0) ? round(($eventual / $total) * 100, 1) : 0 ?>% DEL TOTAL</small>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6 mx-auto">
                <div class="card p-3 text-center">
                    <h6 class="text-muted fw-bold mb-2">DISTRIBUCIÓN POR TIPO DE CONTRATACIÓN</h6>
                    <div class="grafica-contenedor-circular" style="height: 190px;">
                        <canvas id="graficaContratacion"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BLOQUE 2: Áreas de Adscripción y Puestos (Top 10) -->
    <div class="bloque-impresion">
        <div class="row mb-3 g-3">
            <!-- Área de Adscripción -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-building text-info"></i> TOP 10 ÁREAS DE ADSCRIPCIÓN</span>
                        <span class="badge bg-info text-dark">MÁS NUMEROSAS</span>
                    </div>
                    <div class="card-body">
                        <div class="grafica-contenedor mb-2" style="height: 220px;">
                            <canvas id="graficaAreas"></canvas>
                        </div>
                        <hr class="text-muted">
                        <small class="text-muted fw-bold">LISTADO COMPLETO DE ÁREAS:</small>
                        <div class="table-responsive mt-1" style="max-height: 140px; overflow-y: auto;">
                            <table class="table table-hover table-bordered table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>ÁREA DE ADSCRIPCIÓN</th>
                                        <th class="text-center" style="width: 80px;">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($por_area as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['area_adscripcion'] ?: 'NO ESPECIFICADO') ?></td>
                                        <td class="text-center fw-bold text-dark"><?= number_format($row['total']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Puestos -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-id-badge text-success"></i> TOP 10 PUESTOS</span>
                        <span class="badge bg-success">MÁS FRECUENTES</span>
                    </div>
                    <div class="card-body">
                        <div class="grafica-contenedor mb-2" style="height: 220px;">
                            <canvas id="graficaPuestos"></canvas>
                        </div>
                        <hr class="text-muted">
                        <small class="text-muted fw-bold">LISTADO COMPLETO DE PUESTOS:</small>
                        <div class="table-responsive mt-1" style="max-height: 140px; overflow-y: auto;">
                            <table class="table table-hover table-bordered table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>PUESTO</th>
                                        <th class="text-center" style="width: 80px;">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($por_puesto as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['puesto'] ?: 'NO ESPECIFICADO') ?></td>
                                        <td class="text-center fw-bold text-dark"><?= number_format($row['total']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BLOQUE 3: Altas por Quincena (Estructura Vertical Separada) -->
    <div class="bloque-impresion">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-warning text-dark">
                        <span class="fw-bold"><i class="fas fa-calendar-alt text-dark"></i> REGISTRO DE ALTAS POR QUINCENA</span>
                        <span class="badge bg-dark text-white">HISTÓRICO</span>
                    </div>
                    <div class="card-body">
                        <!-- TABLA ARRIBA EN BLOQUE COMPLETO -->
                        <div class="mb-3">
                            <small class="text-muted fw-bold d-block mb-1">DETALLE DE PERSONAL POR QUINCENA:</small>
                            <div class="table-responsive" style="max-height: 150px; overflow-y: auto;">
                                <table class="table table-hover table-bordered table-sm mb-0 text-center">
                                    <thead>
                                        <tr>
                                            <th>QUINCENA</th>
                                            <?php foreach ($por_quincena as $q): ?>
                                                <th><?= htmlspecialchars($q['quincena']) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-dark">TOTAL ALTAS</td>
                                            <?php foreach ($por_quincena as $q): ?>
                                                <td class="fw-bold text-primary"><?= number_format($q['total']) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <hr class="my-2">

                        <!-- GRÁFICA ABAJO EN ANCHO COMPLETO -->
                        <div>
                            <small class="text-muted fw-bold d-block mb-2">GRÁFICA COMPARATIVA:</small>
                            <div class="grafica-contenedor" style="height: 220px; position: relative;">
                                <canvas id="graficaQuincenas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BLOQUE 4: Auditoría y Bajas -->
    <div class="bloque-impresion">
        <div class="card shadow-sm">
            <div class="card-header py-2">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <span><i class="fas fa-history text-warning"></i> RESUMEN DE BAJAS Y ELIMINACIONES (HISTORIAL AGRUPADO)</span>
                    </div>
                    <div class="col-md-5 text-end d-flex justify-content-end align-items-center gap-2">
                        <span class="badge bg-danger">TOTAL BAJAS: <?= $total_eliminados ?></span>

                        <?php if ($es_administrador): ?>
                            <form method="POST" onsubmit="return confirm('⚠️ ATENCIÓN: ¿Estás seguro de vaciar toda la bitácora de eliminados?');" class="d-inline no-print">
                                <button type="submit" name="vaciar_bitacora" class="btn btn-danger btn-sm py-1 px-2" title="Limpiar tabla de auditoría">
                                    <i class="fas fa-trash-alt"></i> Limpiar Bitácora
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">FECHA DE BAJA</th>
                                <th class="text-center">TIPO DE CONTRATACIÓN</th>
                                <th class="text-center">TOTAL DE BAJAS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($eliminados) > 0): ?>
                                <?php foreach ($eliminados as $del): ?>
                                <tr>
                                    <td class="text-center fw-bold text-muted"><?= htmlspecialchars($del['fecha_baja']) ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= ($del['tipo_contratacion'] === 'BASE') ? 'bg-success' : 'bg-info text-dark' ?>">
                                            <?= htmlspecialchars($del['tipo_contratacion'] ?: 'NO ESPECIFICADO') ?>
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold text-danger"><?= number_format($del['total_bajas']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">NO EXISTEN REGISTROS DE BAJAS EN LA BITÁCORA</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts de Gráficos -->
<script>
    Chart.register(ChartDataLabels);

    const ctxContratacion = document.getElementById('graficaContratacion').getContext('2d');
    new Chart(ctxContratacion, {
        type: 'doughnut',
        data: {
            labels: ['BASE', 'EVENTUAL'],
            datasets: [{
                data: [<?= $base ?>, <?= $eventual ?>],
                backgroundColor: ['#198754', '#0dcaf0'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { font: { size: 10, weight: 'bold' } } },
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold', size: 11 },
                    formatter: (value) => value > 0 ? value : ''
                }
            }
        }
    });

    const ctxAreas = document.getElementById('graficaAreas').getContext('2d');
    new Chart(ctxAreas, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($top_areas, 'area_adscripcion')) ?>,
            datasets: [{
                label: 'Personal',
                data: <?= json_encode(array_column($top_areas, 'total')) ?>,
                backgroundColor: '#0dcaf0',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: { anchor: 'end', align: 'right', color: '#333', font: { weight: 'bold', size: 10 } }
            },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { display: false }, grace: '15%' },
                y: { ticks: { font: { size: 9, weight: 'bold' } }, grid: { display: false } }
            }
        }
    });

    const ctxPuestos = document.getElementById('graficaPuestos').getContext('2d');
    new Chart(ctxPuestos, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($top_puestos, 'puesto')) ?>,
            datasets: [{
                label: 'Personal',
                data: <?= json_encode(array_column($top_puestos, 'total')) ?>,
                backgroundColor: '#198754',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: { anchor: 'end', align: 'right', color: '#333', font: { weight: 'bold', size: 10 } }
            },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { display: false }, grace: '15%' },
                y: { ticks: { font: { size: 9, weight: 'bold' } }, grid: { display: false } }
            }
        }
    });

    const ctxQuincenas = document.getElementById('graficaQuincenas').getContext('2d');
    new Chart(ctxQuincenas, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($por_quincena, 'quincena')) ?>,
            datasets: [{
                label: 'Altas por Quincena',
                data: <?= json_encode(array_column($por_quincena, 'total')) ?>,
                backgroundColor: '#ffc107',
                borderColor: '#d39e00',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: { anchor: 'end', align: 'top', color: '#212529', font: { weight: 'bold', size: 10 } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 9, weight: 'bold' } } },
                y: { 
                    type: 'logarithmic',
                    beginAtZero: false, 
                    grid: { display: false }, 
                    ticks: { font: { size: 9 } } 
                }
            }
        }
    });
</script>

</body>
</html>
