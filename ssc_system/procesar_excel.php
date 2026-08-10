<?php
require_once "../../config/conexion.php";
header('Content-Type: application/json');

$offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 500;
$archivo = $_FILES['archivo']['tmp_name'];

$filas = file($archivo);
$total_filas = count($filas) - 1; // Menos el encabezado
$data_batch = array_slice($filas, $offset + 1, $limit);

$procesados_en_este_lote = 0;

foreach ($data_batch as $linea) {
    $datos = str_getcsv($linea);
    
    if (count($datos) < 3) continue; // Salta líneas vacías

    $folio = $datos[2]; // Asumiendo que el Folio es la 3ra columna

    // Punto 7: Detección y reemplazo automático (ON DUPLICATE KEY)
    // Usamos los 28 campos mencionados en tu documento
    $sql = "INSERT INTO siniestros (
        consecutivo, mes, folio, fecha, hora, marca, modelo, tipo, economico, placas, 
        n_inventario, n_serie, adscripcion, nombre_elemento, n_siniestro, taller_asignado, 
        hospital, carpeta_investigacion, arrendado, aseguradora, propio, declaracion_universal, 
        pase_medico, pase_taller, cuadernillo, visto_bueno, fecha_visto_bueno, observaciones
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE 
        fecha=VALUES(fecha), hora=VALUES(hora), adscripcion=VALUES(adscripcion), taller_asignado=VALUES(taller_asignado)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_slice($datos, 0, 28));
    
    $procesados_en_este_lote++;
}

$actual_total_procesados = $offset + $procesados_en_este_lote;

if ($actual_total_procesados < $total_filas) {
    echo json_encode([
        'status' => 'progress',
        'porcentaje' => ($actual_total_procesados / $total_filas) * 100,
        'procesados' => $actual_total_procesados,
        'total' => $total_filas,
        'next_offset' => $offset + $limit
    ]);
} else {
    echo json_encode([
        'status' => 'complete',
        'total' => $total_filas
    ]);
}
