<?php
session_start();
require_once('../../config/conexion.php');

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['nivel'] ?? '') !== 'ADMIN_GENERAL') {
    header("Location: ../../index.php?error=acceso_denegado");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $nivel = $_POST['nivel']; // CAPTURISTA, LECTURA o ADMIN_GENERAL

    // Lógica de módulos según el rol seleccionado
    if ($nivel === 'ADMIN_GENERAL') {
        $modulo_acceso = 'todos';
    } elseif (isset($_POST['modulos']) && is_array($_POST['modulos'])) {
        $modulo_acceso = implode(',', $_POST['modulos']); // Ej. siniestros,personal
    } else {
        $modulo_acceso = 'siniestros'; 
    }

    if (empty($nombre) || empty($usuario) || empty($password)) {
        $error = "Todos los campos obligatorios deben llenarse.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        if ($stmt->rowCount() > 0) {
            $error = "El nombre de usuario ya está registrado.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, usuario, password, nivel, modulo_acceso) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $usuario, $password_hash, $nivel, $modulo_acceso])) {
                header("Location: index.php?msg=usuario_creado");
                exit();
            } else {
                $error = "Error al registrar el usuario.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Usuario - SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --ssc-red: #8b0000; }
        .btn-ssc { background: var(--ssc-red); color: white; border: none; }
        .btn-ssc:hover { background: #660000; color: white; }
    </style>
</head>
<body class="bg-light p-4">
<div class="container" style="max-width: 600px;">
    <div class="card shadow border-0">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Usuario</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre de Usuario (Login)</label>
                    <input type="text" name="usuario" class="form-control" required value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nivel / Rol de Usuario</label>
                    <select name="nivel" id="nivel" class="form-select" required onchange="toggleModulos()">
                        <option value="CAPTURISTA">Capturista (Escritura en los módulos seleccionados)</option>
                        <option value="LECTURA">Visor / Lectura (Solo lectura en los módulos seleccionados)</option>
                        <option value="ADMIN_GENERAL">Administrador General (Control total)</option>
                    </select>
                </div>

                <div class="mb-3" id="seccion_modulos">
                    <label class="form-label fw-bold text-danger"><i class="bi bi-check2-square"></i> Módulos Permitidos (Puedes marcar 1 o varios)</label>
                    <div class="p-3 border rounded bg-white shadow-sm">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="modulos[]" value="siniestros" id="mod_siniestros">
                            <label class="form-check-label fw-semibold" for="mod_siniestros">Siniestros</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="modulos[]" value="armamento" id="mod_armamento">
                            <label class="form-check-label fw-semibold" for="mod_armamento">Armamento Semovientes y Radios</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="modulos[]" value="personal" id="mod_personal">
                            <label class="form-check-label fw-semibold" for="mod_personal">Personal</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="modulos[]" value="radios" id="mod_radios">
                            <label class="form-check-label fw-semibold" for="mod_radios">Radios (Legacy)</label>
                        </div>
                    </div>
                    <small class="text-muted">El usuario podrá ver y/o capturar en todos los módulos que marque aquí.</small>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-ssc">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleModulos() {
    let nivel = document.getElementById('nivel').value;
    let seccion = document.getElementById('seccion_modulos');
    if (nivel === 'ADMIN_GENERAL') {
        seccion.style.display = 'none';
    } else {
        seccion.style.display = 'block';
    }
}
</script>
</body>
</html>
