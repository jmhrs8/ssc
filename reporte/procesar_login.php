<?php
session_start();
include('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, usuario, password, rol, nombre_completo FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            
            // Forzamos la creación de la sesión
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['usuario_nombre'] = $user['nombre_completo'];
            
            // Guardar y cerrar sesión para asegurar que se escriba en el disco
            session_write_close();
            
            header("Location: index.php");
            exit();
        }
    }
    header("Location: login.php?error=1");
    exit();
}
