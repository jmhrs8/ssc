<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Validamos seguridad básica de sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["success" => false, "message" => "No autorizado", "siguiente" => "00001"]);
    exit;
}

$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Buscamos el ID numérico más alto para calcular el verdadero consecutivo
    $stmt = $pdo->query("SELECT MAX(id_registro) AS ultimo_id FROM control_gestion");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si hay registros sumamos 1, si la tabla está vacía empezamos en 1
    $siguiente_numero = ($resultado['ultimo_id']) ? intval($resultado['ultimo_id']) + 1 : 1;
    
    // Formateamos a 5 dígitos fijos (Ej: 00001, 00002, 00015...)
    $siguiente_folio = str_pad($siguiente_numero, 5, "0", STR_PAD_LEFT);

    // Si prefieres que el folio ya incluya prefijo institucional e instrucciones, puedes descomentar la línea de abajo:
    // $siguiente_folio = "SSC-CG-" . str_pad($siguiente_numero, 5, "0", STR_PAD_LEFT) . "/" . date('Y');

    echo json_encode([
        "success" => true, 
        "siguiente" => $siguiente_folio
    ]);

} catch (Exception $e) {
    // Failsafe: En caso de error en la BD, devolvemos el consecutivo inicial para no romper el flujo
    echo json_encode([
        "success" => false, 
        "siguiente" => "00001", 
        "error" => $e->getMessage()
    ]);
}
?>
