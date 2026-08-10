<?php
header('Content-Type: application/json');
$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        $stmt = $pdo->prepare("DELETE FROM control_gestion WHERE id_registro = ?");
        $stmt->execute([$id]);
        
        echo json_encode(["status" => "success", "message" => "El folio fue eliminado exitosamente de la base de datos."]);
    } catch(\PDOException $e) {
        echo json_encode(["status" => "error", "message" => "No se pudo borrar el registro: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ID de registro no proporcionado."]);
}
