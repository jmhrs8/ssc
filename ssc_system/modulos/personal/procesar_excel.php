<?php
// Forzar límites más altos en tiempo de ejecución
set_time_limit(900); 
ini_set('memory_limit', '1024M');

require '../../vendor/autoload.php';
require_once "../../config/conexion.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    $file = $_FILES['archivo_excel']['tmp_name'];

    try {
        // LECTURA OPTIMIZADA PARA VMWARE
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true); 
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $pdo->beginTransaction();

        // INSERT IGNORE para evitar errores por duplicados de RFC
        $sql = "INSERT IGNORE INTO personal (
            apellido_paterno, apellido_materno, nombre, rfc, area_adscripcion, 
            puesto, descripcion_via_publica, tipo_contratacion, fecha_alta, quincena
        ) VALUES (?,?,?,?,?,?,?,?,?,?)";
        
        $stmt = $pdo->prepare($sql);
        $insertados = 0;

        foreach ($rows as $index => $row) {
            if ($index == 0 || empty(trim($row[0] ?? ''))) continue;

            // Procesar Fecha de Alta
            $f_alta = null;
            if (!empty($row[8])) {
                $f_alta = is_numeric($row[8]) 
                    ? date('Y-m-d', ($row[8] - 25569) * 86400) 
                    : date('Y-m-d', strtotime(str_replace('/', '-', $row[8])));
            }

            // Lógica Base/Eventual
            $tipo_txt = strtoupper(trim($row[7] ?? ''));
            $tipo_con = (strpos($tipo_txt, 'EVE') !== false) ? 'EVENTUAL' : 'BASE';

            $stmt->execute([
                mb_strtoupper(trim($row[0] ?? '')), // PATERNO
                mb_strtoupper(trim($row[1] ?? '')), // MATERNO
                mb_strtoupper(trim($row[2] ?? '')), // NOMBRE
                mb_strtoupper(trim($row[3] ?? '')), // RFC
                mb_strtoupper(trim($row[4] ?? '')), // ADSCRIPCION
                mb_strtoupper(trim($row[5] ?? '')), // PUESTO
                mb_strtoupper(trim($row[6] ?? '')), // DESCRIPCION TRABAJO
                $tipo_con,                          // BASE/EVENTUAL
                $f_alta,                            // FECHA ALTA
                mb_strtoupper(trim($row[9] ?? ''))  // QUINCENA
            ]);
            $insertados++;
        }

        $pdo->commit();
        header("Location: index.php?status=success&count=$insertados");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error crítico: " . $e->getMessage());
    }
}
