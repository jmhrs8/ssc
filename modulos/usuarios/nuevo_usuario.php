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

    if ($nivel === 'ADMIN_GENERAL') {
        $modulo_acceso = 'todos';
    } elseif (isset($_POST['modulos_seleccionados']) && !empty($_POST['modulos_seleccionados'])) {
        // Recibimos la cadena limpia desde el campo oculto (ej. "siniestros,armamento,personal,siniestros_personal")
        $modulo_acceso = $_POST['modulos_seleccionados'];
    } else {
        // Valor por defecto por seguridad si no marca nada (para capturista/visor)
        $modulo_acceso = 'siniestros';
    }

    if (empty($nombre) || empty($usuario) || empty($password)) {
        $error = "Todos los campos obligatorios (Nombre, Usuario, Contraseña) deben llenarse.";
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
                $error = "Error al registrar el usuario en la base de datos.";
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
        /* Estilo para los botones de módulos (los hace parecer seleccionados) */
        .btn-check:checked + .btn.btn-outline-dark {
            background-color: #212529;
            color: white;
            border-color: #212529;
        }
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

            <form action="" method="POST" id="formUsuario">
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

                <!-- Nivel / Rol de Usuario -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Nivel / Rol de Usuario</label>
                    <select name="nivel" id="nivel" class="form-select" required onchange="toggleModulos()">
                        <option value="ADMIN_GENERAL" <?php echo (isset($_POST['nivel']) && $_POST['nivel'] == 'ADMIN_GENERAL') ? 'selected' : ''; ?>>Administrador General (Control total)</option>
                        <option value="CAPTURISTA" <?php echo (isset($_POST['nivel']) && $_POST['nivel'] == 'CAPTURISTA') ? 'selected' : ''; ?>>Capturista (Escritura en módulos seleccionados)</option>
                        <option value="LECTURA" <?php echo (isset($_POST['nivel']) && $_POST['nivel'] == 'LECTURA') ? 'selected' : ''; ?>>Visor / Lectura (Solo lectura en módulos seleccionados)</option>
                    </select>
                </div>

                <!-- Módulos Asignados con Botones Interactivos -->
                <div class="mb-3" id="seccion_modulos" style="<?php echo (isset($_POST['nivel']) && $_POST['nivel'] == 'ADMIN_GENERAL') ? 'display:none;' : ''; ?>">
                    <label class="form-label fw-bold text-danger"><i class="bi bi-check2-square"></i> Módulos Asignados (Haz clic para seleccionar)</label>
                    <div class="p-3 border rounded bg-white shadow-sm d-flex flex-wrap gap-2">

                        <!-- Botón Siniestros -->
                        <input type="checkbox" class="btn-check mod-check" value="siniestros" id="btn_mod_siniestros" autocomplete="off" <?php echo (isset($_POST['modulos_seleccionados']) && strpos($_POST['modulos_seleccionados'], 'siniestros') !== false) ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-dark fw-semibold" for="btn_mod_siniestros"><i class="bi bi-shield-exclamation"></i> Siniestros</label>

                        <!-- Botón Armamento -->
                        <input type="checkbox" class="btn-check mod-check" value="armamento" id="btn_mod_armamento" autocomplete="off" <?php echo (isset($_POST['modulos_seleccionados']) && strpos($_POST['modulos_seleccionados'], 'armamento') !== false) ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-dark fw-semibold" for="btn_mod_armamento"><i class="bi bi-shield-fill"></i> Armamento</label>

                        <!-- Botón Personal -->
                        <input type="checkbox" class="btn-check mod-check" value="personal" id="btn_mod_personal" autocomplete="off" <?php echo (isset($_POST['modulos_seleccionados']) && strpos($_POST['modulos_seleccionados'], 'personal') !== false) ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-dark fw-semibold" for="btn_mod_personal"><i class="bi bi-people-fill"></i> Personal</label>

                        <!-- NUEVO BOTÓN: Personal Siniestrado -->
                        <input type="checkbox" class="btn-check mod-check" value="siniestros_personal" id="btn_mod_siniestros_personal" autocomplete="off" <?php echo (isset($_POST['modulos_seleccionados']) && strpos($_POST['modulos_seleccionados'], 'siniestros_personal') !== false) ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-dark fw-semibold" for="btn_mod_siniestros_personal"><i class="bi bi-person-exclamation"></i> Personal Siniestrado</label>

                    </div>
                    <!-- CAMPO OCULTO CLAVE: Aquí se guardará la lista de módulos separados por coma -->
                    <input type="hidden" name="modulos_seleccionados" id="modulos_seleccionados" value="<?php echo htmlspecialchars($_POST['modulos_seleccionados'] ?? ''); ?>">
                    <small class="text-muted d-mt-2">El usuario tendrá acceso a todos los módulos que marque aquí.</small>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-ssc" id="btnGuardar">Guardar Usuario</button>
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

document.getElementById('formUsuario').addEventListener('submit', function(event) {
    let nivel = document.getElementById('nivel').value;

    if (nivel !== 'ADMIN_GENERAL') {
        let checkboxes = document.querySelectorAll('.mod-check:checked');
        let valoresSeleccionados = [];
        checkboxes.forEach((cb) => {
            valoresSeleccionados.push(cb.value);
        });

        document.getElementById('modulos_seleccionados').value = valoresSeleccionados.join(',');
    }
});

toggleModulos();
</script>
</body>
</html>
