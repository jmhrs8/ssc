<?php
session_start();

// 1. CONEXIÓN PDO
require_once('../../config/conexion.php');

// 2. SEGURIDAD: Solo ADMIN_GENERAL puede gestionar usuarios
// Corregido: Ahora busca en mayúsculas para coincidir con login.php
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['nivel'] ?? '') !== 'ADMIN_GENERAL') {
    header("Location: ../../index.php?error=acceso_denegado");
    exit();
}

// 3. OBTENER LISTA DE USUARIOS
try {
    // Usamos la variable $pdo definida en tu conexion.php
    $stmt = $pdo->query("SELECT id, nombre, usuario, nivel, modulo_acceso FROM usuarios ORDER BY id DESC");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al consultar usuarios: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - SSC</title>
    <!-- Bootstrap 5 y Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --ssc-red: #8b0000; --ssc-gold: #b8860b; }
        .navbar-ssc { background: #1a1a1a; border-bottom: 3px solid var(--ssc-red); }
        .btn-ssc { background: var(--ssc-red); color: white; border: none; }
        .btn-ssc:hover { background: #660000; color: white; }
        /* Corregido: Clases de badges para que coincidan con los niveles en mayúsculas */
        .badge-admin { background-color: var(--ssc-red); }
        .badge-capturista { background-color: var(--ssc-gold); color: black; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-ssc navbar-dark mb-4 shadow">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="../../index.php">
            <i class="bi bi-shield-lock-fill text-danger"></i> SISTEMA SSC - ADMINISTRACIÓN
        </a>
        <div class="d-flex">
            <span class="navbar-text text-white me-3 text-uppercase">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>
            </span>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2 class="text-dark"><i class="bi bi-people-fill"></i> Control de Usuarios</h2>
            <p class="text-muted">Gestione los accesos para Administradores Generales y Capturistas por módulo.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="nuevo_usuario.php" class="btn btn-ssc shadow-sm">
                <i class="bi bi-person-plus-fill"></i> Agregar Nuevo Usuario
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Nombre Completo</th>
                            <th>Nombre de Usuario</th>
                            <th>Nivel</th>
                            <th>Módulo Asignado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuarios) > 0): ?>
                            <?php foreach ($usuarios as $user): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                                <td><code><?php echo htmlspecialchars($user['usuario']); ?></code></td>
                                <td>
                                    <!-- Corregido: Comparación a mayúsculas para la etiqueta -->
                                    <span class="badge <?php echo strtoupper($user['nivel']) == 'ADMIN_GENERAL' ? 'badge-admin' : 'badge-capturista'; ?>">
                                        <?php echo strtoupper($user['nivel']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-uppercase small fw-bold">
                                        <?php 
                                            $mod = strtoupper($user['modulo_acceso']);
                                            echo ($mod == 'TODOS' || strtoupper($user['nivel']) == 'ADMIN_GENERAL') ? '🔓 Acceso Total' : '📂 ' . $mod; 
                                        ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="editar_usuario.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="eliminar_usuario.php?id=<?php echo $user['id']; ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('¿Está seguro de eliminar a este usuario?')">
                                             <i class="bi bi-trash3-fill"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No hay usuarios registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="../../index.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left-short"></i> Volver al Menú Principal
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
