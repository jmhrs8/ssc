<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 1. Obtener todos los id_registro que están activos actualmente
    $stmt = $pdo->query("SELECT id_registro FROM control_gestion ORDER BY id_registro ASC");
    $activos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $huecos = [];
    $max_actual = 0;

    if (!empty($activos)) {
        $max_actual = max($activos);
        // Construimos el universo ideal desde 1 hasta el máximo actual para hallar los faltantes
        $universo_ideal = range(1, $max_actual);
        $huecos = array_values(array_diff($universo_ideal, $activos));
    }

    // El consecutivo natural siempre será el máximo actual + 1
    $folio_secuencial = $max_actual + 1;

    echo json_encode([
        'status' => 'success',
        'tiene_hueco' => !empty($huecos),
        'folios_borrados' => $huecos, // Mandamos la lista de todos los eliminados
        'folio_secuencial' => $folio_secuencial
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al verificar folios: ' . $e->getMessage()
    ]);
}
