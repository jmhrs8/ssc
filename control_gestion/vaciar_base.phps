<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'ADMIN') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit;
}

$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Vacía de forma segura la correspondencia (TRUNCATE reinicia los ID consecutivos a 1)
    $pdo->exec("TRUNCATE TABLE control_gestion");
    
    echo json_encode(['status' => 'success', 'message' => '¡Base de datos vaciada de correspondencia de forma exitosa!']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al vaciar: ' . $e->getMessage()]);
}
