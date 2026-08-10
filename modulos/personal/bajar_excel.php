<?php
require '../../vendor/autoload.php';
require_once "../../config/conexion.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Personal Asegurado');

    // 10 ENCABEZADOS SOLICITADOS
    $headers = [
        'APELLIDO PATERNO', 'APELLIDO MATERNO', 'NOMBRE(S)', 'R.F.C.', 
        'ÁREA DE ADSCRIPCIÓN', 'PUESTO', 'DESCRIPCIÓN VÍA PÚBLICA', 
        'CONTRATACIÓN', 'FECHA ALTA', 'QUINCENA'
    ];
    $sheet->fromArray($headers, NULL, 'A1');

    $query = "SELECT apellido_paterno, apellido_materno, nombre, rfc, area_adscripcion, 
                     puesto, descripcion_via_publica, tipo_contratacion, fecha_alta, quincena 
              FROM personal ORDER BY id ASC";
    $stmt = $pdo->query($query);
    $datos = $stmt->fetchAll(PDO::FETCH_NUM);

    if ($datos) {
        $sheet->fromArray($datos, NULL, 'A2');
    }

    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="REPORTE_PERSONAL_'.date('d-m-Y').'.xlsx"');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) { die("Error: " . $e->getMessage()); }
