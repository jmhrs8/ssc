<?php
session_start();
require_once "../../config/conexion.php";

// Validar que se haya enviado por POST y con archivo
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_FILES['archivo_excel'])) {
    header("Location: subir_excel.php");
    exit();
}

$fileTmpPath = $_FILES['archivo_excel']['tmp_name'];
$fileName = $_FILES['archivo_excel']['name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (in_array($fileExtension, ['xlsx', 'xls', 'csv'])) {
    
    // Verificar si existe el autoload de vendor para PhpSpreadsheet
    $vendorAutoload = "../../vendor/autoload.php";
    if (!file_exists($vendorAutoload)) {
        echo "<script>alert('Error: No se encontró la librería PhpSpreadsheet en la ruta de vendor.'); window.location.href='subir_excel.php';</script>";
        exit();
    }
    
    require_once $vendorAutoload;

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
        $sheet = $spreadsheet->getSheetByName('CAPTURA DE SINIESTROS PERSONAL');
        if (!$sheet) {
            $sheet = $spreadsheet->getActiveSheet(); 
        }

        $rows = $sheet->toArray();
        $insertados = 0;
        $duplicados = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $no_folio = trim($row[1] ?? '');
            if (empty($no_folio)) continue; 

            $tipo = trim($row[2] ?? '');
            $mes_de_reporte = trim($row[3] ?? '');
            $no_empleado = trim($row[4] ?? '');
            $edad = is_numeric($row[5]) ? $row[5] : null;
            $rfc = trim($row[6] ?? '');
            $nombre = trim($row[7] ?? '');

            $fecha_sin = $row[8] ?? null;
            if (!empty($fecha_sin)) {
                if (is_numeric($fecha_sin)) {
                    $fecha_de_siniestro = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha_sin)->format('Y-m-d');
                } else {
                    $fecha_de_siniestro = date('Y-m-d', strtotime($fecha_sin));
                }
            } else {
                $fecha_de_siniestro = null;
            }

            $reporte = trim($row[9] ?? '');
            $poliza_seccion = trim($row[10] ?? '');
            $aseguradora = trim($row[11] ?? '');
            $causa_resumido = trim($row[12] ?? '');
            $unidad_vehicular = trim($row[13] ?? '');
            $lesiones = trim($row[14] ?? '');
            $area_adscripcion = trim($row[15] ?? '');
            $hospital = trim($row[16] ?? '');
            $requirio_hospitalizacion = trim($row[17] ?? 'NO');
            $observaciones = trim($row[18] ?? '');
            $montos_erogados = is_numeric($row[19]) ? $row[19] : 0.00;

            // Validar duplicados por folio
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM siniestros_personal WHERE no_folio = ?");
            $stmtCheck->execute([$no_folio]);
            if ($stmtCheck->fetchColumn() > 0) {
                $duplicados++;
                continue;
            }

            // Insertar registro
            $stmt = $pdo->prepare("INSERT INTO siniestros_personal (no_folio, tipo, mes_de_reporte, no_empleado, edad, rfc, nombre, fecha_de_siniestro, reporte, poliza_seccion, aseguradora, causa_resumido, unidad_vehicular, lesiones, area_adscripcion, hospital, requirio_hospitalizacion, observaciones, montos_erogados) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

            $stmt->execute([
                $no_folio, $tipo, $mes_de_reporte, $no_empleado, $edad, $rfc, $nombre,
                $fecha_de_siniestro, $reporte, $poliza_seccion, $aseguradora, $causa_resumido,
                $unidad_vehicular, $lesiones, $area_adscripcion, $hospital,
                $requirio_hospitalizacion, $observaciones, $montos_erogados
            ]);
            $insertados++;
        }

        echo "<script>alert('¡Importación exitosa! Registros nuevos agregados: $insertados. Duplicados omitidos: $duplicados.'); window.location.href='index.php';</script>";
        exit();

    } catch (Exception $e) {
        $error_msg = addslashes($e->getMessage());
        echo "<script>alert('Error al procesar el archivo Excel: $error_msg'); window.location.href='subir_excel.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('Formato de archivo no válido. Sube un archivo .xlsx, .xls o .csv'); window.location.href='subir_excel.php';</script>";
    exit();
}
?>
