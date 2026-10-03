<?php
$host = "localhost";
$user = "root";
$pass = "jmhl2474"; 
$db   = "sistema_reportes";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
