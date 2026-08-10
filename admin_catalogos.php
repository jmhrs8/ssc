<?php
session_start();
require_once 'config/db.php';
if ($_SESSION['rol'] !== 'admin') { die("Acceso denegado."); }

// Insertar nuevo elemento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar'])) {
    $cat = $_POST['categoria'];
    $val = $_POST['valor'];
    $mod = $_POST['modulo'];
    $stmt = $pdo->prepare("INSERT INTO catalogos (categoria, valor, modulo) VALUES (?, ?, ?)");
    $stmt->execute([$cat, $val, $mod]);
}

// Eliminar elemento
if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM catalogos WHERE id = ?")->execute([$_GET['del']]);
    header("Location: admin_catalogos.php");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Catálogos - SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="d-flex justify-content-between">
            <h3>Gestión de Catálogos Operativos</h3>
            <a href="index.php" class="btn btn-secondary">Volver al Panel</a>
        </div>
        
        <div class="card p-4 shadow-sm mt-3">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label>Módulo</label>
                    <select name="modulo" class="form-select" required>
                        <option value="siniestros">Siniestros</option>
                        <option value="radios">Radios</option>
                        <option value="armas">Armamento</option>
                        <option value="personal">Personal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Categoría</label>
                    <select name="categoria" class="form-select" required>
                        <option value="marca">Marca</option>
                        <option value="modelo">Modelo</option>
                        <option value="tipo">Tipo de Bien</option>
                        <option value="cuadernillo">Cuadernillo</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Nuevo Valor (Ej: FORD, MOTOROLA, etc)</label>
                    <input type="text" name="valor" class="form-control" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="agregar" class="btn btn-primary w-100">Agregar</button>
                </div>
            </form>
        </div>

        <div class="mt-4">
            <table class="table table-white table-hover shadow-sm">
                <thead class="table-dark">
                    <tr><th>Módulo</th><th>Categoría</th><th>Valor</th><th>Acción</th></tr>
                </thead>
                <tbody>
                    <?php
                    $items = $pdo->query("SELECT * FROM catalogos ORDER BY modulo, categoria, valor ASC")->fetchAll();
                    foreach($items as $i): ?>
                    <tr>
                        <td><?= strtoupper($i['modulo']) ?></td>
                        <td><?= strtoupper($i['categoria']) ?></td>
                        <td><?= $i['valor'] ?></td>
                        <td><a href="?del=<?= $i['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">Borrar</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
