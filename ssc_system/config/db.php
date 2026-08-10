<?php
$db_host = 'localhost';
$db_name = 'ssc_inventarios';
$db_user = 'root'; // Cambia según tu config
$db_pass = 'jmhl2474';     // Cambia según tu config

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
