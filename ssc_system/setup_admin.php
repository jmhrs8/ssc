<?php
require_once 'config/db.php';
// Definimos la contraseña
$password_plana = 'admin123'; 
$pass_hash = password_hash($password_plana, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO usuarios (usuario, password, nombre, rol) VALUES ('admin', ?, 'Administrador General', 'admin')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pass_hash]);
    echo "Usuario administrador creado con éxito.\n";
    echo "Usuario: admin\n";
    echo "Password: " . $password_plana . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
