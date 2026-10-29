<?php
require_once "../../config/conexion.php";
$stmt = $pdo->query("SHOW COLUMNS FROM espejo_siniestros_armas");
$columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($columnas);
echo "</pre>";
?>
