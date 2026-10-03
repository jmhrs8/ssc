<?php
include('config.php');
$user = 'admin';
$pass = password_hash('admin123', PASSWORD_BCRYPT);
$rol = 'admin';
$nombre = 'Administrador General';

$conn->query("DELETE FROM usuarios WHERE usuario='$user'");
$sql = "INSERT INTO usuarios (usuario, password, rol, nombre_completo) VALUES ('$user', '$pass', '$rol', '$nombre')";

if ($conn->query($sql)) {
    echo "<h1>Usuario 'admin' creado/reseteado con éxito.</h1>";
    echo "<p>Intenta loguearte ahora con: <b>admin</b> y contraseña: <b>admin123</b></p>";
} else {
    echo "Error: " . $conn->error;
}
?>
