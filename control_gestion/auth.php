<?php
session_start();
ini_set('display_errors', 0); // Evitar fugas de texto para no romper la cabecera HTTP

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // AUTENTICACIÓN HÍBRIDA (Soporta la encriptación moderna de Juan y la plana anterior de admin)
        if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['usuario']    = $user['username'];
            $_SESSION['nombre']     = $user['nombre_completo'];
            $_SESSION['rol']        = $user['rol'];

            header("Location: index.php");
            exit;
        } else {
            echo "<script>alert('Credenciales Incorrectas o Usuario Inactivo'); window.location='login.php';</script>";
            exit;
        }
    } catch (PDOException $e) {
        die("Error crítico en el Servidor de Acceso: " . $e->getMessage());
    }
}
