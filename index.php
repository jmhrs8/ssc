<?php
session_start();
require_once('config/conexion.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT nivel, modulo_acceso FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario_actual = $stmt->fetch();

$es_admin = (strtoupper($usuario_actual['nivel']) === 'ADMIN_GENERAL');
$es_todos = (strtolower($usuario_actual['modulo_acceso']) === 'todos');

$modulos_permitidos = [];
if (!$es_admin && !$es_todos) {
    $modulos_permitidos = explode(',', strtolower($usuario_actual['modulo_acceso']));
}

function moduloActivado($nombreModulo, $isAdmin, $isTodos, $modulosPermitidos) {
    if ($isAdmin || $isTodos) return true;
    return in_array(strtolower($nombreModulo), $modulosPermitidos);
}

$archivo_fondo = "uploads/sistema/fondo_actual.jpg";
$fondo_url = file_exists($archivo_fondo) ? $archivo_fondo . "?v=" . filemtime($archivo_fondo) : "";

$archivo_foto_ing = "uploads/sistema/juan_hernandez.jpg";
$foto_ing_url = file_exists($archivo_foto_ing) ? $archivo_foto_ing . "?v=" . filemtime($archivo_foto_ing) : "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/icons/person-badge.svg";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISTEMA DE ASEGURAMIENTO - SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --ssc-dark: #1a1a1a; --ssc-red: #8b0000; }
        body { margin: 0; padding: 0; min-height: 100vh; font-family: 'Segoe UI', sans-serif; position: relative; display: flex; flex-direction: column; }
        body::before {
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1; background-image: url('<?php echo $fondo_url; ?>');
            background-size: cover; background-position: center; opacity: 0.2;
        }
        .main-content { flex: 1; }
        .navbar-custom { background-color: var(--ssc-dark); border-bottom: 3px solid var(--ssc-red); }
        .card-modulo {
            border: none; border-radius: 12px; background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px); transition: all 0.3s ease;
            text-decoration: none !important; color: #333;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 200px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card-modulo:hover { transform: translateY(-8px); background: #fff; color: var(--ssc-red); box-shadow: 0 12px 25px rgba(0,0,0,0.2); }
        .icon-circle {
            width: 70px; height: 70px; background: var(--ssc-dark); color: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin-bottom: 12px;
        }
        .card-modulo:hover .icon-circle { background: var(--ssc-red); }
        .title-modulo { font-weight: bold; text-transform: uppercase; font-size: 0.95rem; text-align: center; }
        .bg-admin { border-top: 4px solid var(--ssc-red) !important; }

        .footer-creditos {
            background-color: rgba(26, 26, 26, 0.95);
            border-top: 3px solid var(--ssc-red);
            color: #ccc;
            font-size: 0.85rem;
            backdrop-filter: blur(10px);
            margin-top: auto;
        }
        .dev-avatar {
            width: 45px; height: 45px; object-fit: cover; border: 2px solid var(--ssc-red); background: #333;
        }
    </style>
</head>
<body>

<header class="main-header">
    <nav class="navbar navbar-custom navbar-dark p-3">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold">SISTEMA DE ASEGURAMIENTO</span>
            <div class="d-flex align-items-center">
                <span class="text-light me-3 small text-uppercase">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?> (<?php echo strtoupper($usuario_actual['nivel']); ?>)
                </span>
                <a href="logout.php" class="btn btn-danger btn-sm">Salir</a>
            </div>
        </div>
    </nav>
</header>

<div class="container py-5 main-content">
    <?php if(isset($_GET['error']) && $_GET['error'] == 'sin_permiso'): ?>
        <div class="alert alert-danger text-center alert-dismissible fade show" role="alert">
            <strong><i class="bi bi-exclamation-triangle-fill"></i> ACCESO DENEGADO:</strong> No tienes permisos para ingresar a ese módulo.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-5 text-center">
        <div class="col-12">
            <h1 class="fw-bold display-5">PANEL PRINCIPAL</h1>
            <p class="text-muted">Gestión de inventarios y recursos</p>
            <hr class="mx-auto" style="width: 60px; height: 3px; background: var(--ssc-red);">
        </div>
    </div>

    <div class="row g-4 justify-content-center">

        <!-- MÓDULO: SINIESTROS VEHICULARES -->
        <?php if (moduloActivado('siniestros', $es_admin, $es_todos, $modulos_permitidos)): ?>
        <div class="col-6 col-md-3">
            <a href="modulos/siniestros/index.php" class="card card-modulo">
                <div class="icon-circle"><i class="bi bi-car-front"></i></div>
                <span class="title-modulo">Vehículos Siniestrados</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- MÓDULO: ARMAMENTO -->
        <?php if (moduloActivado('armamento', $es_admin, $es_todos, $modulos_permitidos)): ?>
        <div class="col-6 col-md-3">
            <a href="modulos/armas/index.php" class="card card-modulo">
                <div class="icon-circle"><i class="bi bi-shield-shaded"></i></div>
                <span class="title-modulo">Armamento Semovientes y Radios</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- MÓDULO: PERSONAL (PADRÓN) -->
        <?php if (moduloActivado('personal', $es_admin, $es_todos, $modulos_permitidos)): ?>
        <div class="col-6 col-md-3">
            <a href="modulos/personal/index.php" class="card card-modulo">
                <div class="icon-circle"><i class="bi bi-person-vcard"></i></div>
                <span class="title-modulo">Padrón de Personal Asegurado</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- NUEVO MÓDULO: PERSONAL SINIESTRADO -->
        <?php if (moduloActivado('siniestros_personal', $es_admin, $es_todos, $modulos_permitidos)): ?>
        <div class="col-6 col-md-3">
            <a href="modulos/siniestros_personal/index.php" class="card card-modulo">
                <div class="icon-circle"><i class="bi bi-person-exclamation"></i></div>
                <span class="title-modulo">Personal Siniestrado</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- ADMINISTRACIÓN (Solo para ADMIN_GENERAL) -->
        <?php if ($es_admin): ?>
        <div class="col-12 mt-5">
            <h5 class="text-muted text-uppercase mb-3"><i class="bi bi-gear-fill"></i> Administración de Sistema</h5>
        </div>

        <div class="col-6 col-md-4">
            <a href="modulos/usuarios/index.php" class="card card-modulo bg-admin">
                <div class="icon-circle" style="background: #444;"><i class="bi bi-people-fill"></i></div>
                <span class="title-modulo">Gestión de Usuarios</span>
                <small class="text-muted">Agregar y privilegios</small>
            </a>
        </div>

        <div class="col-6 col-md-4">
            <a href="config_visual.php" class="card card-modulo bg-admin">
                <div class="icon-circle" style="background: #444;"><i class="bi bi-palette-fill"></i></div>
                <span class="title-modulo">Configuración Visual</span>
                <small class="text-muted">Fondo y logo</small>
            </a>
        </div>

        <!-- CONFIGURACIÓN DE REPORTES AUTOMÁTICOS POR CORREO (CON ICONO DE MAIL) -->
        <div class="col-6 col-md-4">
            <a href="modulos/reportes_generales/configurar_correo_reportes.php" class="card card-modulo bg-admin">
                <div class="icon-circle" style="background: #444;"><i class="bi bi-envelope-at-fill"></i></div>
                <span class="title-modulo">Reportes por Correo</span>
                <small class="text-muted">Frecuencia y destinatarios</small>
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- FOOTER INSTITUCIONAL (Créditos conservados) -->
<footer class="footer-creditos py-3 mt-auto">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between text-center text-md-start">
        <div class="d-flex align-items-center mb-2 mb-md-0">
            <img src="<?php echo $foto_ing_url; ?>" alt="Ing. Juan Manuel" class="rounded-circle dev-avatar me-3 shadow-sm">
            <div>
                <span class="d-block text-white fw-bold">Diseño: Ing. Juan Manuel Hernández Lugo </span>
                <span class="text-muted small">Diseño & Arquitectura de Sistema</span>
            </div>
        </div>
        <div>
            <a href="mailto:jmhrs8@gmail.com" class="text-decoration-none text-light small btn btn-sm btn-outline-dark border-secondary px-3">
                <i class="bi bi-envelope-at-fill text-danger me-1"></i> jmhrs8@gmail.com
            </a>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
