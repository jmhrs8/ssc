<?php
session_start();
require_once '../../config/db.php';
if ($_SESSION['rol'] == 'admin') {
    $stmt = $pdo->prepare("DELETE FROM siniestros WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}
header("Location: index.php");
