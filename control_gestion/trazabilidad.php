<?php
// trazabilidad.php
session_start();
if (!isset($_SESSION['usuario'])) {
    die("Acceso no autorizado.");
}

$id_registro = $_GET['id'] ?? 0;

$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 1. Obtener datos del Oficio y relacionar creado_por con usuarios.id_usuario
    $stmt_oficio = $pdo->prepare("SELECT cg.*, ca.nombre_area, uc.nombre_completo AS capturista 
                                  FROM control_gestion cg 
                                  LEFT JOIN catalogo_areas ca ON cg.id_turnado_por = ca.id_area 
                                  LEFT JOIN usuarios uc ON cg.creado_por = uc.id_usuario 
                                  WHERE cg.id_registro = ?");
    $stmt_oficio->execute([$id_registro]);
    $oficio = $stmt_oficio->fetch(PDO::FETCH_ASSOC);

    if (!$oficio) {
        die("Folio de oficio no encontrado.");
    }

    $nombre_capturista = !empty($oficio['capturista']) ? $oficio['capturista'] : 'Sin registro de captura';

    // 2. Obtener Historial de Asignaciones
    $stmt_hist = $pdo->prepare("SELECT h.*, u.nombre_completo, u.foto_perfil, u.rol
                                FROM historial_asignaciones h
                                INNER JOIN usuarios u ON h.id_usuario = u.id_usuario
                                WHERE h.id_registro = ?
                                ORDER BY h.fecha_inicio ASC");
    $stmt_hist->execute([$id_registro]);
    $historial = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error al cargar la trazabilidad: " . $e->getMessage());
}

function calcularTiempo($inicio, $fin) {
    $fecha1 = new DateTime($inicio);
    $fecha2 = $fin ? new DateTime($fin) : new DateTime();
    $diferencia = $fecha1->diff($fecha2);

    $partes = [];
    if ($diferencia->d > 0) $partes[] = $diferencia->d . ' día(s)';
    if ($diferencia->h > 0) $partes[] = $diferencia->h . ' hr(s)';
    if ($diferencia->i > 0) $partes[] = $diferencia->i . ' min(s)';

    return empty($partes) ? 'Menos de 1 min' : implode(', ', $partes);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Trazabilidad del Folio #<?php echo $oficio['id_registro']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 0.85rem; }
        .header-bg { background-color: #861532; color: #fff; padding: 1.2rem; border-radius: 8px 8px 0 0; }
        .timeline { position: relative; padding: 20px 0; list-style: none; }
        .timeline:before { content: ''; position: absolute; top: 0; bottom: 0; left: 40px; width: 4px; background: #861532; }
        .timeline-item { position: relative; margin-bottom: 25px; padding-left: 70px; }
        .timeline-img { position: absolute; left: 15px; top: 0; width: 52px; height: 52px; object-fit: cover; border-radius: 50%; border: 3px solid #861532; background-color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .timeline-card { background: #fff; border-radius: 6px; padding: 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); border-left: 4px solid #861532; }
        .badge-status { font-size: 0.75rem; padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body class="p-3">

<div class="container bg-white shadow-sm rounded-3 p-0 mb-4">
    <div class="header-bg d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-route me-2"></i>LÍNEA DE TIEMPO Y TRAZABILIDAD DE ATENCIÓN</h5>
            <small class="opacity-75">Oficio: <?php echo htmlspecialchars($oficio['numero_oficio']); ?> | Folio Interno: #<?php echo $oficio['id_registro']; ?></small>
        </div>
        <button class="btn btn-sm btn-light fw-bold" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Imprimir</button>
    </div>

    <div class="p-3 bg-light border-bottom">
        <div class="row g-2">
            <div class="col-md-4"><strong>Origen/Remitente:</strong> <?php echo htmlspecialchars($oficio['titular']); ?> (<?php echo htmlspecialchars($oficio['cargo']); ?>)</div>
            <div class="col-md-4"><strong>Área Turnada:</strong> <?php echo htmlspecialchars($oficio['nombre_area']); ?></div>
            <div class="col-md-4"><strong>Fecha Recepción:</strong> <?php echo $oficio['fecha_recepcion']; ?></div>
            <div class="col-md-12 mt-2 pt-2 border-top">
                <strong>Capturado por:</strong> <span class="badge bg-dark text-white ms-1"><i class="fa-solid fa-user-pen me-1 text-warning"></i> <?php echo htmlspecialchars($nombre_capturista); ?></span>
            </div>
            <div class="col-12 mt-1"><strong>Asunto:</strong> <?php echo htmlspecialchars($oficio['asunto']); ?></div>
        </div>
    </div>

    <div class="p-4">
        <ul class="timeline">
            <?php foreach ($historial as $index => $item):
                $foto = (!empty($item['foto_perfil'])) ? 'uploads/perfiles/' . $item['foto_perfil'] : 'https://via.placeholder.com/50?text=U';
                $tiempoAtencion = calcularTiempo($item['fecha_inicio'], $item['fecha_fin']);
                $esUltimo = ($index === count($historial) - 1);
            ?>
            <li class="timeline-item">
                <img src="<?php echo $foto; ?>" class="timeline-img" alt="Foto">
                <div class="timeline-card">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-dark fs-6"><?php echo htmlspecialchars($item['nombre_completo']); ?></strong>
                        <?php if ($item['estatus_tramite'] == 'CONCLUIDO'): ?>
                            <span class="badge bg-success badge-status"><i class="fa-solid fa-check-double me-1"></i> CONCLUIDO</span>
                        <?php elseif ($esUltimo): ?>
                            <span class="badge bg-warning text-dark badge-status"><i class="fa-solid fa-clock me-1"></i> EN ATENCIÓN ACTUAL</span>
                        <?php else: ?>
                            <span class="badge bg-secondary badge-status"><i class="fa-solid fa-arrow-right-long me-1"></i> REASIGNADO</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small">
                        <i class="fa-solid fa-calendar-check me-1 text-primary"></i> <strong>Recibido:</strong> <?php echo date('d/m/Y H:i', strtotime($item['fecha_inicio'])); ?>
                        <?php if ($item['fecha_fin']): ?>
                            <br><i class="fa-solid fa-calendar-xmark me-1 text-danger"></i> <strong>Turnado/Finalizado:</strong> <?php echo date('d/m/Y H:i', strtotime($item['fecha_fin'])); ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 p-2 bg-light border rounded">
                        <i class="fa-solid fa-hourglass-half me-1 text-warning"></i> <strong>Tiempo con el servidor público:</strong>
                        <span class="fw-bold text-dark"><?php echo $tiempoAtencion; ?></span>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

</body>
</html>
