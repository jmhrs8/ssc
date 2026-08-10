<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "PHP está funcionando.";
$pdo = new PDO('mysql:host=localhost;dbname=ssc_inventarios', 'root', 'jmhl2474');
echo " - Conexión a DB exitosa.";
?>
