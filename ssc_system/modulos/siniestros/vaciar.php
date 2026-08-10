<?php
session_start();
require_once '../../config/db.php';
if ($_SESSION['rol'] == 'admin') {
    $pdo->query("TRUNCATE TABLE siniestros"); // Borra TODO y reinicia el contador
    header("Location: index.php?msg=base_vaciada");
}
