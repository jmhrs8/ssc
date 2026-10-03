<?php
session_start(); // Línea obligatoria para que no te mande al login

// 1. Verificación de Seguridad: Sincronizado con index.php
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php?error=acceso_denegado");
    exit();
}

include('config.php');

$mensaje = "";

// 2. Lógica para agregar nuevo tipo de unidad
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar'])) {
    $nuevo_tipo = trim($_POST['tipo_nombre']);
    if (!empty($nuevo_tipo)) {
        // Asumiendo que tienes una tabla llamada 'tipos_unidad'
        $stmt = $conn->prepare("INSERT INTO tipos_unidad (nombre) VALUES (?)");
        $stmt->bind_param("s", $nuevo_tipo);
        if ($stmt->execute()) {
            $mensaje = "<div class='alert alert-success'>Tipo de unidad agregado correctamente.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error: El tipo ya existe.</div>";
        }
    }
}

// 3. Lógica para eliminar tipo
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    $conn->query("DELETE FROM tipos_unidad WHERE id = $id_del");
    header("Location: gestionar_tipos.php?msg=eliminado");
    exit();
}

// Obtener lista de tipos
$tipos = $conn->query("SELECT * FROM tipos_unidad ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Tipos - SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --guinda: #6b1e34; --oro: #b38e5d; }
        
        body { 
            font-family: sans-serif; 
            margin: 0; 
            background-color: #f4f4f4;
            position: relative;
            min-height: 100vh;
        }

        /* FONDO AL 20% DE OPACIDAD */
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('nuevas-patrullas-cdmx.png'); 
            background-size: cover;
            background-position: center;
            opacity: 0.20; 
            z-index: -1;
        }

        .navbar { background-color: var(--guinda); border-bottom: 4px solid var(--oro); }
        .card { border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); background: rgba(255, 255, 255, 0.95); }
        .btn-ssc { background-color: var(--guinda); color: white; }
        .btn-ssc:hover { background-color: #4d1525; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-3 mb-4">
    <a class="navbar-brand" href="index.php">
        <i class="bi bi-arrow-left"></i> VOLVER AL PANEL
    </a>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header btn-ssc text-center"><h5>Administrar Tipos de Unidades</h5></div>
                <div class="card-body">
                    <?php echo $mensaje; ?>
                    <?php if(isset($_GET['msg'])) echo "<div class='alert alert-warning'>Registro eliminado.</div>"; ?>

                    <form method="POST" class="mb-4">
                        <label class="form-label fw-bold">Nuevo Tipo (Ej: Pickup, Sedan, Moto)</label>
                        <div class="input-group">
                            <input type="text" name="tipo_nombre" class="form-control" placeholder="Escriba el tipo..." required>
                            <button type="submit" name="agregar" class="btn btn-ssc">Agregar</button>
                        </div>
                    </form>

                    <table class="table table-hover border">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre del Tipo</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($t = $tipos->fetch_assoc()): ?>
                            <tr>
                                <td class="align-middle"><?php echo strtoupper($t['nombre']); ?></td>
                                <td class="text-center">
                                    <a href="gestionar_tipos.php?delete=<?php echo $t['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('¿Eliminar este tipo?')">
                                        Borrar
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
