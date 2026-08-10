<?php
session_start();
require_once "../../config/conexion.php";

// Validación estricta: Solo ADMIN_GENERAL puede vaciar la base
if (!isset($_SESSION['user_id']) || strtoupper(trim($_SESSION['nivel'] ?? '')) !== 'ADMIN_GENERAL') {
    echo "<script>alert('Acceso denegado. Se requiere nivel ADMIN_GENERAL para vaciar la base de datos.'); window.location.href='index.php';</script>";
    exit();
}

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE siniestros_personal;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "<script>alert('¡La base de datos de siniestros se ha vaciado completamente!'); window.location.href='index.php';</script>";
    exit();
} catch (PDOException $e) {
    echo "<script>alert('Error al vaciar la base: " . addslashes($e->getMessage()) . "'); window.location.href='index.php';</script>";
    exit();
}
?>
