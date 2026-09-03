<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Si ya hay sesión activa, redirigir al Dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_rol'] = $user['rol'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas. Verifique correo y contraseña.';
        }
    } else {
        $error = 'Por favor complete todos los campos.';
    }
}

// Obtener fondo de pantalla de la configuración
$stmtCfg = $pdo->query("SELECT logo_url, bg_url FROM configuracion LIMIT 1");
$cfg = $stmtCfg->fetch();
$bgUrl = !empty($cfg['bg_url']) ? $cfg['bg_url'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            <?php if ($bgUrl): ?>
            background: url('<?= $bgUrl ?>') no-repeat center center fixed;
            background-size: cover;
            <?php else: ?>
            background-color: #f8f9fa;
            <?php endif; ?>
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
        }
    </style>
</head>
<body>
<div class="card login-card shadow-lg p-4">
    <div class="text-center mb-3">
        <?php if (!empty($cfg['logo_url'])): ?>
            <img src="<?= htmlspecialchars($cfg['logo_url']) ?>" style="max-height: 80px;" class="mb-2">
        <?php endif; ?>
        <h4 class="fw-bold">Sistema de Inventario y Control de Insumos</h4>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
    </form>
</div>
</body>
</html>
