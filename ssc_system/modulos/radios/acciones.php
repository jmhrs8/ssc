<?php
require_once "../../config/conexion.php";

// ELIMINAR INDIVIDUAL
if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM inventario_radio WHERE id = ?");
    $stmt->execute([$_GET['eliminar']]);
    header("Location: index.php?status=deleted");
}

// VACIAR TABLA (TRUNCATE)
if (isset($_GET['cmd']) && $_GET['cmd'] == 'truncate') {
    $pdo->query("TRUNCATE TABLE inventario_radio");
    header("Location: index.php?status=truncated");
}
