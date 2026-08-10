<?php
require '../../vendor/autoload.php'; // Carga la librería
require_once "../../config/conexion.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

set_time_limit(0); 
ini_set('memory_limit', '1G'); // Más memoria para los 70k registros

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    try {
        $archivo = $_FILES['archivo']['tmp_name'];
        $spreadsheet = IOFactory::load($archivo);
        $hoja = $spreadsheet->getActiveSheet();
        $filas = $hoja->toArray();

        $pdo->beginTransaction();
        
        // SQL con ON DUPLICATE KEY para actualizar si el folio ya existe
        $sql = "INSERT INTO siniestros (no_consecutivo, mes, folio, fecha, hora, marca, modelo, tipo, economico, placas, adscripcion, nombre_elemento, no_siniestro) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?) 
                ON DUPLICATE KEY UPDATE 
                placas=VALUES(placas), 
                nombre_elemento=VALUES(nombre_elemento)";
        
        $stmt = $pdo->prepare($sql);
        $count = 0;

        foreach ($filas as $index => $row) {
            if ($index == 0) continue; // Saltar encabezados
            if (empty($row[2])) continue; // Si no hay folio, saltar

            $stmt->execute([
                $row[0], $row[1], $row[2], $row[3], $row[4], 
                $row[5], $row[6], $row[7], $row[8], $row[9], 
                $row[10], $row[11], $row[12]
            ]);
            $count++;
        }

        $pdo->commit();
        echo "Carga exitosa: $count registros procesados.";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>
