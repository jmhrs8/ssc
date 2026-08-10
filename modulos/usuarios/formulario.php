<?php
session_start();
require_once "../../config/conexion.php";

if ($_SESSION['nivel'] !== 'admin') { exit("No autorizado"); }

$id = $_GET['id'] ?? null;
$u = ['usuario' => '', 'nombre' => '', 'nivel' => 'capturista', 'modulo_asignado' => 'siniestros'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuario | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><?= $id ? 'Editar Usuario' : 'Registrar Nuevo Usuario' ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="guardar.php" method="POST">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" name="nombre" class="form-control" value="<?= $u['nombre'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nombre de Usuario (Login)</label>
                                <input type="text" name="usuario" class="form-control" value="<?= $u['usuario'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contraseña <?= $id ? '(Dejar en blanco para no cambiar)' : '' ?></label>
                                <input type="password" name="password" class="form-control" <?= $id ? '' : 'required' ?>>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nivel de Acceso</label>
                                    <select name="nivel" class="form-select">
                                        <option value="capturista" <?= $u['nivel']=='capturista'?'selected':'' ?>>Capturista</option>
                                        <option value="admin" <?= $u['nivel']=='admin'?'selected':'' ?>>Administrador</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Módulo Asignado</label>
                                    <select name="modulo_asignado" class="form-select text-uppercase">
                                        <option value="general" <?= $u['modulo_asignado']=='general'?'selected':'' ?>>General (Todos)</option>
                                        <option value="siniestros" <?= $u['modulo_asignado']=='siniestros'?'selected':'' ?>>Siniestros</option>
                                        <option value="armamento" <?= $u['modulo_asignado']=='armamento'?'selected':'' ?>>Armamento</option>
                                        <option value="personal" <?= $u['modulo_asignado']=='personal'?'selected':'' ?>>Personal</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-success">Guardar Usuario</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
