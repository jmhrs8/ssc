<?php
session_start();
include('config.php');
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php?error=acceso_denegado");
    exit();
}
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    if ($id_del != $_SESSION['user_id']) {
        $conn->query("DELETE FROM usuarios WHERE id = $id_del");
        header("Location: usuarios.php?msg=eliminado");
    } else {
        header("Location: usuarios.php?msg=error_autorreferencia");
    }
    exit();
}
$res = $conn->query("SELECT id, usuario, rol, nombre_completo FROM usuarios ORDER BY nombre_completo ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - SSC</title>
    <link rel="manifest" href="manifest.json">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <script>
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').catch(err => console.log('SW error', err));
      }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --guinda: #6b1e34; --oro: #b38e5d; }
        body { font-family: sans-serif; background-color: #f4f4f4; min-height: 100vh; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .tabla-gestion { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
        .tabla-gestion th { background: var(--guinda); color: white; padding: 12px 15px; text-align: left; font-size: 14px; }
        .tabla-gestion td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: middle; word-wrap: break-word; }
        .col-nombre  { width: 35%; }
        .col-usuario { width: 25%; }
        .col-rol     { width: 20%; }
        .col-acciones{ width: 20%; text-align: center; }
        .btn-edit { background: var(--oro); color: white; padding: 8px 12px; border-radius: 4px; text-decoration: none; margin-right: 5px; display: inline-block; }
        .btn-del { background: #d32f2f; color: white; padding: 8px 12px; border-radius: 4px; text-decoration: none; display: inline-block; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--oro); padding-bottom: 10px; margin-bottom: 20px;">
        <h2 style="color:var(--guinda); margin:0;">Gestión de Usuarios</h2>
        <a href="index.php" style="text-decoration:none; color:#666; font-weight:bold;"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
    </div>
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] == 'eliminado'): ?><div class="alert alert-success">Usuario eliminado correctamente.</div><?php elseif ($_GET['msg'] == 'error_autorreferencia'): ?><div class="alert alert-error">No puedes eliminar tu propia cuenta de administrador.</div><?php endif; ?>
    <?php endif; ?>
    <a href="crear_usuario.php" style="display:inline-block; margin-bottom:20px; color:var(--oro); font-weight:bold; text-decoration:none;"><i class="fas fa-user-plus"></i> + Nuevo Usuario</a>
    <table class="tabla-gestion">
        <thead><tr><th class="col-nombre">Nombre Real</th><th class="col-usuario">Usuario</th><th class="col-rol">Rol</th><th class="col-acciones">Acciones</th></tr></thead>
        <tbody>
            <?php if($res && $res->num_rows > 0): ?><?php while($user = $res->fetch_assoc()): ?>
            <tr><td><strong><?= htmlspecialchars($user['nombre_completo']) ?></strong></td><td><?= htmlspecialchars($user['usuario']) ?></td><td><span style="background:#f0f0f0; padding:3px 8px; border-radius:4px; font-size:12px;"><?= strtoupper($user['rol']) ?></span></td><td class="col-acciones"><a href="editar_usuario.php?id=<?= $user['id'] ?>" class="btn-edit" title="Editar"><i class="fas fa-edit"></i></a><a href="usuarios.php?delete=<?= $user['id'] ?>" class="btn-del" onclick="return confirm('¿Eliminar a este usuario?')" title="Eliminar"><i class="fas fa-trash"></i></a></td></tr>
            <?php endwhile; ?><?php else: ?><tr><td colspan="4" style="text-align:center;">No se encontraron usuarios en la base de datos.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
