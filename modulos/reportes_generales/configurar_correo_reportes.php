root@aseguramiento-QCS1250:/var/www/html/modulos/reportes_generales# cat configurar.php
<?php
session_start();
require_once "../../config/conexion.php";

// Validación de seguridad (Solo Admin General)
if (!isset($_SESSION['user_id']) || strtoupper(trim($_SESSION['nivel'] ?? '')) !== 'ADMIN_GENERAL') {
    header("Location: ../../index.php?error=sin_permiso");
    exit();
}

$mensaje = "";
$tipo_alerta = "success";

// Procesar el formulario de guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    $frecuencia = $_POST['frecuencia'];
    $destinatarios = trim($_POST['destinatarios']);
    $activo = isset($_POST['activo']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE configuracion_reportes_correo SET frecuencia = ?, destinatarios = ?, activo = ? WHERE id = 1");
    $stmt->execute([$frecuencia, $destinatarios, $activo]);
    $mensaje = "¡Configuración actualizada correctamente!";
}

// Obtener configuración actual
$stmt = $pdo->query("SELECT * FROM configuracion_reportes_correo WHERE id = 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Si por alguna razón la tabla está vacía, creamos un registro base
if (!$config) {
    $pdo->exec("INSERT INTO configuracion_reportes_correo (id, frecuencia, destinatarios, activo) VALUES (1, 'semanal', '', 0)");
    $stmt = $pdo->query("SELECT * FROM configuracion_reportes_correo WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CONFIGURAR REPORTES AUTOMÁTICOS | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-size: 12px; background-color: #f4f6f9; text-transform: uppercase; }</style>
</head>
<body>
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-envelope-open-text"></i> CONFIGURACIÓN DE ENVÍO AUTOMÁTICO DE REPORTES GENERALES</h5>
            <a href="../../index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-home"></i> INICIO</a>
        </div>
        <div class="card-body">
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?= $tipo_alerta ?> fw-bold"><?= $mensaje ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">ESTADO DEL SERVICIO AUTOMÁTICO</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= ($config['activo'] == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-success" for="activo">ACTIVAR ENVÍOS AUTOMÁTICOS POR CORREO</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">FRECUENCIA DE ENVÍO</label>
                    <select name="frecuencia" class="form-select form-select-sm">
                        <option value="semanal" <?= ($config['frecuencia'] == 'semanal') ? 'selected' : '' ?>>SEMANAL</option>
                        <option value="mensual" <?= ($config['frecuencia'] == 'mensual') ? 'selected' : '' ?>>MENSUAL</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">CORREOS DESTINATARIOS (SEPARADOS POR COMAS)</label>
                    <input type="text" name="destinatarios" class="form-control form-control-sm" value="<?= htmlspecialchars($config['destinatarios']) ?>" placeholder="ejemplo1@ssc.gob.mx, ejemplo2@ssc.gob.mx" required>
                    <small class="text-muted text-lowercase">puedes ingresar dos o más correos separados estrictamente por comas.</small>
                </div>

                <button type="submit" name="guardar_config" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> GUARDAR CONFIGURACIÓN</button>
                <a href="../../index.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> REGRESAR AL PANEL</a>
            </form>

            <hr class="my-4">

            <div class="bg-light p-3 border rounded">
                <h6 class="fw-bold text-dark"><i class="fas fa-paper-plane text-info"></i> ZONA DE PRUEBAS MANUALES</h6>
                <p class="text-muted mb-2">Puedes forzar la ejecución del envío en este momento para verificar que los correos y el reporte PDF se generan y despachan correctamente sin esperar al cron automático.</p>
                <a href="cron_enviar.php?prueba=1" class="btn btn-success btn-sm" target="_blank">
                    <i class="fas fa-bolt"></i> PROBAR ENVÍO DE CORREO AHORA
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
