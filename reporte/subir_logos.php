<?php
session_start();
$mensaje = "";

if (isset($_FILES['logo'])) {
    $tipo = $_POST['tipo_logo'];
    $nombre_archivo = ($tipo == 'cdmx') ? 'logo_cdmx.png' : 'logo_ssc.png';
    $ruta_destino = "/var/www/html/" . $nombre_archivo;

    if (move_uploaded_file($_FILES['logo']['tmp_name'], $ruta_destino)) {
        $mensaje = "<div style='color: green;'>Éxito: El logo " . $tipo . " ha sido actualizado.</div>";
    } else {
        $mensaje = "<div style='color: red;'>Error al subir el archivo. Revisa los permisos de la carpeta.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Logos Institucionales</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 50px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; margin: auto; }
        h2 { color: #6b1e34; border-bottom: 2px solid #b38e5d; padding-bottom: 10px; }
        .logo-preview { max-width: 150px; display: block; margin: 10px 0; border: 1px solid #ddd; padding: 5px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input { width: 100%; padding: 10px; margin-top: 5px; }
        button { background: #6b1e34; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
        button:hover { background: #4e1425; }
        .back-link { display: block; margin-top: 20px; text-align: center; color: #666; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <h2>Subir Logos Oficiales</h2>
    <?php echo $mensaje; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Seleccionar Tipo de Logo:</label>
            <select name="tipo_logo" required>
                <option value="cdmx">Logo Ciudad de México (Superior)</option>
                <option value="ssc">Logo SSC (Pie de página)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Archivo (Debe ser .png):</label>
            <input type="file" name="logo" accept="image/png" required>
        </div>

        <button type="submit">Actualizar Logo</button>
    </form>

    <hr>
    <h3>Vistas Previas:</h3>
    <label>CDMX:</label>
    <img src="logo_cdmx.png?t=<?php echo time(); ?>" class="logo-preview" alt="No cargado">
    <label>SSC:</label>
    <img src="logo_ssc.png?t=<?php echo time(); ?>" class="logo-preview" alt="No cargado">

    <a href="index.php" class="back-link">← Volver al Sistema</a>
</div>

</body>
</html>
