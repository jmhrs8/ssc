<?php
require_once "../../config/conexion.php";
$stmt = $pdo->query("DESCRIBE siniestros"); // O SHOW COLUMNS FROM siniestros
$columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($columnas);
