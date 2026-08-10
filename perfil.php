<?php
session_start();
require_once 'config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['nuevo_fondo'])) {
    $file = $_FILES['nuevo_fondo'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombre_archivo = "fondo_" . $_SESSION['user_id'] . "." . $ext;
    $ruta_destino = "assets/img/fondos/" . $nombre_archivo;

    if (move_uploaded_file($file['tmp_name'], $ruta_destino)) {
        $stmt = $pdo->prepare("UPDATE usuarios SET img_fondo = ? WHERE id = ?");
        $stmt->execute([$nombre_archivo, $_SESSION['user_id']]);
        $_SESSION['fondo'] = $nombre_archivo;
        header("Location: perfil.php?status=success");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style> body::before { background-image: url('assets/img/fondos/<?= $_SESSION['fondo'] ?>'); opacity: <?= $_SESSION['opacidad'] ?>; } </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-lg p-4">
            <h2>Configuración de Perfil</h2>
            <hr>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    [cite_start]<label class="form-label">Cambiar Fondo de Pantalla (Se aplicará con 20% de opacidad)</label> [cite: 20]
                    <input type="file" name="nuevo_fondo" class="form-control" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar Fondo</button>
                <a href="index.php" class="btn btn-secondary">Regresar</a>
            </form>
        </div>
    </div>
</body>
</html>
