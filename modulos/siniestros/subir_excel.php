<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../../vendor/autoload.php';
require_once "../../config/conexion.php";
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    header('Content-Type: application/json');
    try {
        $inputFileName = $_FILES['archivo']['tmp_name'];
        $spreadsheet = IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        $pdo->beginTransaction();

        $sql = "INSERT INTO siniestros (
            no_consecutivo, mes, folio, fecha_reporte, hora_reporte, fecha, hora, 
            marca, modelo, tipo, economico_placas, no_inventario, no_serie, adscripcion, 
            nombre_elemento, no_siniestro, taller_asignado, hospital, carp_investigacion, 
            propio, arrendado, aseguradora, declaracion_universal, pase_medicos, pase_taller, 
            graficas, cuadernillo, visto_bueno, fecha_visto_bueno, observaciones, estatus, 
            zona, tipo_siniestro, taller_ingreso, calles, colonia, alcaldia, vehiculo_3ro, 
            placas_3ro, seguro, danos, lesionados, observaciones_generales, 
            fecha_vb_taller, fecha_oficio_recibido, numero_expediente, papeleta_control_gestion
        ) VALUES (
            :no_consecutivo, :mes, :folio, :fecha_reporte, :hora_reporte, :fecha, :hora, 
            :marca, :modelo, :tipo, :economico_placas, :no_inventario, :no_serie, :adscripcion, 
            :nombre_elemento, :no_siniestro, :taller_asignado, :hospital, :carp_investigacion, 
            :propio, :arrendado, :aseguradora, :declaracion_universal, :pase_medicos, :pase_taller, 
            :graficas, :cuadernillo, :visto_bueno, :fecha_visto_bueno, :observaciones, :estatus, 
            :zona, :tipo_siniestro, :taller_ingreso, :calles, :colonia, :alcaldia, :vehiculo_3ro, 
            :placas_3ro, :seguro, :danos, :lesionados, :observaciones_generales, 
            :fecha_vb_taller, :fecha_oficio_recibido, :numero_expediente, :papeleta_control_gestion
        ) ON DUPLICATE KEY UPDATE 
            no_consecutivo = VALUES(no_consecutivo), 
            mes = VALUES(mes), 
            fecha_reporte = VALUES(fecha_reporte), 
            nombre_elemento = VALUES(nombre_elemento), 
            estatus = VALUES(estatus), 
            observaciones_generales = VALUES(observaciones_generales)";

        $stmt = $pdo->prepare($sql);
        $count = 0;

        foreach ($rows as $index => $row) {
            if ($index == 0 || empty($row[2])) continue;

            $parseDate = function($val) {
                if (empty($val)) return null;
                if (is_numeric($val)) {
                    $unixDate = Date::excelToTimestamp($val);
                    return date('Y-m-d', $unixDate);
                }
                return date('Y-m-d', strtotime($val)) ?: null;
            };

            $parseTime = function($val) {
                if (empty($val)) return null;
                if (is_numeric($val)) {
                    $unixTime = Date::excelToTimestamp($val);
                    return date('H:i:s', $unixTime);
                }
                $valStr = strtolower(trim($val));
                $valStr = str_replace(['p.m.', 'pm', 'a.m.', 'am'], ['', '', '', ''], $valStr);
                $valStr = trim($valStr);
                
                $timeSec = strtotime($valStr);
                if ($timeSec === false) return null;
                
                if ((strpos(strtolower($val), 'p.m.') !== false || strpos(strtolower($val), 'pm') !== false)) {
                    $hour = (int)date('H', $timeSec);
                    if ($hour < 12) {
                        $timeSec += 12 * 3600;
                    }
                }
                return date('H:i:s', $timeSec);
            };

            // Función para recortar cadenas al tamaño máximo permitido por columnas específicas
            $limitStr = function($val, $maxLen) {
                if ($val === null) return null;
                return mb_substr(trim($val), 0, $maxLen);
            };

            $stmt->execute([
                ':no_consecutivo'          => $limitStr($row[0] ?? null, 50),
                ':mes'                     => $limitStr($row[1] ?? null, 50),
                ':folio'                   => $limitStr($row[2] ?? null, 50),
                ':fecha_reporte'           => $parseDate($row[3]),
                ':hora_reporte'            => $parseTime($row[4]),
                ':fecha'                   => $parseDate($row[5]),
                ':hora'                    => $parseTime($row[6]),
                ':marca'                   => $limitStr($row[7] ?? null, 100),
                ':modelo'                  => $limitStr($row[8] ?? null, 100),
                ':tipo'                    => $limitStr($row[9] ?? null, 100),
                ':economico_placas'        => $limitStr($row[10] ?? null, 50),
                ':no_inventario'           => $limitStr($row[11] ?? null, 50),
                ':no_serie'                => $limitStr($row[12] ?? null, 100),
                ':adscripcion'             => $limitStr($row[13] ?? null, 150),
                ':nombre_elemento'         => $limitStr($row[14] ?? null, 255),
                ':no_siniestro'            => $limitStr($row[15] ?? null, 100),
                ':taller_asignado'         => $limitStr($row[16] ?? null, 150),
                ':hospital'                => $limitStr($row[17] ?? null, 150), // Protegido contra desbordamiento
                ':carp_investigacion'      => $limitStr($row[18] ?? null, 100),
                ':propio'                  => $limitStr($row[19] ?? null, 50),
                ':arrendado'               => $limitStr($row[20] ?? null, 50),
                ':aseguradora'             => $limitStr($row[21] ?? null, 100),
                ':declaracion_universal'   => $limitStr($row[22] ?? null, 50),
                ':pase_medicos'            => $limitStr($row[23] ?? null, 50),
                ':pase_taller'             => $limitStr($row[24] ?? null, 50),
                ':graficas'                => $limitStr($row[25] ?? null, 5),
                ':cuadernillo'             => $limitStr($row[26] ?? null, 50),
                ':visto_bueno'             => $limitStr($row[27] ?? null, 50),
                ':fecha_visto_bueno'       => $parseDate($row[28]),
                ':observaciones'           => $row[29] ?? null, // Campos largos admiten texto completo
                ':estatus'                 => $limitStr($row[30] ?? null, 100),
                ':zona'                    => $limitStr($row[31] ?? null, 100),
                ':tipo_siniestro'          => $limitStr($row[32] ?? null, 100),
                ':taller_ingreso'          => $limitStr($row[33] ?? null, 150),
                ':calles'                  => $limitStr($row[34] ?? null, 255),
                ':colonia'                 => $limitStr($row[35] ?? null, 150),
                ':alcaldia'                => $limitStr($row[36] ?? null, 150),
                ':vehiculo_3ro'            => $limitStr($row[37] ?? null, 150),
                ':placas_3ro'              => $limitStr($row[38] ?? null, 50),
                ':seguro'                  => $limitStr($row[39] ?? null, 100),
                ':danos'                   => $row[40] ?? null,
                ':lesionados'              => $limitStr($row[41] ?? null, 100),
                ':observaciones_generales' => $row[42] ?? null,
                ':fecha_vb_taller'         => $parseDate($row[43]),
                ':fecha_oficio_recibido'   => $parseDate($row[44]),
                ':numero_expediente'       => $limitStr($row[45] ?? null, 100),
                ':papeleta_control_gestion'=> $limitStr($row[46] ?? null, 100)
            ]);
            $count++;
        }

        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Carga completa: $count registros procesados correctamente."]);
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => "Error al procesar: " . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Carga Masiva</title>
</head>
<body class="bg-light p-4">
<div class="container" style="max-width: 600px;">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">Carga Masiva de Siniestros</div>
        <div class="card-body">
            <form id="formCarga" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-bold">Seleccionar archivo Excel (.xlsx):</label>
                    <input type="file" name="archivo" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Procesar Información</button>
                <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Volver</a>
            </form>
            <div id="resultado" class="mt-3"></div>
        </div>
    </div>
</div>
<script>
document.getElementById('formCarga').onsubmit = async (e) => {
    e.preventDefault();
    const resDiv = document.getElementById('resultado');
    resDiv.innerHTML = '<div class="alert alert-info">Procesando, por favor espere...</div>';
    let formData = new FormData(e.target);
    let response = await fetch('', {method: 'POST', body: formData});
    let data = await response.json();
    resDiv.innerHTML = `<div class="alert ${data.status === 'success' ? 'alert-success' : 'alert-danger'}">${data.message}</div>`;
};
</script>
</body>
</html>
