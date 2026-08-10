<?php
session_start();
require_once "../../config/conexion.php";

if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'admin') {
    die("Acceso denegado");
}

if (isset($_GET['del'])) {
    $id = $_GET['del'];
    
    // Evitar que el admin se borre a sí mismo por accidente
    if ($id == $_SESSION['user_id']) {
        die("<script>alert('No puedes eliminar tu propia cuenta administrativa.'); window.location.href='index.php';</script>");
    }

    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: index.php?msg=deleted");
}
