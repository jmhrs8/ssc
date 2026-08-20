<?php
session_start();
set_time_limit(900);
ini_set('memory_limit', '1024M');

require '../../vendor/autoload.php';
require_once "../../config/conexion.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    $file = $_FILES['archivo_excel']['tmp_name'];

    $nuevos = 0;
    $actualizados = 0;
    $recortados = 0;
    $errores_detallados = [];
    $log_recortes = [];
    $log_duplicados = []; // NUEVO: Para guardar el detalle de duplicados reescritos

    try {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $sql = "INSERT INTO personal (apellido_paterno, apellido_materno, nombre, rfc, area_adscripcion, puesto, descripcion_via_publica, tipo_contratacion, fecha_alta, quincena) 
                VALUES (?,?,?,?,?,?,?,?,?,?) 
                ON DUPLICATE KEY UPDATE 
                apellido_paterno = VALUES(apellido_paterno),
                apellido_materno = VALUES(apellido_materno),
                nombre = VALUES(nombre),
                area_adscripcion = VALUES(area_adscripcion),
                puesto = VALUES(puesto),
                descripcion_via_publica = VALUES(descripcion_via_publica),
                tipo_contratacion = VALUES(tipo_contratacion),
                fecha_alta = VALUES(fecha_alta),
                quincena = VALUES(quincena)";
                
        $stmt_insert = $pdo->prepare($sql);

        $pdo->beginTransaction();

        foreach ($rows as $index => $row) {
            if ($index == 0) continue;

            $num_fila = $index + 1;
            
            if (empty(trim($row[0] ?? '')) && empty(trim($row[1] ?? '')) && empty(trim($row[2] ?? '')) && empty(trim($row[3] ?? ''))) {
                continue;
            }

            try {
                $paterno   = mb_strtoupper(trim($row[0] ?? 'SIN DATO'));
                $materno   = mb_strtoupper(trim($row[1] ?? ''));
                $nombre    = mb_strtoupper(trim($row[2] ?? 'SIN DATO'));
                $rfc_raw   = mb_strtoupper(trim($row[3] ?? ''));
                $nombre_completo = "$nombre $paterno $materno";

                if (empty($rfc_raw) || $rfc_raw === 'NAN') {
                    $errores_detallados[] = "Fila $num_fila ($nombre_completo): RFC vacío u omitido.";
                    continue;
                }

                $rfc = $rfc_raw;
                if (mb_strlen($rfc) > 13) {
                    $rfc = mb_substr($rfc, 0, 13);
                    $recortados++;
                    $log_recortes[] = "Fila $num_fila ($nombre_completo): RFC original '$rfc_raw' recortado a '$rfc'.";
                }

                // Limpieza de campos largos
                $adscripcion = mb_strtoupper(trim($row[4] ?? ''));
                if (mb_strlen($adscripcion) > 255) $adscripcion = mb_substr($adscripcion, 0, 255);

                $puesto = mb_strtoupper(trim($row[5] ?? ''));
                if (mb_strlen($puesto) > 150) $puesto = mb_substr($puesto, 0, 150);

                $descripcion = mb_strtoupper(trim($row[6] ?? ''));
                if (mb_strlen($descripcion) > 255) $descripcion = mb_substr($descripcion, 0, 255);

                $f_alta = null;
                if (!empty($row[8])) {
                    $f_alta = is_numeric($row[8]) 
                        ? date('Y-m-d', ($row[8] - 25569) * 86400) 
                        : date('Y-m-d', strtotime(str_replace('/', '-', $row[8])));
                }

                $tipo_txt = strtoupper(trim($row[7] ?? ''));
                $tipo_con = (strpos($tipo_txt, 'EVE') !== false) ? 'EVENTUAL' : 'BASE';
                $quincena = mb_strtoupper(trim($row[9] ?? ''));

                // Verificamos antes si ya existe en la BD para reportarlo específicamente como duplicado reescrito
                $stmt_chk = $pdo->prepare("SELECT COUNT(*) FROM personal WHERE rfc = ?");
                $stmt_chk->execute([$rfc]);
                $ya_existia = $stmt_chk->fetchColumn() > 0;

                $stmt_insert->execute([
                    $paterno, $materno, $nombre, $rfc, 
                    $adscripcion, $puesto, $descripcion, 
                    $tipo_con, $f_alta, $quincena
                ]);

                if ($ya_existia) {
                    $actualizados++;
                    $log_duplicados[] = "Fila $num_fila: RFC '$rfc' ($nombre_completo) ya existía y fue reescrito/actualizado.";
                } else {
                    $nuevos++;
                }

            } catch (Exception $row_ex) {
                $errores_detallados[] = "Fila $num_fila: " . $row_ex->getMessage();
            }
        }

        $pdo->commit();

        $_SESSION['resultado_importacion'] = [
            'nuevos' => $nuevos,
            'duplicados' => $actualizados,
            'log_duplicados' => $log_duplicados, // NUEVO
            'recortados' => $recortados,
            'log_recortes' => $log_recortes,
            'errores' => count($errores_detallados),
            'detalle_errores' => $errores_detallados
        ];

        header("Location: subir_excel.php");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error crítico en el proceso: " . $e->getMessage());
    }
}
?>
