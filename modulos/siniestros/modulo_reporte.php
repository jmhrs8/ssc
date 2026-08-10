<?php
// Conexión a la base (ajusta tus datos)
$db = new mysqli("localhost", "usuario", "password", "ssc_system");

$folio = $_GET['folio']; // Recibimos el folio desde el botón del index

// 1. Jalamos datos de la base ORIGINAL (Siniestros)
$resPrincipal = $db->query("SELECT * FROM siniestros WHERE folio_siniestro = '$folio'");
$datosOriginal = $resPrincipal->fetch_assoc();

// 2. Jalamos datos de la base ESPEJO (Reporte Detallado) por si ya se empezó a llenar
$resEspejo = $db->query("SELECT * FROM reporte_siniestro_detallado WHERE folio_siniestro = '$folio'");
$datosEspejo = $resEspejo->fetch_assoc();

// Lógica de Proyección: Si existe en espejo úsalo, si no, usa el original, si no, vacío.
$marca = $datosEspejo['marca_espejo'] ?? $datosOriginal['marca'] ?? '';
$modelo = $datosEspejo['modelo_espejo'] ?? $datosOriginal['modelo'] ?? '';
$licencia = $datosEspejo['cond_licencia'] ?? ''; // Este suele estar en blanco para captura
?>

<form action="procesar_espejo.php" method="POST">
    <input type="hidden" name="folio_siniestro" value="<?php echo $folio; ?>">
    
    <h3>Datos del Vehículo (Proyectados)</h3>
    <input type="text" name="marca_espejo" value="<?php echo $marca; ?>">
    <input type="text" name="modelo_espejo" value="<?php echo $modelo; ?>">
    
    <h3>Datos a Capturar (Base Espejo)</h3>
    <input type="text" name="cond_licencia" value="<?php echo $licencia; ?>" placeholder="Capturar Licencia">
    <textarea name="concluyo_atencion"><?php echo $datosEspejo['concluyo_atencion'] ?? ''; ?></textarea>
    
    <button type="submit">Guardar en Base Espejo</button>
</form>
