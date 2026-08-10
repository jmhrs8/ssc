<?php
session_start();
require_once '../../config/conexion.php'; 
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];
    $modo = $_POST['modo_duplicados'] ?? 'duplicar';

    try {
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        $totalRows = count($rows) - 1;

        $success = 0;
        foreach ($rows as $index => $row) {
            if ($index == 0 || empty($row[2])) continue; // Evitar cabeceras y celdas sin Folio unificado

            $folio = trim($row[2]);

            // Gestión reactiva del modo de colisiones
            if ($modo == 'reemplazar') {
                $stmtDel = $pdo->prepare("DELETE FROM siniestros WHERE folio = ?");
                $stmtDel->execute([$folio]);
            }

            // Tratamiento riguroso de fechas nativas de Excel
            $f_reporte   = !empty($row[3])  ? (is_numeric($row[3])  ? date("Y-m-d", Date::excelToTimestamp($row[3]))  : date("Y-m-d", strtotime(str_replace('/', '-', $row[3]))))  : null;
            $f_siniestro = !empty($row[5])  ? (is_numeric($row[5])  ? date("Y-m-d", Date::excelToTimestamp($row[5]))  : date("Y-m-d", strtotime(str_replace('/', '-', $row[5]))))  : null;
            $f_vb        = !empty($row[28]) ? (is_numeric($row[28]) ? date("Y-m-d", Date::excelToTimestamp($row[28])) : date("Y-m-d", strtotime(str_replace('/', '-', $row[28])))) : null;
            $f_vb_taller = !empty($row[43]) ? (is_numeric($row[43]) ? date("Y-m-d", Date::excelToTimestamp($row[43])) : date("Y-m-d", strtotime(str_replace('/', '-', $row[43])))) : null;
            $f_oficio    = !empty($row[44]) ? (is_numeric($row[44]) ? date("Y-m-d", Date::excelToTimestamp($row[44])) : date("Y-m-d", strtotime(str_replace('/', '-', $row[44])))) : null;

            $h_reporte   = !empty($row[4]) ? date("H:i:s", strtotime(str_replace(['.m.', '.m'], 'm', strtolower($row[4])))) : null;
            $h_siniestro = !empty($row[6]) ? date("H:i:s", strtotime(str_replace(['.m.', '.m'], 'm', strtolower($row[6])))) : null;

            // Bloque SQL parametrizado con las 47 columnas emparejadas al Layout
            $sql = "INSERT INTO siniestros (
                no_consecutivo, mes, folio, fecha_reporte, hora_reporte, fecha_siniestro, hora_siniestro,
                marca, modelo, tipo, economico_placas, no_inventario, no_serie, adscripcion,
                nombre_elemento, no_siniestro, taller_asignado, hospital, carp_investigacion,
                propio, arrendado, aseguradora, declaracion_universal, pase_medicos, pase_taller,
                graficas, cuadernillo, visto_bueno, fecha_visto_bueno, observaciones, estatus,
                zona, tipo_siniestro, taller_ingress, calles, colonia, alcaldia,
                vehiculo_3ro, placas_3ro, seguro_3ro, danos_3ro, lesionados, observaciones_generales,
                fecha_visto_bueno_taller, fecha_oficio_recibido, no_expediente, papeleta_control_gestion
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            $stmt = $pdo->prepare($sql);

            $params = [
                $row[0],  $row[1],  $folio,   $f_reporte, $h_reporte, $f_siniestro, $h_siniestro,
                $row[7],  $row[8],  $row[9],  $row[10], $row[11], $row[12], $row[13],
                $row[14], $row[15], $row[16], $row[17], $row[18], $row[19], $row[20],
                $row[21], $row[22], $row[23], $row[24], $row[25], $row[26], $row[27],
                $f_vb,    $row[29], $row[30], $row[31], $row[32], $row[33], $row[34],
                $row[35], $row[36], $row[37], $row[38], $row[39], $row[40], $row[41],
                $row[42], $f_vb_taller, $f_oficio, $row[45], $row[46]
            ];

            // Normalización a MAYÚSCULAS para mantener consistencia en búsquedas
            foreach ($params as $k => $val) {
                if ($val !== null && !in_array($k, [3,4,5,6,28,43,44])) {
                    $params[$k] = strtoupper(trim($val));
                }
            }

            $stmt->execute($params);
            $success++;

            // Despacho periódico de progreso en la sesión
            if ($index % 10 == 0) {
                session_start();
                $_SESSION['progress'] = round(($index / $totalRows) * 100);
                session_write_close();
            }
        }
        echo json_encode(['status' => 'success', 'message' => "Procesamiento exitoso. Se cargaron $success registros en total."]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => "Falla de ejecución: " . $e->getMessage()]);
    }
}
?>
