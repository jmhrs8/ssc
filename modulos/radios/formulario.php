<?php
require_once "../../config/conexion.php";

$id = $_GET['id'] ?? null;
$datos = [
    'poliza' => '', 'tipo_bien' => '', 'tipo_siniestro' => '', 'anio_vigencia' => date('Y'),
    'fecha_siniestro' => '', 'no_siniestro' => '', 'despacho' => '', 'aseguradora' => '',
    'estatus' => '', 'no_expediente' => '', 'tipo_dano_reclamacion' => '', 'reclamo' => '',
    'marca' => '', 'modelo' => '', 'serie_matricula' => '', 'estatus_tramite' => '',
    'observaciones' => '', 'importe_convenio' => '0.00', 'tipo_pago' => '', 'comprobante_pago' => ''
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM inventario_radio WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) $datos = array_merge($datos, $res);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'EDITAR' : 'NUEVO' ?> REGISTRO | RADIOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-size: 12px; text-transform: uppercase; }
        .form-control-sm { text-transform: uppercase; }
        .card-header { background: #1a1a1a; color: white; }
    </style>
</head>
<body>
<div class="container mt-4 mb-5">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between">
            <h6 class="mb-0">FORMULARIO DE RADIOS Y SEMOVIENTES</h6>
            <a href="index.php" class="btn btn-sm btn-outline-light">VOLVER</a>
        </div>
        <div class="card-body">
            <form action="guardar.php" method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">PÓLIZA</label>
                        <input type="text" name="poliza" class="form-control form-control-sm" value="<?= $datos['poliza'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">TIPO DE BIEN</label>
                        <input type="text" name="tipo_bien" class="form-control form-control-sm" value="<?= $datos['tipo_bien'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">TIPO DE SINIESTRO</label>
                        <input type="text" name="tipo_siniestro" class="form-control form-control-sm" value="<?= $datos['tipo_siniestro'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">AÑO/VIGENCIA</label>
                        <input type="text" name="anio_vigencia" class="form-control form-control-sm" value="<?= $datos['anio_vigencia'] ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">FECHA DE SINIESTRO</label>
                        <input type="date" name="fecha_siniestro" class="form-control form-control-sm" value="<?= $datos['fecha_siniestro'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">No. SINIESTRO</label>
                        <input type="text" name="no_siniestro" class="form-control form-control-sm" value="<?= $datos['no_siniestro'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">DESPACHO</label>
                        <input type="text" name="despacho" class="form-control form-control-sm" value="<?= $datos['despacho'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">ASEGURADORA</label>
                        <input type="text" name="aseguradora" class="form-control form-control-sm" value="<?= $datos['aseguradora'] ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">ESTATUS</label>
                        <input type="text" name="estatus" class="form-control form-control-sm" value="<?= $datos['estatus'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">No. EXPEDIENTE</label>
                        <input type="text" name="no_expediente" class="form-control form-control-sm" value="<?= $datos['no_expediente'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">TIPO DE DAÑO O RECLAMACIÓN</label>
                        <input type="text" name="tipo_dano_reclamacion" class="form-control form-control-sm" value="<?= $datos['tipo_dano_reclamacion'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">RECLAMO</label>
                        <input type="text" name="reclamo" class="form-control form-control-sm" value="<?= $datos['reclamo'] ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">MARCA</label>
                        <input type="text" name="marca" class="form-control form-control-sm" value="<?= $datos['marca'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">MODELO</label>
                        <input type="text" name="modelo" class="form-control form-control-sm" value="<?= $datos['modelo'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">SERIE/MATRICULA</label>
                        <input type="text" name="serie_matricula" class="form-control form-control-sm" value="<?= $datos['serie_matricula'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">ESTATUS DE TRAMITE</label>
                        <input type="text" name="estatus_tramite" class="form-control form-control-sm" value="<?= $datos['estatus_tramite'] ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">OBSERVACIONES</label>
                        <textarea name="observaciones" class="form-control form-control-sm" rows="2"><?= $datos['observaciones'] ?></textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">IMPORTE DEL CONVENIO</label>
                        <input type="number" step="0.01" name="importe_convenio" class="form-control form-control-sm" value="<?= $datos['importe_convenio'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">TIPO DE PAGO</label>
                        <input type="text" name="tipo_pago" class="form-control form-control-sm" value="<?= $datos['tipo_pago'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">COMPROBANTE</label>
                        <input type="text" name="comprobante_pago" class="form-control form-control-sm" value="<?= $datos['comprobante_pago'] ?>">
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-dark px-5 shadow"><i class="fas fa-save"></i> GUARDAR REGISTRO</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
