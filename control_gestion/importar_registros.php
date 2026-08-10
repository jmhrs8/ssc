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

        // Cachear usuarios para el asignado
        $usuarios_cache = $pdo->query("SELECT id_usuario, nombre_completo FROM usuarios")->fetchAll(PDO::FETCH_KEY_PAIR);

        $sql_main = "INSERT INTO control_gestion
            (numero_oficio, asunto, fecha_recepcion, fecha_oficio, titular, cargo, id_turnado_por, registró, id_estatus, dias_termino, fecha_vencimiento, id_usuario_asignado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
        $stmt_main = $pdo->prepare($sql_main);

        $capturo = $_SESSION['usuario'] ?? 'ADMIN';

        foreach ($rows as $index => $row) {
            if (empty($row[0])) continue;

            $numero_oficio   = trim($row[0]);
            $asunto          = !empty($row[1]) ? mb_strtoupper(trim($row[1]), 'UTF-8') : 'SIN ASUNTO ESPECIFICADO';
            $fecha_recepcion = !empty($row[2]) ? trim($row[2]) : date('Y-m-d');
            $fecha_oficio    = !empty($row[3]) ? trim($row[3]) : date('Y-m-d');
            $titular         = !empty($row[4]) ? mb_strtoupper(trim($row[4]), 'UTF-8') : 'ANÓNIMO';
            $cargo           = !empty($row[5]) ? mb_strtoupper(trim($row[5]), 'UTF-8') : 'PARTICULAR';
            $turnado_por_raw = !empty($row[6]) ? mb_strtoupper(trim($row[6]), 'UTF-8') : '';
            $asignado_raw    = !empty($row[7]) ? trim($row[7]) : '';
            $dias_termino    = !empty($row[8]) ? (int)$row[8] : 5;

            // 1. Mapeo Estricto a los 3 IDs Oficiales de la Base
            if (strpos($turnado_por_raw, 'ASEGURAMIENTO') !== false) {
                $id_turnado_por = 2; // ASEGURAMIENTO DE BIENES
            } elseif (strpos($turnado_por_raw, 'SEGURO') !== false || strpos($turnado_por_raw, 'VIDA') !== false) {
                $id_turnado_por = 3; // JUD. DE SEGUROS DE VIDA
            } else {
                $id_turnado_por = 1; // SUBDIRECCION (Por defecto)
            }

            // 2. Resolver id_usuario_asignado
            $id_asignado = array_search($asignado_raw, $usuarios_cache);
            if ($id_asignado === false) {
                $id_asignado = 1;
            }

            // 3. Calcular fecha de vencimiento
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
