<?php
session_start();
include('config.php');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: login.php"); exit(); }

$id = intval($_GET['id']);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre_completo']);
    $nueva_pass = $_POST['password'];
    $rol = $_POST['rol'];

    if (!empty($nueva_pass)) {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo = ?, password = ?, rol = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nombre, $nueva_pass, $rol, $id);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo = ?, rol = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $rol, $id);
    }
    $stmt->execute();
    header("Location: usuarios.php?msg=editado");
    exit();
}

$res = $conn->query("SELECT * FROM usuarios WHERE id = $id");
$user = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Editar Usuario</title></head>
<body style="font-family:sans-serif; background:#eee; display:flex; justify-content:center; padding:50px;">
    <form method="POST" style="background:white; padding:30px; border-radius:10px; width:300px; box-shadow:0 5px 15px rgba(0,0,0,0.1);">
        <h3 style="color:#6b1e34">Editar: <?= htmlspecialchars($user['usuario']) ?></h3>
        <input type="text" name="nombre_completo" value="<?= htmlspecialchars($user['nombre_completo']) ?>" required style="width:100%; margin-bottom:10px; padding:8px;">
        <input type="password" name="password" placeholder="Nueva Contraseña (opcional)" style="width:100%; margin-bottom:10px; padding:8px;">
        <select name="rol" style="width:100%; margin-bottom:10px; padding:8px;">
            <option value="usuario" <?= $user['rol'] == 'usuario' ? 'selected' : '' ?>>Usuario</option>
            <option value="admin" <?= $user['rol'] == 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <button type="submit" style="width:100%; background:#6b1e34; color:white; border:none; padding:10px;">GUARDAR</button>
        <br><br><a href="usuarios.php" style="font-size:12px;">Volver</a>
    </form>
</body>
</html>
