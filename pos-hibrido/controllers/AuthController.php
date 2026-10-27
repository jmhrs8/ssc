<?php
// controllers/AuthController.php

namespace Controllers;

use Models\Usuario;

class AuthController 
{
    private Usuario $usuarioModel;

    public function __construct() 
    {
        $this->usuarioModel = new Usuario();
    }

    public function mostrarLogin(): void 
    {
        if (isset($_SESSION['usuario_id'])) {
            header('Location: index.php?route=dashboard');
            exit;
        }
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function procesarLogin(): void 
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=login');
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $usuario = $this->usuarioModel->obtenerPorEmail($email);

        // Verificamos contraseña (compatible con hashes bcrypt de la semilla)
        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['rol_nombre']   = $usuario['rol_nombre'];
            $_SESSION['sucursal_id']  = 1; // Por defecto Matriz

            header('Location: index.php?route=dashboard');
            exit;
        } else {
            $error = "Correo o contraseña incorrectos.";
            require_once __DIR__ . '/../views/auth/login.php';
        }
    }

    public function logout(): void 
    {
        session_destroy();
        header('Location: index.php?route=login');
        exit;
    }
}
