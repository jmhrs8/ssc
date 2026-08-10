<?php
session_start();
require_once '../../config/db.php';
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM siniestros WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) die("Registro no encontrado");
?>
<div class="container mt-5">
    <form action="actualizar.php" method="POST">
        <input type="hidden" name="id" value="<?= $data['id'] ?>">
        <label>Folio</label>
        <input type="text" name="folio" class="form-control" value="<?= $data['folio'] ?>" readonly>
        <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
        <a href="eliminar.php?id=<?= $data['id'] ?>" class="btn btn-danger mt-3" onclick="return confirm('¿Seguro?')">Eliminar Registro</a>
    </form>
</div>
