<?php
session_start();
$ruta_fondo = "uploads/sistema/fondo_actual.jpg";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['fondo'])) {
    if (move_uploaded_file($_FILES['fondo']['tmp_name'], $ruta_fondo)) {
        $msg = "Fondo actualizado correctamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración Visual - SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container bg-white p-4 shadow rounded" style="max-width: 500px;">
        <h4>Personalizar Sistema</h4>
        <p class="text-muted small">Sube la imagen que se verá de fondo con 20% de opacidad.</p>
        
        <?php if(isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Seleccionar Imagen (JPG)</label>
                <input type="file" name="fondo" class="form-control" accept="image/jpeg" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Subir y Aplicar Fondo</button>
            <a href="index.php" class="btn btn-link w-100 mt-2">Volver al Menú</a>
        </form>
    </div>
</body>
</html>
