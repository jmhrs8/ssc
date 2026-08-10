<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificación de seguridad
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'ADMIN') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit;
}

$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // 1. Desactivar validación de llaves foráneas
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // 2. Vaciar tablas y resetear IDs
    $pdo->exec("TRUNCATE TABLE control_gestion;");
    $pdo->exec("TRUNCATE TABLE soporte_pdf;");
    
    // 3. Reactivar validación
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo json_encode(['status' => 'success', 'message' => 'Base de datos vaciada y folios reiniciados a 1.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
