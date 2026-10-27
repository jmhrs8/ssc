<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - POS Híbrido</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #1f2937;
            --border: #d1d5db;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: var(--card);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }
        h2 { margin-top: 0; color: #111827; font-size: 24px; }
        .subtitle { font-size: 14px; color: #6b7280; margin-bottom: 24px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 12px; font-size: 15px;
            border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }
        .btn {
            background-color: var(--primary); color: white; border: none;
            width: 100%; padding: 14px; font-size: 16px; font-weight: 600;
            border-radius: 8px; cursor: pointer; transition: background 0.2s;
        }
        .btn:hover { background-color: var(--primary-hover); }
        .alert { background: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 12px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>POS Híbrido</h2>
        <div class="subtitle">Inicie sesión con sus credenciales</div>

        <?php if (isset($error)): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="index.php?route=login/procesar" method="POST">
            <div class="form-group">
                <label>Correo Electrónico:</label>
                <input type="email" name="email" class="form-control" required placeholder="admin@pos.local" autofocus>
            </div>
            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn">Ingresar al Sistema</button>
        </form>
    </div>
</body>
</html>
