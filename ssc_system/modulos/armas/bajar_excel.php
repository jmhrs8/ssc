<?php
/**
 * ARCHIVO: bajar_excel.php
 * FUNCIÓN: Exporta la base de datos de armamento con el nuevo orden de 37 columnas.
 */
error_reporting(0);
ini_set('display_errors', 0);

require '../../vendor/autoload.php';
require_once "../../config/conexion.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('INVENTARIO_ARMAMENTO');

    // 1. ENCABEZADOS OFICIALES (37 Columnas)
    $headers = [
        'PÓLIZA', 'TIPO DE BIEN', 'TIPO DE SINIESTRO', 'AÑO/VIGENCIA', 'FECHA DE SINIESTRO',
        'No. SINIESTRO', 'DESPACHO', 'ASEGURADORA', 'STATUS (SEGURO)', 'No. EXPEDIENTE',
        'TIPO DE DAÑO O RECLAMACIÓN', 'FECHA DE RECLAMO', 'MES', 'MARCA', 'MODELO',
        'SERIE/MATRICULA', 'STATUS DE TRAMITE', 'OBSERVACIONES (DETALLE)', 'IMPORTE DEL CONVENIO', 'TIPO DE PAGO',
        'COMPROBANTE DE PAGO', 'FECHA DE ACUSE DE RECLAMO', 'DÍAS TRANSCURRIDOS', 'FOLIO SDRA', 'OF. RECIBIDO',
        'N° DE OFICIO', 'BIENES DIVERSOS', 'CANDADO DE MANOS', 'ARMAS', 'CARGADOR',
        'CARTUCHOS', 'CASCOS', 'ESCUDOS', 'CHALECOS', 'ATENDIO',
        'DIGITALIZADO', 'OBSERVACIONES GRALES'
    ];

    // Estilo para encabezados (Fondo negro, letras Cyan)
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => '0DCAF0']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1A1A']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    // Aplicar encabezados y estilos
    $colChar = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($colChar . '1', $h);
        $sheet->getStyle($colChar . '1')->applyFromArray($headerStyle);
        $sheet->getColumnDimension($colChar)->setAutoSize(true);
        $colChar++;
    }

    // 2. CONSULTA SQL CON EL NUEVO ORDEN Y CÁLCULO DE DÍAS
    $sql = "SELECT 
                poliza, tipo_bien, tipo_siniestro, anio_vigencia, fecha_siniestro_1,
                no_siniestro, despacho, aseguradora, status_seguro, no_expediente,
                tipo_dano, fecha_reclamacion, mes_reclamacion, marca, modelo,
                serie_matricula_1, status_tramite, siniestro_detalle, importe_convenio, tipo_pago,
                comprobante_pago, fecha_acuse, 
                DATEDIFF(IFNULL(fecha_acuse, CURDATE()), fecha_reclamacion) as dias_calculados,
                folio_sdra, of_recibido, no_oficio, bienes_diversos, candado_manos, 
                armas, cargador, cartuchos, cascos, escudos, chalecos, atendio, 
                digitalizado, observaciones_grales
            FROM inventario_armas 
            ORDER BY id ASC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_NUM);

    // 3. INSERTAR DATOS
    if ($rows) {
        $sheet->fromArray($rows, NULL, 'A2');
        
        // Formatear columna de Importe (S) como moneda
        $lastRow = count($rows) + 1;
        $sheet->getStyle('S2:S'.$lastRow)->getNumberFormat()->setFormatCode('$#,##0.00');
    }

    // 4. DESCARGA
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="REPORTE_ARMAMENTO_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');

    // Limpiar buffer para evitar carácteres extraños
    if (ob_get_length()) ob_end_clean();

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die("Error al generar Excel: " . $e->getMessage());
}
