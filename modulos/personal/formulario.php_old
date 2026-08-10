<?php
require_once "../../config/conexion.php";

$id = $_GET['id'] ?? null;
$datos = null;

// Si existe ID, buscamos los datos para editar
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM personal WHERE id = ?");
    $stmt->execute([$id]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'MODIFICAR' : 'NUEVO' ?> REGISTRO | PERSONAL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-size: 12px; background-color: #f4f6f9; text-transform: uppercase; }
        .card { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header-form { background: #1a1a1a; color: white; padding: 15px; border-radius: 8px 8px 0 0; }
        label { font-weight: bold; color: #555; margin-bottom: 5px; }
        input, select, textarea { text-transform: uppercase; font-size: 12px !important; }
    </style>
</head>
<body>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="index.php" class="btn btn-dark btn-sm"><i class="fas fa-arrow-left"></i> REGRESAR AL LISTADO</a>
            </div>

            <div class="card">
                <div class="header-form">
                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> <?= $id ? 'MODIFICAR DATOS DEL PERSONAL' : 'REGISTRAR NUEVO PERSONAL' ?></h5>
                </div>
                <div class="card-body p-4">
                    <form action="acciones.php" method="POST">
                        <input type="hidden" name="id" value="<?= $datos['id'] ?? '' ?>">
                        <input type="hidden" name="accion" value="<?= $id ? 'editar' : 'guardar' ?>">

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>APELLIDO PATERNO</label>
                                <input type="text" name="apellido_paterno" class="form-control" value="<?= $datos['apellido_paterno'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>APELLIDO MATERNO</label>
                                <input type="text" name="apellido_materno" class="form-control" value="<?= $datos['apellido_materno'] ?? '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>NOMBRE(S)</label>
                                <input type="text" name="nombre" class="form-control" value="<?= $datos['nombre'] ?? '' ?>" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>R.F.C.</label>
                                <input type="text" name="rfc" class="form-control fw-bold text-primary" maxlength="13" value="<?= $datos['rfc'] ?? '' ?>" required>
                            </div>

                            <div class="col-md-8 mb-3">
                                <label>ÁREA DE ADSCRIPCIÓN</label>
                                <input type="text" name="area_adscripcion" class="form-control" value="<?= $datos['area_adscripcion'] ?? '' ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>PUESTO</label>
                                <input type="text" name="puesto" class="form-control" value="<?= $datos['puesto'] ?? '' ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>TIPO DE CONTRATACIÓN</label>
                                <select name="tipo_contratacion" class="form-select" required>
                                    <option value="">-- SELECCIONE --</option>
                                    <option value="BASE" <?= (isset($datos['tipo_contratacion']) && $datos['tipo_contratacion'] == 'BASE') ? 'selected' : '' ?>>BASE</option>
                                    <option value="EVENTUAL" <?= (isset($datos['tipo_contratacion']) && $datos['tipo_contratacion'] == 'EVENTUAL') ? 'selected' : '' ?>>EVENTUAL</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>DESCRIPCIÓN DEL TRABAJO QUE REALIZA EN LA VÍA PÚBLICA</label>
                                <textarea name="descripcion_via_publica" class="form-control" rows="2"><?= $datos['descripcion_via_publica'] ?? '' ?></textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>FECHA DE ALTA</label>
                                <input type="date" name="fecha_alta" class="form-control" value="<?= $datos['fecha_alta'] ?? '' ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>QUINCENA</label>
                                <input type="text" name="quincena" class="form-control" placeholder="EJ. 2026-08" value="<?= $datos['quincena'] ?? '' ?>">
                            </div>
                        </div>

                        <hr>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-5 fw-bold">
                                <i class="fas fa-save"></i> <?= $id ? 'ACTUALIZAR DATOS' : 'GUARDAR REGISTRO' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Forzar mayúsculas en tiempo real
    document.querySelectorAll('input[type=text], textarea').forEach(el => {
        el.addEventListener('input', e => {
            e.target.value = e.target.value.toUpperCase();
        });
    });
</script>
</body>
</html>
