<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - SSC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --guinda: #6b1e34; 
            --oro: #b38e5d; 
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            background-color: #f4f4f4;
            position: relative;
            overflow: hidden;
        }

        /* CAPA DE FONDO AL 20% DE OPACIDAD */
        body::before {
            content: "";
            position: fixed;
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            /* Imagen de patrullas en formato .png */
            background-image: url('nuevas-patrullas-cdmx.png'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.20; /* 20% de transparencia */
            z-index: -1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 380px;
            text-align: center;
            border-top: 6px solid var(--guinda);
            position: relative;
            z-index: 1;
        }

        .login-card img {
            width: 180px;
            margin-bottom: 20px;
        }

        .login-card h2 { 
            color: var(--guinda); 
            margin-bottom: 10px; 
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--guinda);
        }

        .form-control { 
            width: 100%; 
            padding: 12px 12px 12px 40px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--oro);
        }

        .btn-login { 
            background: var(--guinda); 
            color: white; 
            border: none; 
            padding: 14px; 
            width: 100%; 
            border-radius: 8px; 
            font-weight: bold; 
            font-size: 16px;
            cursor: pointer; 
            transition: background 0.3s;
            text-transform: uppercase;
        }

        .btn-login:hover { 
            background: #4d1525; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .error-msg { 
            background: #f8d7da;
            color: #721c24; 
            padding: 10px;
            border-radius: 5px;
            font-size: 14px; 
            margin-bottom: 20px; 
            border: 1px solid #f5c6cb;
        }

        .footer-text {
            margin-top: 25px;
            font-size: 11px;
            color: #888;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <h2>REPORTE FOTOGRÁFICO DE SINIESTROS</h2>
        <h8>SUBDIR DE RIESGOS Y ASEGURAMIENTO</h8>
        <p>Ingrese sus credenciales para acceder</p>

        <?php if(isset($_GET['error'])): ?>
            <div class='error-msg'>
                <i class="fas fa-exclamation-circle"></i> Usuario o contraseña incorrectos
            </div>
        <?php endif; ?>

        <form action="procesar_login.php" method="POST">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="usuario" class="form-control" placeholder="Usuario" required autofocus>
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

        <div class="footer-text">
            SECRETARÍA DE SEGURIDAD CIUDADANA <br>
            Ciudad de México - 2026
           Sistema diseñado por el Ing. Juan Manuel Hernandez Lugo
          jmhrs8@gmail.com 
        </div>
    </div>

</body>
</html>
