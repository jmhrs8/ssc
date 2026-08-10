<?php
require_once "../../config/conexion.php";

// 1. Lógica para VACIAR TODO EL MÓDULO (Punto 11)
if (isset($_GET['cmd']) && $_GET['cmd'] === 'truncate') {
    try {
        // Desactivar revisión de llaves foráneas temporalmente para limpieza profunda
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $sql = "TRUNCATE TABLE siniestros";
        $pdo->exec($sql);
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        // Redirigir al index con mensaje de éxito
        header("Location: index.php?status=vaciado_exitoso");
    } catch (PDOException $e) {
        die("Error al vaciar la base de datos: " . $e->getMessage());
    }
    exit;
}

// 2. Lógica para ELIMINAR UN SOLO REGISTRO (Punto 13)
if (isset($_GET['eliminar'])) {
    try {
        $id = $_GET['eliminar'];
        $stmt = $pdo->prepare("DELETE FROM siniestros WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: index.php?status=registro_eliminado");
    } catch (PDOException $e) {
        die("Error al eliminar el registro: " . $e->getMessage());
    }
    exit;
}

// Si se accede al archivo sin parámetros, regresar al index
header("Location: index.php");
exit;
