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
            // Saltamos encabezados
            if ($index == 0) continue;

            // Validamos que al menos exista el No. de Expediente (Col 1) o Serie (Col 10)
            if (empty($row[1]) && empty($row[10])) continue;

            $sql = "INSERT INTO inventario_armas (
                no_expediente, folio_sdra, of_recibido, siniestro, tipo_bien, 
                serie, no_siniestro, fecha_registro, fecha_reclamacion, aseguradora, 
                ajustador, n_oficio, estatus, candado_manos, tipo_arma, 
                cargador, cartuchos, cascos, escudos, chalecos, 
                atendio, status_interno, observaciones, fecha_captura, mes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);

            // Tratamiento de fechas para evitar error 1292
            $f_siniestro = (!empty($row[9]) && strtotime($row[9])) ? date('Y-m-d', strtotime($row[9])) : null;
            $f_recla = (!empty($row[13]) && strtotime($row[13])) ? date('Y-m-d', strtotime($row[13])) : null;
            $f_captura = (!empty($row[23]) && strtotime($row[23])) ? date('Y-m-d', strtotime($row[23])) : null;

            $stmt->execute([
                $row[1] ?? '',  // No. Expediente
                $row[2] ?? '',  // Folio SDRA
                $row[3] ?? '',  // Of. Recibido
                $row[4] ?? '',  // Siniestro
                $row[8] ?? '',  // Tipo de Bien (Col I en Excel aprox)
                $row[10] ?? '', // Matricula o Serie
                $row[11] ?? '', // No. Siniestro
                $f_siniestro,   // Fecha de Siniestro (Col L)
                $f_recla,       // Fecha de Reclamación
                $row[14] ?? '', // Aseguradora
                $row[15] ?? '', // Ajustador
                $row[16] ?? '', // N. de Oficio
                $row[17] ?? 'PENDIENTE', // Status
                $row[18] ?? '', // Candado de manos
                $row[19] ?? '', // Armas (tipo_arma)
                $row[20] ?? '', // Cargador
                $row[21] ?? '', // Cartuchos
                $row[22] ?? '', // Cascos
                $row[23] ?? '', // Escudos
                $row[24] ?? '', // Chalecos
                $row[25] ?? '', // Atendió
                $row[26] ?? '', // Status Interno
                $row[27] ?? '', // Observaciones
                $f_captura,     // Fecha de Captura
                $row[29] ?? ''  // Mes
            ]);
            $count++;
        }

        $pdo->commit();
        header("Location: index.php?status=import_success&count=$count");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error Crítico en Excel (Armas): " . $e->getMessage());
    }
}
