<?php
/**
 * ARCHIVO: subir_excel.php
 * VERSIÓN: FINAL - AJUSTADA A ESTRUCTURA DE 41 COLUMNAS CON FILTRO DE DUPLICADOS EN NO. EXPEDIENTE
 */
require '../../vendor/autoload.php';
require_once "../../config/conexion.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

set_time_limit(0);
ini_set('memory_limit', '1G');

// Procesa fechas de Excel o texto
function normalizarFecha($valor) {
    if (empty($valor) || $valor == '00/00/0000' || $valor == 'S/F' || trim($valor) == '') return null;
    try {
        if (is_numeric($valor)) return date("Y-m-d", Date::excelToTimestamp($valor));
        $timestamp = strtotime(str_replace('/', '-', $valor));
        return ($timestamp) ? date("Y-m-d", $timestamp) : null;
    } catch (Exception $e) { return null; }
}

// Asegura que los campos de cantidad no fallen si vienen vacíos
function limpiarEntero($valor) {
    $v = trim($valor ?? '');
    return (is_numeric($v)) ? intval($v) : 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    header('Content-Type: application/json');
    $index = 0;
    try {
        $spreadsheet = IOFactory::load($_FILES['archivo']['tmp_name']);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        // 1. OBTENEMOS LOS EXPEDIENTES EXISTENTES EN LA BD PARA EVITAR DUPLICADOS
        $stmt_existentes = $pdo->query("SELECT no_expediente FROM inventario_armas WHERE no_expediente IS NOT NULL AND no_expediente != ''");
        $expedientes_db = $stmt_existentes->fetchAll(PDO::FETCH_COLUMN);
        // Creamos un diccionario flip para búsqueda rápida en memoria (O(1))
        $mapa_existentes = array_flip(array_map('trim', array_map('strtoupper', $expedientes_db)));

        $pdo->beginTransaction();
        // SQL_MODE vacío para permitir flexibilidad en inserción
        $pdo->exec("SET sql_mode = ''");

        // Preparamos el INSERT con las columnas exactas de tu DESCRIBE
        $sql = "INSERT INTO inventario_armas (
            poliza, tipo_bien, tipo_siniestro, anio_vigencia, fecha_siniestro_1,
            no_siniestro, despacho, aseguradora, status_seguro, no_expediente,
            tipo_dano, fecha_reclamacion, mes_reclamacion, marca, modelo,
            serie_matricula_1, status_tramite, siniestro_detalle, importe_convenio, tipo_pago,
            comprobante_pago, fecha_acuse, folio_sdra, of_recibido, no_oficio,
            bienes_diversos, candado_manos, armas, cargador, cartuchos,
            cascos, escudos, chalecos, atendio, digitalizado, observaciones_grales
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $pdo->prepare($sql);
        $count = 0;
        $duplicados_omitidos = 0;

        foreach ($rows as $index => $row) {
            if ($index == 0 || empty(array_filter($row))) continue;

            // Extraemos el número de expediente de la celda correspondiente (índice 9 según tu mapeo)
            $expediente_excel = trim((string)($row[9] ?? ''));
            $expediente_mayus = mb_strtoupper($expediente_excel, 'UTF-8');

            // VALIDACIÓN DE DUPLICADOS: Si el expediente ya existe en la BD, lo omitimos
            if (!empty($expediente_mayus) && isset($mapa_existentes[$expediente_mayus])) {
                $duplicados_omitidos++;
                continue; // Salta esta fila y continúa con la siguiente
            }

            // 1. Año: Extraer solo los 4 dígitos (para evitar error en tipo YEAR)
            $anio = preg_match('/(\d{4})/', (string)$row[3], $m) ? intval($m[1]) : null;

            // 2. Importe: Quitar comas de miles si existen para que MySQL lo acepte
            $importe_raw = str_replace(',', '', $row[18] ?? '0');
            $importe = filter_var($importe_raw, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;

            // Mapeo de datos (Asegúrate que el orden coincida con tu Excel)
            $data = [
                mb_strtoupper(trim($row[0] ?? ''), 'UTF-8'),  // poliza
                $row[1], // tipo_bien
                $row[2], // tipo_siniestro
                $anio,   // anio_vigencia
                normalizarFecha($row[4]), // fecha_siniestro_1
                $row[5], // no_siniestro
                $row[6], // despacho
                $row[7], // aseguradora
                $row[8], // status_seguro
                $row[9], // no_expediente
                $row[10], // tipo_dano
                normalizarFecha($row[11]), // fecha_reclamacion
                $row[12], // mes_reclamacion
                $row[13], // marca
                $row[14], // modelo
                $row[15], // serie_matricula_1
                $row[16], // status_tramite
                $row[17], // siniestro_detalle
                $importe, // importe_convenio
                $row[19], // tipo_pago
                $row[20], // comprobante_pago
                normalizarFecha($row[21]), // fecha_acuse
                $row[23], // folio_sdra (el 22 son días, lo saltamos)
                $row[24], // of_recibido
                $row[25], // no_oficio
                $row[26], // bienes_diversos
                limpiarEntero($row[27]), // candado_manos
                limpiarEntero($row[28]), // armas
                limpiarEntero($row[29]), // cargador
                limpiarEntero($row[30]), // cartuchos
                limpiarEntero($row[31]), // cascos
                limpiarEntero($row[32]), // escudos
                limpiarEntero($row[33]), // chalecos
                $row[34], // atendio
                mb_substr(trim($row[35] ?? ''), 0, 10), // digitalizado (respetando VARCHAR 10 si no lo ampliaste)
                $row[36]  // observaciones_grales
            ];

            $stmt->execute($data);
            $count++;

            // Agregamos al mapa local para evitar duplicados si el mismo Excel trae repetidos internos
            if (!empty($expediente_mayus)) {
                $mapa_existentes[$expediente_mayus] = true;
            }
        }

        $pdo->commit();
        
        $mensaje_final = "¡IMPORTACIÓN EXITOSA! SE CARGARON $count REGISTROS NUEVOS.";
        if ($duplicados_omitidos > 0) {
            $mensaje_final .= " (Se omitieron $duplicados_omitidos registros por tener número de expediente duplicado).";
        }

        echo json_encode(["status" => "success", "message" => $mensaje_final]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => "ERROR EN FILA ".($index+1).": ".$e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISTEMA DE INVENTARIOS | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container" style="max-width: 600px;">
        <div class="card shadow">
            <div class="card-header bg-dark text-info text-center">
                <h5 class="mb-0">SUBIR INVENTARIO ACTUALIZADO</h5>
            </div>
            <div class="card-body text-center">
                <div id="res" class="mb-3"></div>
                <form id="f">
                    <input type="file" name="archivo" class="form-control mb-3" required>
                    <button type="submit" id="b" class="btn btn-primary w-100">SUBIR EXCEL</button>
                </form>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('f').onsubmit = function(e) {
        e.preventDefault();
        const b = document.getElementById('b');
        b.disabled = true;
        b.innerText = 'PROCESANDO...';
        fetch('subir_excel.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => {
            b.disabled = false;
            b.innerText = 'SUBIR EXCEL';
            document.getElementById('res').innerHTML = `<div class="alert alert-${res.status==='success'?'success':'danger'}">${res.message}</div>`;
        });
    };
    </script>
</body>
</html>
