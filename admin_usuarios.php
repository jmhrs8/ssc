<?php
session_start();

// 1. CONFIGURACIÓN DE CONEXIÓN: Usamos el archivo correcto de su sistema
require_once "config/conexion.php"; 

// 2. SEGURIDAD HOMOLOGADA: Validamos con el nivel real de su login actual
if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'ADMIN_GENERAL') { 
    die("Acceso denegado. No tiene privilegios de Administrador General."); 
}

$msg = "";
$error_db = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $pass = password_hash($_POST['pass'], PASSWORD_DEFAULT);
    
    // Ajustamos las columnas a la estructura real de su tabla (usuario, password, nombre, nivel, modulo_acceso)
    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, nombre, nivel, modulo_acceso) VALUES (?, ?, ?, ?, ?)");
    
    // Si el rol elegido es "admin", guardamos nivel ADMIN_GENERAL y acceso TODOS. Si no, CAPTURISTA y su módulo.
    if ($_POST['rol'] === 'ADMIN_GENERAL') {
        $nivel_insertar = 'ADMIN_GENERAL';
        $modulo_insertar = 'TODOS';
    } else {
        $nivel_insertar = 'CAPTURISTA';
        $modulo_insertar = strtoupper($_POST['rol']); // SINIESTROS, ARMAMENTO, PERSONAL
    }

    if ($stmt->execute([$_POST['user'], $pass, $_POST['nombre'], $nivel_insertar, $modulo_insertar])) {
        $msg = "Usuario creado correctamente.";
    } else {
        $error_db = "Error al registrar el usuario en la base de datos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h3>Crear Nuevo Usuario</h3>
        
        <?php if(!empty($msg)): ?>
            <div class="alert alert-success"><?= $msg ?></div>
        <?php endif; ?>
        <?php if(!empty($error_db)): ?>
            <div class="alert alert-danger"><?= $error_db ?></div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-3"><input type="text" name="nombre" class="form-control" placeholder="Nombre Real" required></div>
                    <div class="col-md-3"><input type="text" name="user" class="form-control" placeholder="Nombre de Usuario" required></div>
                    <div class="col-md-3"><input type="password" name="pass" class="form-control" placeholder="Contraseña" required></div>
                    <div class="col-md-2">
                        <select name="rol" class="form-select">
                            <option value="siniestros">Siniestros</option>
                            <option value="armamento">Armamento</option>
                            <option value="personal">Personal</option>
                            <option value="ADMIN_GENERAL">Administrador General</option>
                        </select>
                    </div>
                    <div class="col-md-1"><button type="submit" name="crear_usuario" class="btn btn-success w-100">Crear</button></div>
                </div>
            </form>
        </div>

        <h3 class="mt-5">Usuarios Activos</h3>
        <table class="table table-striped bg-white shadow-sm mt-3">
            <thead><tr><th>Nombre</th><th>Usuario</th><th>Nivel</th><th>Módulo de Acceso</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php
                // Se mapea con la estructura real de campos de su base de datos ssc_inventarios
                $usuarios = $pdo->query("SELECT * FROM usuarios")->fetchAll();
                foreach($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['usuario']) ?></td>
                    <td><span class="badge bg-secondary"><?= $u['nivel'] ?></span></td>
                    <td><span class="badge bg-info text-dark"><?= $u['modulo_acceso'] ?? 'NINGUNO' ?></span></td>
                    <td><button class="btn btn-sm btn-danger">Eliminar</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="index.php" class="btn btn-secondary">Volver al Panel</a>
    </div>
</body>
</html>
