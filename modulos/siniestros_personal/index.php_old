<?php
session_start();
require_once "../../config/conexion.php";

// Validación de seguridad
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$nivel_actual = strtoupper(trim($_SESSION['nivel'] ?? ''));
$permiso_siniestros = $_SESSION['permiso_siniestros'] ?? 0;
$usuario_sesion = $_SESSION['nombre'] ?? ($_SESSION['usuario'] ?? 'INVITADO');

// Control de Permisos por Rol: ADMIN_GENERAL, ADMINISTRADOR, CAPTURISTA, LECTURA
if (!$nivel_actual || ($nivel_actual !== 'ADMIN_GENERAL' && $nivel_actual !== 'ADMINISTRADOR' && $nivel_actual !== 'CAPTURISTA' && $nivel_actual !== 'LECTURA' && $permiso_siniestros != 1)) {
    header("Location: ../../index.php?error=sin_permiso");
    exit();
}

$es_admin_general = ($nivel_actual === 'ADMIN_GENERAL');
$es_administrador = ($es_admin_general || $nivel_actual === 'ADMINISTRADOR');
$es_lectura = ($nivel_actual === 'LECTURA');

$search = $_GET['buscar'] ?? '';

try {
    $term = "%$search%";
    // Contar total de registros
    $count_sql = "SELECT COUNT(*) FROM siniestros_personal WHERE no_folio LIKE ? OR nombre LIKE ? OR rfc LIKE ? OR no_empleado LIKE ?";
    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute([$term, $term, $term, $term]);
    $total_registros = $stmt_count->fetchColumn();

    // Consulta para traer TODOS los campos de la tabla
    $query = "SELECT * FROM siniestros_personal WHERE
              no_folio LIKE ? OR
              nombre LIKE ? OR
              rfc LIKE ? OR
              no_empleado LIKE ? OR
              aseguradora LIKE ?
              ORDER BY id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$term, $term, $term, $term, $term]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("ERROR DE SISTEMA: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PERSONAL SINIESTRADO | SSC SYSTEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-size: 11px; background-color: #f4f6f9; text-transform: uppercase; }
        .header-top { background: #1a1a1a; color: white; padding: 12px; }
        .table-container { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-responsive { overflow-x: auto; max-height: 70vh; }
        .table { min-width: 2400px; }
        .table th { background: #212529 !important; color: white !important; text-align: center; vertical-align: middle; white-space: nowrap; position: sticky; top: 0; z-index: 10; }
        .table td { white-space: nowrap; vertical-align: middle; border: 1px solid #dee2e6; }
        .img-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div class="header-top shadow-sm mb-3">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-user-injured"></i> CONTROL DE PERSONAL SINIESTRADO</h5>
            <small class="text-warning">TOTAL REGISTROS: <?= $total_registros ?></small>
            <small class="ms-3 text-muted">USUARIO: <?= strtoupper(htmlspecialchars($usuario_sesion)) ?> [ROL: <?= $nivel_actual ?>]</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (!$es_lectura): ?>
                <a href="subir_excel.php" class="btn btn-info btn-sm text-dark fw-bold" title="Cargar y Procesar archivo Excel"><i class="fas fa-file-excel"></i> SUBIR / PROCESAR EXCEL</a>
            <?php endif; ?>
            <a href="acciones.php?accion=exportar_excel" class="btn btn-success btn-sm fw-bold"><i class="fas fa-file-download"></i> DESCARGAR EXCEL</a>
            <a href="reporte_pdf_final.php" class="btn btn-warning btn-sm text-dark fw-bold" target="_blank"><i class="fas fa-chart-bar"></i> REPORTES</a>
            <?php if (!$es_lectura): ?>
                <a href="formulario.php" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> NUEVO SINIESTRO</a>
            <?php endif; ?>
            <?php if ($es_admin_general): ?>
                <a href="acciones.php?accion=limpiar_base" class="btn btn-danger btn-sm fw-bold" onclick="return confirm('¡ADVERTENCIA CRÍTICA! ¿Está seguro de ELIMINAR TODOS LOS REGISTROS de la base de datos? Esta acción vaciará completamente la tabla.');"><i class="fas fa-trash-alt"></i> VACIAR BASE DE DATOS</a>
            <?php endif; ?>
            <a href="../../index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-home"></i> INICIO</a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Mensajes de Estado -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Buscador -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body py-2">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-10">
                    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar por Folio, Nombre, RFC, No. Empleado o Aseguradora..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i> Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Resultados -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>ID</th>
                        <th>No. Folio</th>
                        <th>Tipo</th>
                        <th>Mes de Reporte</th>
                        <th>No. Empleado</th>
                        <th>Edad</th>
                        <th>RFC</th>
                        <th>Nombre del Elemento</th>
                        <th>Fecha de Siniestro</th>
                        <th>Reporte</th>
                        <th>Póliza / Sección</th>
                        <th>Aseguradora</th>
                        <th>Causa Resumido</th>
                        <th>Unidad Vehicular</th>
                        <th>Lesiones</th>
                        <th>Área de Adscripción</th>
                        <th>Hospital</th>
                        <th>Requirió Hospitalización</th>
                        <th>Observaciones</th>
                        <th>Montos Erogados</th>
                        <th>Evidencias (Fotos)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($resultados) > 0): ?>
                        <?php foreach ($resultados as $row): ?>
                            <tr>
                                <td class="text-center">
                                    <!-- Botón Formato Oficial / PDF -->
                                    <a href="generar_formato_oficial.php?id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-xs" target="_blank" title="Imprimir Formato Oficial">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <?php if (!$es_lectura): ?>
                                        <a href="formulario.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-xs text-dark" title="Editar"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                    <?php if ($es_admin_general): ?>
                                        <a href="acciones.php?accion=eliminar&id=<?= $row['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('¿Está seguro de eliminar este registro?');" title="Eliminar"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $row['id'] ?></td>
                                <td><b><?= htmlspecialchars($row['no_folio']) ?></b></td>
                                <td class="text-center"><?= htmlspecialchars($row['tipo']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['mes_de_reporte']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['no_empleado']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['edad']) ?></td>
                                <td><?= htmlspecialchars($row['rfc']) ?></td>
                                <td><b><?= htmlspecialchars($row['nombre']) ?></b></td>
                                <td class="text-center"><?= htmlspecialchars($row['fecha_de_siniestro']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['reporte']) ?></td>
                                <td><?= htmlspecialchars($row['poliza_seccion']) ?></td>
                                <td><?= htmlspecialchars($row['aseguradora']) ?></td>
                                <td><?= htmlspecialchars($row['causa_resumido']) ?></td>
                                <td><?= htmlspecialchars($row['unidad_vehicular']) ?></td>
                                <td><?= htmlspecialchars($row['lesiones']) ?></td>
                                <td><?= htmlspecialchars($row['area_adscripcion']) ?></td>
                                <td><?= htmlspecialchars($row['hospital']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= ($row['requirio_hospitalizacion'] == 'SI') ? 'bg-danger' : 'bg-secondary' ?>">
                                        <?= htmlspecialchars($row['requirio_hospitalizacion']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['observaciones']) ?></td>
                                <td class="text-end">$<?= number_format($row['montos_erogados'], 2) ?></td>
                                <td class="text-center">
                                    <?php
                                    if (!empty($row['foto'])) {
                                        $fotos = json_decode($row['foto'], true);
                                        if (is_array($fotos)) {
                                            foreach ($fotos as $f) {
                                                echo '<a href="../../' . htmlspecialchars($f) . '" target="_blank"><img src="../../' . htmlspecialchars($f) . '" class="img-thumb me-1"></a>';
                                            }
                                        } else {
                                            echo '<a href="../../' . htmlspecialchars($row['foto']) . '" target="_blank"><img src="../../' . htmlspecialchars($row['foto']) . '" class="img-thumb"></a>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">Sin foto</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="22" class="text-center py-4 text-muted">No se encontraron registros de personal siniestrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
