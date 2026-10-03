<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php?error=acceso_denegado"); 
    exit(); 
}
include('config.php');
$usuario_actual = $_SESSION['usuario_nombre'];
$tipos = $conn->query("SELECT * FROM tipos_unidad ORDER BY nombre ASC"); 
$mis_reportes = $conn->query("SELECT id, n_inventario, n_placa, fecha FROM reportes WHERE registrado_por = '$usuario_actual' ORDER BY id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Registro - SSC</title>
    <link rel="manifest" href="manifest.json">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6b1e34">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <script>
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').catch(err => console.log('SW error', err));
      }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --guinda: #6b1e34; --oro: #b38e5d; }
        body { font-family: sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .navbar { max-width: 800px; margin: 0 auto 10px; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 10px; border-top: 5px solid var(--guinda); box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { color: var(--guinda); margin-top: 0; }
        label { font-weight: bold; font-size: 14px; }
        input[type="text"], select { width: 100%; padding: 10px; margin: 10px 0 20px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; text-transform: uppercase; }
        .btn { width: 100%; padding: 12px; background: var(--guinda); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn:hover { background: #4d1525; }
        .tabla-reportes { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        .tabla-reportes th { background: #f8f9fa; text-align: left; padding: 10px; border-bottom: 2px solid #ddd; }
        .tabla-reportes td { padding: 10px; border-bottom: 1px solid #eee; }
        .pdf-btn { color: #d32f2f; text-decoration: none; font-weight: bold; }
        #preview-container { display: flex; flex-wrap: wrap; gap: 10px; margin: 15px 0; padding: 10px; border: 2px dashed var(--oro); border-radius: 8px; min-height: 50px; }
        .foto-item { border: 2px solid var(--guinda); border-radius: 4px; padding: 2px; position: relative; cursor: move; }
        
        /* Estilo específico para el botón de Excel */
        .btn-excel { background: #28a745; margin-bottom: 15px; text-decoration: none; width: auto; display: inline-flex; }
        .btn-excel:hover { background: #1e7e34; }
    </style>
</head>
<body>
<div class="navbar">
    <div><i class="fas fa-user"></i> <strong><?= htmlspecialchars($usuario_actual) ?></strong> <small>[<?= strtoupper($_SESSION['rol']) ?>]</small></div>
    <div style="display: flex; gap: 15px;"><a href="index.php" style="color:var(--guinda); text-decoration:none; font-weight:bold;">Volver al Panel</a><a href="logout.php" style="color:red; text-decoration:none; font-weight:bold;">Cerrar Sesión</a></div>
</div>
<div class="card">
    <?php if (isset($_GET['msg'])): ?><div style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px; border-radius:5px;"><i class="fas fa-check-circle"></i> ¡Reporte guardado con éxito!</div><?php endif; ?>
    <h2><i class="fas fa-edit"></i> Nuevo Registro de Unidad</h2>
    <form action="procesar.php" method="POST" enctype="multipart/form-data">
        <label>No. Inventario:</label><input type="text" name="n_inventario" required placeholder="EJ: SSC-12345" oninput="this.value = this.value.toUpperCase()">
        <label>No. Placa:</label><input type="text" name="n_placa" required placeholder="EJ: MX-000-A1" oninput="this.value = this.value.toUpperCase()">
        <label>Tipo de Unidad:</label><select name="n_tipo" required><option value="">-- Seleccione un tipo --</option><?php if($tipos && $tipos->num_rows > 0): ?><?php while($t = $tipos->fetch_assoc()): ?><option value="<?= htmlspecialchars(strtoupper($t['nombre'])) ?>"><?= strtoupper(htmlspecialchars($t['nombre'])) ?></option><?php endwhile; ?><?php endif; ?></select>
        <label>Fotos de la Unidad:</label><input type="file" id="input-fotos" name="fotos[]" multiple required accept="image/*"><div id="preview-container"></div><input type="hidden" name="orden_imagenes" id="orden_imagenes">
        <button type="submit" class="btn"><i class="fas fa-save"></i> GUARDAR Y GENERAR REPORTE</button>
    </form>
</div>
<div class="card">
    <h2><i class="fas fa-file-pdf"></i> Mis Registros Recientes</h2>
    
    <a href="exportar_excel.php" class="btn btn-excel">
        <i class="fas fa-file-excel"></i> EXPORTAR INVENTARIO A EXCEL (.XLS)
    </a>

    <table class="tabla-reportes">
        <thead><tr><th>Inventario</th><th>Placa</th><th>Fecha</th><th>Acción</th></tr></thead>
        <tbody>
            <?php if($mis_reportes && $mis_reportes->num_rows > 0): ?><?php while($row = $mis_reportes->fetch_assoc()): ?>
            <tr><td><strong><?= strtoupper($row['n_inventario']) ?></strong></td><td><?= strtoupper($row['n_placa']) ?></td><td><?= date('d/m/y H:i', strtotime($row['fecha'])) ?></td><td><a href="generar_pdf.php?id=<?= $row['id'] ?>" target="_blank" class="pdf-btn"><i class="fas fa-file-pdf"></i> Ver PDF</a></td></tr>
            <?php endwhile; ?><?php else: ?><tr><td colspan="4" style="text-align:center;">No has creado reportes aún.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const input = document.getElementById('input-fotos');
    const preview = document.getElementById('preview-container');
    const ordenInput = document.getElementById('orden_imagenes');
    input.addEventListener('change', function() {
        preview.innerHTML = '';
        Array.from(this.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => {
                const div = document.createElement('div');
                div.className = 'foto-item'; div.setAttribute('data-name', file.name);
                div.innerHTML = `<img src="${e.target.result}" width="100" height="75" style="object-fit:cover; border-radius:4px;">`;
                preview.appendChild(div); actualizarOrden();
            }
            reader.readAsDataURL(file);
        });
    });
    new Sortable(preview, { animation: 150, onEnd: actualizarOrden });
    function actualizarOrden() {
        const names = Array.from(preview.querySelectorAll('.foto-item')).map(i => i.getAttribute('data-name'));
        ordenInput.value = JSON.stringify(names);
    }
</script>
</body>
</html>
