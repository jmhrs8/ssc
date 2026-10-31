<?php
require_once "../../config/conexion.php";
$stmt = $pdo->query("SHOW COLUMNS FROM inventario_armas");
$columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($columnas as $col) {
    if (strpos(strtolower($col), 'obs') !== false) {
        echo "Columna encontrada: " . $col . "<br>";
    }
}
?>
