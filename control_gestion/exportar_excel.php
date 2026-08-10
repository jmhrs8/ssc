<?php
// exportar_excel.php
session_start();

if (!isset($_SESSION['usuario'])) {
    die("Acceso denegado.");
}

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Consultar registros con JOIN para obtener nombres en lugar de IDs
    $query = "SELECT 
                cg.numero_oficio,
                cg.asunto,
                cg.fecha_recepcion,
                cg.fecha_oficio,
                cg.titular,
                cg.cargo,
                COALESCE(ca.nombre_area, 'SUBDIRECCION') AS nombre_area,
                COALESCE(u.nombre_completo, '') AS funcionario_asignado,
                COALESCE(cg.dias_termino, 5) AS dias_termino
              FROM control_gestion cg
              LEFT JOIN catalogo_areas ca ON cg.id_turnado_por = ca.id_area
              LEFT JOIN usuarios u ON cg.id_usuario_asignado = u.id_usuario
              ORDER BY cg.id_registro ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear la hoja de Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Respaldo_Control_Gestion');

    // Encabezados coincidentes con la plantilla de importación
    $headers = [
        'Número de Oficio',
        'Asunto',
        'Fecha de Recepción',
        'Fecha del Oficio',
        'Titular',
        'Cargo',
        'Área que Turna',
        'Funcionario Asignado',
        'Días de Término'
    ];

    $sheet->fromArray($headers, NULL, 'A1');

    // Estilo para el encabezado (Color guinda institucional #861532)
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '861532']
        ],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];
    $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

    // Cargar datos fila por fila
    $rowNumber = 2;
    foreach ($registros as $row) {
        $sheet->setCellValue('A' . $rowNumber, $row['numero_oficio']);
        $sheet->setCellValue('B' . $rowNumber, $row['asunto']);
        $sheet->setCellValue('C' . $rowNumber, $row['fecha_recepcion']);
        $sheet->setCellValue('D' . $rowNumber, $row['fecha_oficio']);
        $sheet->setCellValue('E' . $rowNumber, $row['titular']);
        $sheet->setCellValue('F' . $rowNumber, $row['cargo']);
        $sheet->setCellValue('G' . $rowNumber, $row['nombre_area']);
        $sheet->setCellValue('H' . $rowNumber, $row['funcionario_asignado']);
        $sheet->setCellValue('I' . $rowNumber, $row['dias_termino']);
        $rowNumber++;
    }

    // Auto-ajustar ancho de columnas
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Nombre del archivo de respaldo
    $filename = 'Respaldo_Control_Gestion_' . date('Y-m-d_H-i') . '.xlsx';

    // Encabezados HTTP para descarga directa
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die("Error al exportar a Excel: " . $e->getMessage());
}
