<?php
session_start();
if(isset($_SESSION['usuario'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso - Control de Gestión SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            /* Imagen de fondo */
            background-image: url('img/fondo_login.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;

            /* Alineación del contenedor */
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            width: 380px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            /* Un fondo blanco con un toque ligero de transparencia para que se vea elegante con el fondo */
            background-color: rgba(255, 255, 255, 0.95);
        }
        
        /* Estilos optimizados de la firma para el Login */
        .login-footer-firma {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid #861532;
        }
        .firma-avatar {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #861532;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="card p-4">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-dark">SSC CDMX</h4>
        <small class="text-muted">Control de Gestión de la Subdirección de Riesgos y Aseguramiento</small>
    </div>
    <form action="auth.php" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold">Usuario:</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">Contraseña:</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-dark w-100 fw-bold">Ingresar al Sistema</button>
    </form>

    <div class="login-footer-firma text-center">
        <div class="d-flex flex-column align-items-center justify-content-center gap-1">
            <?php 
            $ruta_foto = 'uploads/perfiles/juan_hernandez.jpg';
            if (file_exists($ruta_foto)): ?>
                <img src="<?php echo $ruta_foto; ?>" alt="Ing. Juan Manuel Hernández Lugo" class="firma-avatar mb-1">
            <?php else: ?>
                <div class="firma-avatar d-flex align-items-center justify-content-center bg-secondary text-white fw-bold mb-1" style="font-size:0.85rem; background-color: #6c757d !important;">
                    JM
                </div>
            <?php endif; ?>
            
            <div class="fw-bold text-dark" style="font-size: 0.78rem;">
                Diseño y Programación por el <span style="color: #861532;">Ing. Juan Manuel Hernández Lugo jmhrs8@gmail.com</span>
            </div>
            <div class="text-muted" style="font-size: 0.7rem;">
                &copy; <?php echo date('Y'); ?> SSC - Todos los derechos reservados.
            </div>
        </div>
    </div>
</div>
</body>
</html>
