<?php
require '../../vendor/autoload.php';
require_once "../../config/conexion.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    $file = $_FILES['archivo_excel']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $pdo->beginTransaction();
        $count = 0;

        foreach ($rows as $index => $row) {
            if ($index == 0) continue; // Saltar encabezado

            // Ignorar filas vacías
            if (empty($row[0]) && empty($row[14]) && empty($row[9])) continue;

            // 1. VALIDACIÓN DE FECHA
            $fecha_sin = null;
            if (!empty($row[4])) {
                if (is_numeric($row[4])) {
                    $unix_date = (floatval($row[4]) - 25569) * 86400;
                    $fecha_sin = date('Y-m-d', $unix_date);
                } else {
                    $timestamp = strtotime(str_replace('/', '-', $row[4]));
                    $fecha_sin = ($timestamp) ? date('Y-m-d', $timestamp) : null;
                }
            }

            // 2. VALIDACIÓN DE IMPORTE
            $importe_raw = str_replace(['$', ','], '', $row[17] ?? '0');
            $importe = is_numeric($importe_raw) ? floatval($importe_raw) : 0.00;

            // 3. FUNCIÓN DE LIMPIEZA Y RECORTE (Evita errores "Data too long")
            // Recortamos a 100 caracteres las columnas comunes y a 250 las de texto largo
            $poliza        = mb_substr(mb_strtoupper(strval($row[0] ?? '')), 0, 100);
            $tipo_bien     = mb_substr(mb_strtoupper(strval($row[1] ?? '')), 0, 100);
            $tipo_sin      = mb_substr(mb_strtoupper(strval($row[2] ?? '')), 0, 100);
            $anio          = mb_substr(mb_strtoupper(strval($row[3] ?? '')), 0, 20);
            $no_siniestro  = mb_substr(mb_strtoupper(strval($row[5] ?? '')), 0, 100); // <-- CORRECCIÓN AQUÍ
            $despacho      = mb_substr(mb_strtoupper(strval($row[6] ?? '')), 0, 100);
            $aseguradora   = mb_substr(mb_strtoupper(strval($row[7] ?? '')), 0, 100);
            $estatus       = mb_substr(mb_strtoupper(strval($row[8] ?? '')), 0, 100);
            $no_expediente = mb_substr(mb_strtoupper(strval($row[9] ?? '')), 0, 100);
            $tipo_dano     = mb_substr(mb_strtoupper(strval($row[10] ?? '')), 0, 250);
            $reclamo       = mb_substr(mb_strtoupper(strval($row[11] ?? '')), 0, 250);
            $marca         = mb_substr(mb_strtoupper(strval($row[12] ?? '')), 0, 100);
            $modelo        = mb_substr(mb_strtoupper(strval($row[13] ?? '')), 0, 100);
            $serie         = mb_substr(mb_strtoupper(strval($row[14] ?? '')), 0, 100);
            $estatus_tram  = mb_substr(mb_strtoupper(strval($row[15] ?? '')), 0, 100);
            $obs           = mb_strtoupper(strval($row[16] ?? '')); // Observaciones suele ser TEXT, no limitamos
            $tipo_pago     = mb_substr(mb_strtoupper(strval($row[18] ?? '')), 0, 100);
            $comprobante   = mb_substr(mb_strtoupper(strval($row[19] ?? '')), 0, 100);

            $sql = "INSERT INTO inventario_radio (
                poliza, tipo_bien, tipo_siniestro, anio_vigencia,
                fecha_siniestro, no_siniestro, despacho, aseguradora,
                estatus, no_expediente, tipo_dano_reclamacion, reclamo,
                marca, modelo, serie_matricula, estatus_tramite,
                observaciones, importe_convenio, tipo_pago, comprobante_pago
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $poliza, $tipo_bien, $tipo_sin, $anio, $fecha_sin, 
                $no_siniestro, $despacho, $aseguradora, $estatus, 
                $no_expediente, $tipo_dano, $reclamo, $marca, $modelo, 
                $serie, $estatus_tram, $obs, $importe, $tipo_pago, $comprobante
            ]);
            $count++;
        }

        $pdo->commit();
        header("Location: index.php?status=import_success&count=$count");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("ERROR EN FILA " . ($index + 1) . ": " . $e->getMessage());
    }
} else {
    die("SOLICITUD NO VÁLIDA.");
}
