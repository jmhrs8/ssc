<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Conexión a la base de datos
$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 1. Estadísticas Generales (Totales)
    $stmt = $pdo->query("SELECT
        SUM(CASE WHEN pdf_conclusion IS NOT NULL AND pdf_conclusion != '' THEN 1 ELSE 0 END) as concluidos,
        SUM(CASE WHEN pdf_conclusion IS NULL OR pdf_conclusion = '' THEN 1 ELSE 0 END) as pendientes
        FROM control_gestion");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_concluidos = $stats['concluidos'] ?? 0;
    $total_pendientes = $stats['pendientes'] ?? 0;

    // 2. Carga de trabajo y estatus por Personal Asignado (Atención)
    $personal_stats = $pdo->query("SELECT
            u.nombre_completo as empleado,
            SUM(CASE WHEN cg.pdf_conclusion IS NOT NULL AND cg.pdf_conclusion != '' THEN 1 ELSE 0 END) as concluidos,
            SUM(CASE WHEN cg.pdf_conclusion IS NULL OR cg.pdf_conclusion = '' THEN 1 ELSE 0 END) as pendientes,
            COUNT(cg.id_registro) as total
        FROM usuarios u
        LEFT JOIN control_gestion cg ON u.id_usuario = cg.id_usuario_asignado
        GROUP BY u.id_usuario, u.nombre_completo
        ORDER BY total DESC, u.nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Detalle de la lista general de pendientes
    $pendientes_lista = $pdo->query("SELECT cg.id_registro, cg.numero_oficio, u.nombre_completo as asignado
        FROM control_gestion cg
        LEFT JOIN usuarios u ON cg.id_usuario_asignado = u.id_usuario
        WHERE cg.pdf_conclusion IS NULL OR cg.pdf_conclusion = ''
        ORDER BY cg.id_registro DESC")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Folios capturados por día (Usa 'creado_en')
    $capturas_diarias = $pdo->query("SELECT 
            DATE(creado_en) as fecha, 
            COUNT(id_registro) as total_capturados
        FROM control_gestion
        WHERE creado_en IS NOT NULL
        GROUP BY DATE(creado_en)
        ORDER BY fecha ASC
        LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);

    // 5. NUEVA CONSULTA: RATING / RANKING DE QUIÉN CAPTURÓ MÁS (Basado en creado_por)
    $rating_captura = $pdo->query("SELECT 
            u.id_usuario,
            u.nombre_completo,
            u.username,
            u.rol,
            u.foto_perfil,
            COUNT(cg.id_registro) as total_capturados
        FROM usuarios u
        INNER JOIN control_gestion cg ON u.id_usuario = cg.creado_por
        GROUP BY u.id_usuario, u.nombre_completo, u.username, u.rol, u.foto_perfil
        ORDER BY total_capturados DESC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en el reporte: " . $e->getMessage());
}

// Mapear arrays para Chart.js - Rendimiento por Personal
$lista_empleados = [];
$lista_concluidos = [];
$lista_pendientes = [];

foreach ($personal_stats as $p) {
    $lista_empleados[] = $p['empleado'];
    $lista_concluidos[] = (int)$p['concluidos'];
    $lista_pendientes[] = (int)$p['pendientes'];
}

// Mapear arrays para Chart.js - Capturas Diarias
$fechas_diarias = [];
$totales_diarios = [];

foreach ($capturas_diarias as $cd) {
    $fechas_diarias[] = date('d/m/Y', strtotime($cd['fecha']));
    $totales_diarios[] = (int)$cd['total_capturados'];
}

// Mapear arrays para Chart.js - Rating de Captura
$rating_nombres = [];
$rating_totales = [];

foreach ($rating_captura as $rc) {
    $rating_nombres[] = $rc['nombre_completo'];
    $rating_totales[] = (int)$rc['total_capturados'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Ejecutivo por Personal | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js + Plugin de DataLabels -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .navbar-ssc { background-color: #861532; color: white; }
        .card-metric { border-radius: 8px; border: none; }
        .table-responsive { max-height: 400px; overflow-y: auto; }
        .avatar-rating { width: 32px; height: 32px; object-fit: cover; border-radius: 50%; border: 2px solid #861532; }
    </style>
</head>
<body>

<nav class="navbar navbar-ssc shadow-sm mb-4">
    <div class="container-fluid py-1 px-4">
        <span class="navbar-brand text-white fw-bold fs-5"><i class="fa-solid fa-chart-pie me-2 text-warning"></i>SSC | Panel de Productividad</span>
        <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Regresar al Sistema</a>
    </div>
</nav>

<div class="container-fluid px-4 pb-5">

    <!-- Bloque de Botones de Impresión -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark m-0">📊 Monitoreo y Rendimiento de Personal</h3>
        <a href="reporte_imprimible.php" target="_blank" class="btn btn-dark bg-gradient shadow-sm">
            <i class="fa-solid fa-print me-2 text-warning"></i>Generar Impresión (PDF)
        </a>
    </div>

    <!-- Primera Fila: Rating de Capturistas (Nuevo) -->
    <div class="row g-3 mb-4">
        <!-- Tabla Rating Top Captura -->
        <div class="col-xl-5 col-md-6">
            <div class="card p-3 shadow-sm h-100 bg-white card-metric">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-trophy me-2 text-warning"></i>Rating: Mayor Número de Capturas</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.85rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 40px;">#</th>
                                <th>Usuario Capturista</th>
                                <th class="text-center">Rol</th>
                                <th class="text-end">Total Capturados</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $posicion = 1;
                            foreach($rating_captura as $rc): 
                                $foto = (!empty($rc['foto_perfil'])) ? 'uploads/perfiles/' . $rc['foto_perfil'] : 'https://via.placeholder.com/32?text=U';
                                $medalla = '';
                                if ($posicion === 1) $medalla = '🥇 ';
                                elseif ($posicion === 2) $medalla = '🥈 ';
                                elseif ($posicion === 3) $medalla = '🥉 ';
                            ?>
                            <tr>
                                <td class="text-center fw-bold fs-6"><?php echo $medalla . $posicion; ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo $foto; ?>" class="avatar-rating" alt="Avatar">
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($rc['nombre_completo']); ?></div>
                                            <small class="text-muted">@<?php echo htmlspecialchars($rc['username']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center"><span class="badge bg-secondary" style="font-size:0.7rem;"><?php echo $rc['rol']; ?></span></td>
                                <td class="text-end fw-bold fs-6 text-primary"><?php echo number_format($rc['total_capturados']); ?></td>
                            </tr>
                            <?php 
                            $posicion++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gráfica Rating de Captura -->
        <div class="col-xl-7 col-md-6">
            <div class="card p-3 shadow-sm h-100 bg-white card-metric">
                <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-chart-column me-2 text-info"></i>Comparativa Visual de Captura</h6>
                <div style="position: relative; height: 300px;">
                    <canvas id="graficaRating"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Segunda Fila: Gráficas Generales y por Personal -->
    <div class="row g-3 mb-4">
        <!-- Gráfica General -->
        <div class="col-xl-4 col-md-5">
            <div class="card p-3 shadow-sm h-100 bg-white card-metric">
                <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-shield-halved me-1"></i>Estado General de Folios</h6>
                <div style="position: relative; height: 320px;">
                    <canvas id="graficaGeneral"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfica de Rendimiento por Personal (Atención) -->
        <div class="col-xl-8 col-md-7">
            <div class="card p-3 shadow-sm h-100 bg-white card-metric">
                <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-users-gear me-1"></i>Carga de Trabajo / Atención por Servidor Público</h6>
                <div style="position: relative; height: 320px;">
                    <canvas id="graficaPersonal"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tercera Fila: Capturas por Día -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card p-3 shadow-sm bg-white card-metric">
                <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-calendar-day me-2 text-primary"></i>Histórico de Folios Capturados por Día</h6>
                <div style="position: relative; height: 280px;">
                    <canvas id="graficaDiaria"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Cuarta Fila: Tablas Detalladas -->
    <div class="row g-3">
        <!-- Tabla de Rendimiento General por Empleado -->
        <div class="col-xl-6">
            <div class="card p-3 shadow-sm bg-white card-metric">
                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-list-check me-2 text-success"></i>Productividad por Integrante (Atención)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mt-2" style="font-size:0.85rem;">
                        <thead class="table-dark">
                            <tr>
                                <th>Servidor Público Asignado</th>
                                <th class="text-center">Concluidos</th>
                                <th class="text-center">Pendientes</th>
                                <th class="text-center">Total Asignados</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($personal_stats as $p): ?>
                            <tr>
                                <td class="fw-bold text-secondary"><?php echo htmlspecialchars($p['empleado']); ?></td>
                                <td class="text-center"><span class="badge bg-success px-2 py-1"><?php echo $p['concluidos']; ?></span></td>
                                <td class="text-center"><span class="badge bg-warning text-dark px-2 py-1"><?php echo $p['pendientes']; ?></span></td>
                                <td class="text-center fw-bold text-dark"><?php echo $p['total']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabla de Folios Pendientes de Acción con Responsable -->
        <div class="col-xl-6">
            <div class="card p-3 shadow-sm bg-white card-metric">
                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-clock-history me-2 text-danger"></i>Ubicación de Folios Pendientes</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mt-2" style="font-size:0.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Número de Oficio</th>
                                <th>Responsable</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pendientes_lista)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No hay folios pendientes actualmente 🎉</td></tr>
                            <?php endif; ?>
                            <?php foreach($pendientes_lista as $r): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($r['numero_oficio']); ?></td>
                                <td class="text-muted"><i class="fa-solid fa-user-tag me-1 text-secondary" style="font-size:0.75rem;"></i><?php echo htmlspecialchars($r['asignado'] ?? 'SIN ASIGNAR'); ?></td>
                                <td class="text-center">
                                    <a href="index.php?id=<?php echo $r['id_registro']; ?>" class="btn btn-sm btn-outline-primary py-0 px-2 fw-bold" style="font-size:0.75rem;">Atender</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    // Registrar plugin de etiquetas globales en Chart.js
    Chart.register(ChartDataLabels);

    // 1. Gráfica Rating de Captura
    const ctxRating = document.getElementById('graficaRating').getContext('2d');
    new Chart(ctxRating, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($rating_nombres); ?>,
            datasets: [{
                label: 'Total Folios Capturados',
                data: <?php echo json_encode($rating_totales); ?>,
                backgroundColor: '#861532',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 20 } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false },
                datalabels: {
                    align: 'top',
                    anchor: 'end',
                    color: '#861532',
                    font: { weight: 'bold', size: 11 },
                    formatter: (val) => val
                }
            }
        }
    });

    // 2. Configuración Gráfica de Estatus General
    const ctxGen = document.getElementById('graficaGeneral').getContext('2d');
    new Chart(ctxGen, {
        type: 'doughnut',
        data: {
            labels: ['Concluidos', 'Pendientes'],
            datasets: [{
                data: [<?php echo $total_concluidos; ?>, <?php echo $total_pendientes; ?>],
                backgroundColor: ['#198754', '#ffc107'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold' },
                    formatter: (val) => val > 0 ? val : ''
                }
            }
        }
    });

    // 3. Configuración Gráfica Barras Apiladas por Personal
    const ctxPers = document.getElementById('graficaPersonal').getContext('2d');
    new Chart(ctxPers, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($lista_empleados); ?>,
            datasets: [
                {
                    label: 'Concluidos',
                    data: <?php echo json_encode($lista_concluidos); ?>,
                    backgroundColor: '#198754'
                },
                {
                    label: 'Pendientes',
                    data: <?php echo json_encode($lista_pendientes); ?>,
                    backgroundColor: '#ffc107'
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true, beginAtZero: true },
                y: { stacked: true }
            },
            plugins: {
                legend: { position: 'top' },
                datalabels: {
                    color: '#000',
                    font: { weight: 'bold', size: 10 },
                    formatter: (val) => val > 0 ? val : ''
                }
            }
        }
    });

    // 4. Configuración Gráfica de Capturas Diarias
    const ctxDiaria = document.getElementById('graficaDiaria').getContext('2d');
    new Chart(ctxDiaria, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($fechas_diarias); ?>,
            datasets: [{
                label: 'Folios Capturados',
                data: <?php echo json_encode($totales_diarios); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.15)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointBackgroundColor: '#861532'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 20 } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: true, position: 'top' },
                datalabels: {
                    align: 'top',
                    anchor: 'end',
                    backgroundColor: '#861532',
                    borderRadius: 4,
                    color: '#fff',
                    font: { weight: 'bold', size: 11 },
                    padding: 4,
                    formatter: (val) => val
                }
            }
        }
    });
</script>
</body>
</html>
