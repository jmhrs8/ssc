<?php
// ... conexión a base de datos ...

// Recibimos los datos del formulario
$folio = $_POST['folio_siniestro'];
$marca = $_POST['marca_espejo'];
$modelo = $_POST['modelo_espejo'];
$concluyo = $_POST['concluyo_atencion'];

// Sentencia SQL real para PHP
$sql = "INSERT INTO reporte_siniestro_detallado (folio_siniestro, marca_espejo, modelo_espejo, concluyo_atencion) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        marca_espejo = VALUES(marca_espejo),
        modelo_espejo = VALUES(modelo_espejo),
        concluyo_atencion = VALUES(concluyo_atencion)";

$stmt = $db->prepare($sql);
$stmt->bind_param("ssss", $folio, $marca, $modelo, $concluyo);
$stmt->execute();

echo "Datos guardados. Ahora puedes generar el PDF.";
// Aquí podrías poner el botón para ir a generar_pdf.php?folio=XYZ
?>
