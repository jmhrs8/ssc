<?php
require_once "../../config/conexion.php";

// Guardar nueva opción
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nuevo_valor'])) {
    $cat = $_POST['categoria'];
    $val = strtoupper(trim($_POST['nuevo_valor']));
    if (!empty($val)) {
        $ins = $pdo->prepare("INSERT INTO catalogos (categoria, valor) VALUES (?, ?)");
        $ins->execute([$cat, $val]);
        header("Location: catalogos.php?msg=ok");
        exit();
    }
}

// Eliminar opción
if (isset($_GET['del'])) {
    $stmt = $pdo->prepare("DELETE FROM catalogos WHERE id = ?");
    $stmt->execute([$_GET['del']]);
    header("Location: catalogos.php");
    exit();
}

$catalogos = $pdo->query("SELECT * FROM catalogos ORDER BY categoria, valor ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Listados | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f4f7f6; font-size: 11px; text-transform: uppercase; }
        .badge-cat { font-size: 9px; background: #0dcaf0; color: #000; }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-list text-info"></i> GESTIÓN DE LISTADOS DESPLEGABLES</h4>
        <a href="formulario.php" class="btn btn-dark btn-sm"><i class="fas fa-arrow-left"></i> VOLVER AL FORMULARIO</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white fw-bold">AGREGAR NUEVA OPCIÓN</div>
        <div class="card-body">
            <form method="POST" class="row g-2">
                <div class="col-md-5">
                    <label class="fw-bold">CAMPO DEL FORMULARIO:</label>
                    <select name="categoria" class="form-select form-select-sm" required>
                        <option value="TIPO_BIEN">TIPO DE BIEN</option>
                        <option value="TIPO_SINIESTRO">TIPO DE SINIESTRO</option>
                        <option value="ASEGURADORA">ASEGURADORA</option>
                        <option value="DESPACHO">DESPACHO (AJUSTADOR)</option>
                        <option value="ATENDIO">PERSONAL QUE ATENDIÓ</option>
                        <option value="STATUS_TRAMITE">ESTATUS DE TRÁMITE</option>
                        <option value="POLIZA">PÓLIZA</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="fw-bold">VALOR A MOSTRAR:</label>
                    <input type="text" name="nuevo_valor" class="form-control form-control-sm" placeholder="EJ. ABACO AJUSTADORES" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th>CATEGORÍA / CAMPO</th>
                        <th>VALOR</th>
                        <th class="text-center">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($catalogos as $c): ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= htmlspecialchars($c['categoria']) ?></td>
                        <td><?= htmlspecialchars($c['valor']) ?></td>
                        <td class="text-center">
                            <a href="?del=<?= $c['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿ELIMINAR ESTA OPCIÓN?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
