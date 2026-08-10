<?php
require '../../vendor/autoload.php';
require_once "../../config/conexion.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 1. Definir los 47 encabezados exactos en el orden esperado por subir_excel.php
$headers = [
    'No. Consecutivo', 'Mes', 'Folio', 'Fecha Reporte', 'Hora Reporte', 'Fecha Siniestro', 'Hora Siniestro',
    'Marca', 'Modelo', 'Tipo', 'Económico/Placas', 'No. Inventario', 'No. Serie', 'Adscripción',
    'Nombre Elemento', 'No. Siniestro', 'Taller Asignado', 'Hospital', 'Carpeta Investigación',
    'Propio', 'Arrendado', 'Aseguradora', 'Declaración Universal', 'Pase Médicos', 'Pase Taller',
    'Gráficas', 'Cuadernillo', 'Visto Bueno', 'Fecha Visto Bueno', 'Observaciones', 'Estatus',
    'Zona', 'Tipo Siniestro', 'Taller Ingreso', 'Calles', 'Colonia', 'Alcaldía',
    'Vehículo 3ro', 'Placas 3ro', 'Seguro 3ro', 'Daños 3ro', 'Lesionados', 'Observaciones Generales',
    'Fecha Visto Bueno Taller', 'Fecha Oficio Recibido', 'No. Expediente', 'Papeleta Gestión'
];

// 2. Colocar encabezados en la fila 1
$column = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($column . '1', $header);
    $column++;
}

// 3. Obtener los datos de la base de datos
try {
    $stmt = $pdo->query("SELECT * FROM siniestros");
    $rowCount = 2;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Mapeo manual para asegurar que los datos caigan en la columna correcta
        $data = [
            $row['no_consecutivo'], $row['mes'], $row['folio'], $row['fecha_reporte'], 
            $row['hora_reporte'], $row['fecha_siniestro'], $row['hora_siniestro'],
            $row['marca'], $row['modelo'], $row['tipo'], $row['economico_placas'], 
            $row['no_inventario'], $row['no_serie'], $row['adscripcion'],
            $row['nombre_elemento'], $row['no_siniestro'], $row['taller_asignado'], 
            $row['hospital'], $row['carp_investigacion'],
            $row['propio'], $row['arrendado'], $row['aseguradora'], 
            $row['declaracion_universal'], $row['pase_medicos'], $row['pase_taller'],
            $row['graficas'], $row['cuadernillo'], $row['visto_bueno'], 
            $row['fecha_visto_bueno'], $row['observaciones'], $row['estatus'],
            $row['zona'], $row['tipo_siniestro'], $row['taller_ingreso'], 
            $row['calles'], $row['colonia'], $row['alcaldia'],
            $row['vehiculo_3ro'], $row['placas_3ro'], $row['seguro_3ro'], 
            $row['danos_3ro'], $row['lesionados'], $row['observaciones_generales'],
            $row['fecha_visto_bueno_taller'], $row['fecha_oficio_recibido'], 
            $row['no_expediente'], $row['papeleta_control_gestion']
        ];

        $sheet->fromArray($data, null, 'A' . $rowCount);
        $rowCount++;
    }
} catch (Exception $e) {
    die("Error al generar el archivo: " . $e->getMessage());
}

// 4. Configurar headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Reporte_Siniestros_'.date('Y-m-d').'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
