<?php
session_start();
require_once('../../config/conexion.php');

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['nivel'] ?? '') !== 'ADMIN_GENERAL') {
    header("Location: ../../index.php?error=acceso_denegado");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $login = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $nivel = $_POST['nivel'];

    // Lógica de módulos: Si es Admin General, acceso total. Si no, usa lo seleccionado.
    if ($nivel === 'ADMIN_GENERAL') {
        $modulo_acceso = 'todos';
    } elseif (isset($_POST['modulos_seleccionados']) && !empty($_POST['modulos_seleccionados'])) {
        // Recibimos la cadena limpia desde el campo oculto (ej. "siniestros,armamento,personal,siniestros_personal")
        $modulo_acceso = $_POST['modulos_seleccionados'];
    } else {
        // Valor por defecto por seguridad si no marca nada (para capturista/visor)
        $modulo_acceso = 'siniestros';
    }

    if (empty($nombre) || empty($login)) {
        $error = "El nombre y el usuario son obligatorios.";
    } else {
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, password = ?, nivel = ?, modulo_acceso = ? WHERE id = ?");
            $stmt->execute([$nombre, $login, $password_hash, $nivel, $modulo_acceso, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, nivel = ?, modulo_acceso = ? WHERE id = ?");
            $stmt->execute([$nombre, $login, $nivel, $modulo_acceso, $id]);
        }
        header("Location: index.php?msg=actualizado");
        exit();
    }
}

// Convertir los módulos actuales del usuario en un array para marcar los botones al cargar
$modulos_usuario = explode(',', strtolower($usuario['modulo_acceso']));
$es_admin_general = (strtoupper($usuario['nivel']) === 'ADMIN_GENERAL');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --ssc-gold: #b8860b; }
        .btn-warning { background: var(--ssc-gold); color: white; border: none; }
        .btn-warning:hover { background: #996f08; color: white; }
        /* Estilo para los botones de módulos cuando están activos */
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
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Usuario #<?php echo htmlspecialchars($usuario['id']); ?></h4>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" id="formEditarUsuario">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($usuario['nombre']); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre de Usuario (Login)</label>
                    <input type="text" name="usuario" class="form-control" required value="<?php echo htmlspecialchars($usuario['usuario']); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nueva Contraseña (Dejar en blanco para no cambiar)</label>
                    <input type="password" name="password" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nivel / Rol de Usuario</label>
                    <select name="nivel" id="nivel" class="form-select" required onchange="toggleModulos()">
                        <option value="ADMIN_GENERAL" <?php echo (strtoupper($usuario['nivel']) == 'ADMIN_GENERAL') ? 'selected' : ''; ?>>Administrador General (Control total)</option>
                        <option value="CAPTURISTA" <?php echo (strtoupper($usuario['nivel']) == 'CAPTURISTA') ? 'selected' : ''; ?>>Capturista (Escritura en módulos seleccionados)</option>
                        <option value="LECTURA" <?php echo (strtoupper($usuario['nivel']) == 'LECTURA') ? 'selected' : ''; ?>>Visor / Lectura (Solo lectura en módulos seleccionados)</option>
                    </select>
                </div>

                <div class="mb-3" id="seccion_modulos" style="<?php echo $es_admin_general ? 'display:none;' : ''; ?>">
                    <label class="form-label fw-bold text-danger"><i class="bi bi-check2-square"></i> Módulos Asignados (Haz clic para seleccionar)</label>
                    <div class="p-3 border rounded bg-white shadow-sm d-flex flex-wrap gap-2">

                        <!-- Botón Siniestros -->
                        <input type="checkbox" class="btn-check mod-check" value="siniestros" id="btn_siniestros" autocomplete="off" <?php echo in_array('siniestros', $modulos_usuario) ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-dark fw-semibold" for="btn_siniestros"><i class="bi bi-shield-exclamation"></i> Siniestros</label>

                        <!-- Botón Armamento -->
                        <input type="checkbox" class="btn-check mod-check" value="armamento" id="btn_armamento" autocomplete="off" <?php echo in_array('armamento', $modulos_usuario) ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-dark fw-semibold" for="btn_armamento"><i class="bi bi-shield-fill"></i> Armamento</label>

                        <!-- Botón Personal -->
                        <input type="checkbox" class="btn-check mod-check" value="personal" id="btn_personal" autocomplete="off" <?php echo in_array('personal', $modulos_usuario) ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-dark fw-semibold" for="btn_personal"><i class="bi bi-people-fill"></i> Personal</label>

                        <!-- NUEVO BOTÓN: Personal Siniestrado -->
                        <input type="checkbox" class="btn-check mod-check" value="siniestros_personal" id="btn_siniestros_personal" autocomplete="off" <?php echo in_array('siniestros_personal', $modulos_usuario) ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-dark fw-semibold" for="btn_siniestros_personal"><i class="bi bi-person-exclamation"></i> Personal Siniestrado</label>

                    </div>
                    <!-- CAMPO OCULTO CLAVE: Aquí se guardará la lista de módulos separados por coma -->
                    <input type="hidden" name="modulos_seleccionados" id="modulos_seleccionados" value="<?php echo htmlspecialchars($usuario['modulo_acceso']); ?>">
                    <small class="text-muted d-mt-2">El usuario tendrá acceso a todos los módulos que marque aquí.</small>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">Volver</a>
                    <button type="submit" class="btn btn-warning" id="btnActualizar">Actualizar Datos</button>
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

// CORRECCIÓN CRÍTICA DE JAVASCRIPT
document.getElementById('formEditarUsuario').addEventListener('submit', function(event) {
    let nivel = document.getElementById('nivel').value;

    if (nivel !== 'ADMIN_GENERAL') {
        let checkboxesMarcados = document.querySelectorAll('.mod-check:checked');
        let valoresSeleccionados = [];

        checkboxesMarcados.forEach((cb) => {
            valoresSeleccionados.push(cb.value);
        });

        // Unimos los valores por coma (ej. "siniestros,personal,siniestros_personal")
        document.getElementById('modulos_seleccionados').value = valoresSeleccionados.join(',');
    }
});

// Ejecutar al cargar la página
toggleModulos();
</script>
</body>
</html>
