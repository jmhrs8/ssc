<?php
require '../../vendor/autoload.php';
require_once "../../config/conexion.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte Radios y Semovientes');

    // 1. ENCABEZADOS EXACTOS (20 COLUMNAS)
    $headers = [
        'POLIZA', 'TIPO DE BIEN', 'TIPO DE SINIESTRO', 'AÑO/VIGENCIA', 
        'FECHA DE SINIESTRO', 'No. SINIESTRO', 'DESPACHO', 'ASEGURADORA', 
        'ESTATUS', 'No. EXPEDIENTE', 'TIPO DE DAÑO O RECLAMACIÓN', 'RECLAMO', 
        'MARCA', 'MODELO', 'SERIE/MATRICULA', 'ESTATUS DE TRAMITE', 
        'OBSERVACIONES', 'IMPORTE DEL CONVENIO', 'TIPO DE PAGO', 'COMPROBANTE DE PAGO'
    ];
    $sheet->fromArray($headers, NULL, 'A1');

    // 2. CONSULTA ACTUALIZADA (Cuidando los nuevos nombres de columna)
    $query = "SELECT 
                poliza, tipo_bien, tipo_siniestro, anio_vigencia, 
                fecha_siniestro, no_siniestro, despacho, aseguradora, 
                estatus, no_expediente, tipo_dano_reclamacion, reclamo, 
                marca, modelo, serie_matricula, estatus_tramite, 
                observaciones, importe_convenio, tipo_pago, comprobante_pago 
              FROM inventario_radio 
              ORDER BY id ASC";
              
    $stmt = $pdo->query($query);
    $datos = $stmt->fetchAll(PDO::FETCH_NUM);

    if ($datos) {
        $sheet->fromArray($datos, NULL, 'A2');
    }

    // 3. FORMATO AUTOMÁTICO DE COLUMNAS (A hasta T son 20 columnas)
    foreach (range('A', 'T') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Configuración de cabecera para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="REPORTE_RADIOS_Y_SEMOVIENTES.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die("Error al exportar: " . $e->getMessage());
}
