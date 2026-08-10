<?php
require '../../vendor/autoload.php';
require_once "../../config/conexion.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados exactos para tu base de datos
$headers = [
    'no_consecutivo', 'mes', 'folio', 'fecha', 'hora', 
    'marca', 'modelo', 'tipo', 'economico', 'placas', 
    'adscripcion', 'nombre_elemento', 'no_siniestro'
];

// Escribir encabezados en la fila 1
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// Ejemplo en la fila 2
$sheet->fromArray(['1', 'ENERO', 'FOLIO-001', '2026-04-17', '12:00', 'NISSAN', '2024', 'PICKUP', 'ECO-100', 'ABC-123', 'CENTRO', 'JUAN PEREZ', 'SIN-01'], NULL, 'A2');

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="plantilla_siniestros.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
