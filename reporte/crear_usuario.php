<?php
session_start();
include('config.php');

// 1. SEGURIDAD: Solo admins pueden crear usuarios
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: login.php"); 
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['usuario']);
    $pass = $_POST['password']; 
    $nombre = trim($_POST['nombre_completo']);
    $rol = $_POST['rol'];

    // Para mantener la compatibilidad con tu procesar_login.php actual, 
    // guardamos el texto plano, pero podrías usar password_hash si prefieres.
    $stmt = $conn->prepare("INSERT INTO usuarios (usuario, password, rol, nombre_completo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user, $pass, $rol, $nombre);
    
    if($stmt->execute()) {
        header("Location: usuarios.php?msg=creado");
    } else {
        $error = "Error: El nombre de usuario ya existe en el sistema.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Usuario - SSC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --guinda: #6b1e34; --oro: #b38e5d; }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            margin: 0; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            background-color: #f4f4f4;
            position: relative;
        }

        /* FONDO AL 20% */
        body::before {
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('nuevas-patrullas-cdmx.png'); 
            background-size: cover; background-position: center;
            opacity: 0.20; z-index: -1;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            border-top: 5px solid var(--guinda);
        }

        h2 { color: var(--guinda); text-align: center; margin-bottom: 25px; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #444; font-size: 14px; }
        
        input, select {
            width: 100%; padding: 10px; border: 1px solid #ccc;
            border-radius: 6px; box-sizing: border-box; font-size: 15px;
        }

        .btn-save {
            background: var(--guinda); color: white; border: none;
            width: 100%; padding: 12px; border-radius: 6px;
            font-weight: bold; cursor: pointer; margin-top: 10px;
            transition: 0.3s;
        }

        .btn-save:hover { background: #4d1525; }
        
        .btn-cancel {
            display: block; text-align: center; margin-top: 15px;
            color: #666; text-decoration: none; font-size: 13px;
        }

        .error-alert {
            background: #f8d7da; color: #721c24; padding: 10px;
            border-radius: 5px; margin-bottom: 15px; font-size: 14px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="card">
    <h2><i class="fas fa-user-plus"></i> Registrar Usuario</h2>

    <?php if(isset($error)) echo "<div class='error-alert'>$error</div>"; ?>

    <form method="POST">
        <div class="form-group">
            <label>ID de Usuario (Login)</label>
            <input type="text" name="usuario" placeholder="Ej: oficial_123" required>
        </div>

        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <div class="form-group">
            <label>Nombre Completo</label>
            <input type="text" name="nombre_completo" placeholder="Nombre y Apellidos" required>
        </div>

        <div class="form-group">
            <label>Rol en el Sistema</label>
            <select name="rol">
                <option value="usuario">Usuario Estándar (Lectura/Escritura)</option>
                <option value="admin">Administrador (Control Total)</option>
            </select>
        </div>

        <button type="submit" class="btn-save">CREAR CUENTA</button>
        <a href="usuarios.php" class="btn-cancel">Cancelar y volver</a>
    </form>
</div>

</body>
</html>
