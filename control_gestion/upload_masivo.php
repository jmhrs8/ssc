<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Solo permitir que administradores ejecuten la carga masiva
if(!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'ADMIN') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Se requieren permisos de Administrador."]);
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    die(json_encode(["status" => "error", "message" => "Error de conexión: " . $e->getMessage()]));
}

// Se unificó a 'archivo_excel' que es el nombre que manda el formulario de tu index.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['archivo_excel']) || isset($_FILES['excel_file']))) {
    $fileInputName = isset($_FILES['archivo_excel']) ? 'archivo_excel' : 'excel_file';
    $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($fileTmpPath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Remover la fila 1 de encabezados
        $headers = array_shift($rows);

        $insertados = 0;
        $duplicados = 0;
        $errores_detalle = [];

        // Cachear catálogos para optimizar tiempos de ejecución
        $areas_cache = $pdo->query("SELECT id_area, nombre_area FROM catalogo_areas")->fetchAll(PDO::FETCH_KEY_PAIR);
        $usuarios_cache = $pdo->query("SELECT id_usuario, nombre_completo FROM usuarios")->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmt_area = $pdo->prepare("INSERT INTO catalogo_areas (nombre_area) VALUES (?)");

        // Tu consulta SQL original operativa al 100%
        $sql_main = "INSERT INTO control_gestion
            (numero_oficio, asunto, fecha_recepcion, fecha_oficio, titular, cargo, id_turnado_por, registró, id_estatus, dias_termino, fecha_vencimiento, id_usuario_asignado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
        $stmt_main = $pdo->prepare($sql_main);

        $capturo = $_SESSION['usuario'] ?? 'ADMIN';

        foreach ($rows as $index => $row) {
            // Validar que la fila tenga número de oficio
            if (empty($row[0])) continue;

            /* MAPEO DE COLUMNAS DEL EXCEL V3 GENERADO:
               Column 0: Número de Oficio
               Column 1: Asunto
               Column 2: Fecha de Recepción (YYYY-MM-DD)
               Column 3: Fecha del Oficio (YYYY-MM-DD)
               Column 4: Titular
               Column 5: Cargo
               Column 6: Área que Turna (Por defecto 'DAAA')
               Column 7: Funcionario Asignado (Vacio/Default)
               Column 8: Días de término
            */

            $numero_oficio   = trim($row[0]);
            $asunto          = !empty($row[1]) ? mb_strtoupper(trim($row[1]), 'UTF-8') : 'SIN ASUNTO ESPECIFICADO';
            $fecha_recepcion = !empty($row[2]) ? trim($row[2]) : date('Y-m-d');
            $fecha_oficio    = !empty($row[3]) ? trim($row[3]) : date('Y-m-d');
            $titular         = !empty($row[4]) ? mb_strtoupper(trim($row[4]), 'UTF-8') : 'ANÓNIMO';
            $cargo           = !empty($row[5]) ? mb_strtoupper(trim($row[5]), 'UTF-8') : 'PARTICULAR';
            $turnado_por_raw = !empty($row[6]) ? trim($row[6]) : 'DAAA';
            $asignado_raw    = !empty($row[7]) ? trim($row[7]) : '';
            $dias_termino    = !empty($row[8]) ? (int)$row[8] : 5;

            // 1. Resolver id_turnado_por
            $id_turnado_por = array_search($turnado_por_raw, $areas_cache);
            if ($id_turnado_por === false && !empty($turnado_por_raw)) {
                $stmt_area->execute([$turnado_por_raw]);
                $id_turnado_por = $pdo->lastInsertId();
                $areas_cache[$id_turnado_por] = $turnado_por_raw;
            } elseif (empty($turnado_por_raw)) {
                $id_turnado_por = 1;
            }

            // 2. Resolver id_usuario_asignado
            $id_asignado = array_search($asignado_raw, $usuarios_cache);
            if ($id_asignado === false) {
                $id_asignado = 1; // ID por defecto asignado seguro
            }

            // 3. Calcular fecha de vencimiento basada en la recepción
            $fecha_vencimiento = date('Y-m-d', strtotime($fecha_recepcion . ' + ' . $dias_termino . ' days'));

            try {
                $stmt_main->execute([
                    $numero_oficio,
                    $asunto,
                    $fecha_recepcion,
                    $fecha_oficio,
                    $titular,
                    $cargo,
                    $id_turnado_por,
                    $capturo,
                    $dias_termino,
                    $fecha_vencimiento,
                    $id_asignado
                ]);
                $insertados++;
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    $duplicados++;
                    $errores_detalle[] = "Línea " . ($index+2) . ": Omitido por Oficio Duplicado ($numero_oficio)";
                } else {
                    $errores_detalle[] = "Línea " . ($index+2) . ": Error operativo - " . $e->getMessage();
                }
            }
        }

        echo json_encode([
            "status" => "success",
            "message" => "Procesamiento de plantilla Excel completado con éxito.",
            "insertados" => $insertados,
            "duplicados_omitidos" => $duplicados,
            "detalles" => $errores_detalle
        ]);

    } catch (\Exception $e) {
        echo json_encode(["status" => "error", "message" => "Error al analizar el Excel: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No se recibió un archivo de Excel válido o la clave del input es incorrecta."]);
}
