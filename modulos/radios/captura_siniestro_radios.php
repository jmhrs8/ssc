<?php
session_start();
require_once "../../config/conexion.php";

$id = $_GET['id'] ?? null;
if (!$id) die("ID NO PROPORCIONADO");

$stmt = $pdo->prepare("SELECT r.*, e.* FROM inventario_radio r LEFT JOIN espejo_siniestros_radios e ON r.id = e.id_radio WHERE r.id = ?");
$stmt->execute([$id]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) die("REGISTRO DE RADIO NO ENCONTRADO");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CAPTURA COMPLETA - RADIOS 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 11px; background-color: #f4f6f9; }
        input, textarea { text-transform: uppercase; }
        .seccion-titulo { background: #212529; color: white; padding: 5px 10px; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>
<div class="container mt-3 mb-5">
    <div class="card shadow">
        <div class="card-header bg-dark text-white text-center fw-bold">FORMULARIO DE SINIESTRO INSTITUCIONAL</div>
        <div class="card-body">
            <form action="guardar_siniestro_radios_espejo.php" method="POST">
                <input type="hidden" name="id_radio" value="<?= $datos['id'] ?>">

                <div class="row g-2">
                    <div class="col-md-6"><label class="fw-bold">EXPEDIENTE:</label><input type="text" class="form-control form-control-sm bg-light" value="<?= $datos['no_expediente'] ?>" readonly></div>
                    <div class="col-md-6"><label class="fw-bold">FECHA ELABORACIÓN:</label><input type="date" name="fecha_elaboracion" class="form-control form-control-sm" value="<?= $datos['fecha_elaboracion'] ?? date('Y-m-d') ?>"></div>
                </div>

                <div class="seccion-titulo">DATOS DEL RESGUARDANTE DEL BIEN</div>
                <div class="row g-2 mt-1">
                    <div class="col-md-6"><label>NOMBRE:</label><input type="text" name="nombre_resguardante" class="form-control form-control-sm" value="<?= strtoupper($datos['nombre_resguardante'] ?? $datos['atendio'] ?? '') ?>" required></div>
                    <div class="col-md-6"><label>ADSCRIPCIÓN:</label><input type="text" name="adscripcion" class="form-control form-control-sm" value="<?= strtoupper($datos['adscripcion'] ?? '') ?>"></div>
                    <div class="col-md-3"><label>GRADO:</label><input type="text" name="grado" class="form-control form-control-sm" value="<?= strtoupper($datos['grado'] ?? '') ?>"></div>
                    <div class="col-md-3"><label>NO. EMPLEADO:</label><input type="text" name="no_empleado" class="form-control form-control-sm" value="<?= $datos['no_empleado'] ?? '' ?>"></div>
                    <div class="col-md-3"><label>TELÉFONO:</label><input type="text" name="telefono" class="form-control form-control-sm" value="<?= $datos['telefono'] ?? '' ?>"></div>
                    <div class="col-md-3"><label>E-MAIL:</label><input type="email" name="email" class="form-control form-control-sm" style="text-transform: lowercase;" value="<?= strtolower($datos['email'] ?? '') ?>"></div>
                </div>

                <div class="seccion-titulo">DATOS DEL SINIESTRO</div>
                <div class="row g-2 mt-1">
                    <div class="col-md-4"><label>TIPO DE SINIESTRO:</label><input type="text" name="tipo_siniestro" class="form-control form-control-sm" value="<?= strtoupper($datos['tipo_siniestro'] ?? '') ?>" required></div>
                    <div class="col-md-4"><label>FECHA SINIESTRO:</label><input type="date" name="fecha_siniestro" class="form-control form-control-sm" value="<?= $datos['fecha_siniestro'] ?? '' ?>" required></div>
                    <div class="col-md-4"><label>HORA OCURRENCIA:</label><input type="time" name="hora_siniestro" class="form-control form-control-sm" value="<?= $datos['hora_siniestro'] ?? '' ?>" required></div>
                    <div class="col-md-12"><label>LUGAR DEL SINIESTRO:</label><input type="text" name="lugar_siniestro" class="form-control form-control-sm" value="<?= strtoupper($datos['lugar_siniestro'] ?? '') ?>" required></div>
                    <div class="col-md-12"><label>NARRACIÓN SINIESTRO:</label><textarea name="narracion" class="form-control form-control-sm" rows="3" required><?= strtoupper($datos['narracion'] ?? '') ?></textarea></div>
                </div>

                <div class="seccion-titulo">DATOS REPORTE A LA ASEGURADORA</div>
                <div class="row g-2 mt-1">
                    <div class="col-md-4"><label>ASEGURADORA:</label><input type="text" name="aseguradora" class="form-control form-control-sm" value="<?= strtoupper($datos['aseguradora'] ?? 'SEGUROS AGROASEMEX') ?>"></div>
                    <div class="col-md-4"><label>PÓLIZA:</label><input type="text" name="poliza" class="form-control form-control-sm" value="<?= strtoupper($datos['poliza'] ?? '') ?>"></div>
                    <div class="col-md-4"><label>NO. SINIESTRO ASIGNADO:</label><input type="text" name="no_siniestro_seguro" class="form-control form-control-sm" value="<?= strtoupper($datos['no_siniestro_seguro'] ?? '') ?>"></div>
                    <div class="col-md-4"><label>FECHA REPORTE:</label><input type="date" name="fecha_reporte" class="form-control form-control-sm" value="<?= $datos['fecha_reporte'] ?? '' ?>"></div>
                    <div class="col-md-4"><label>HORA REPORTE:</label><input type="time" name="hora_reporte" class="form-control form-control-sm" value="<?= $datos['hora_reporte'] ?? '' ?>"></div>
                    <div class="col-md-4"><label>RECIBE REPORTE:</label><input type="text" name="nombre_recibe" class="form-control form-control-sm" value="<?= strtoupper($datos['nombre_recibe'] ?? 'ERIKA REYES ROCHA') ?>"></div>
                    <div class="col-md-12"><label>DESPACHO AJUSTADOR:</label><input type="text" name="despacho_ajustador" class="form-control form-control-sm" value="<?= strtoupper($datos['despacho_ajustador'] ?? '') ?>"></div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-danger btn-sm px-5 fw-bold">GUARDAR Y GENERAR REPORTE PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
