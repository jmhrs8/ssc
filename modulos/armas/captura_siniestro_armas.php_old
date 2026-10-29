<?php
session_start();
require_once "../../config/conexion.php";

$id = $_GET['id'] ?? null;
if (!$id) die("ID NO PROPORCIONADO");

// Jalamos TODO: lo técnico del inventario y lo que se haya avanzado en la espejo
$stmt = $pdo->prepare("SELECT a.id as id_principal, a.*, e.* FROM inventario_armas a 
                       LEFT JOIN espejo_siniestros_armas e ON a.id = e.id_arma 
                       WHERE a.id = ?");
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$d) die("REGISTRO NO ENCONTRADO");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CAPTURA COMPLETA - REPORTE DE SINIESTRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 12px; background-color: #f8f9fa; text-transform: uppercase; }
        .card-header { background: #000; color: white; font-weight: bold; }
        .section-title { background: #e9ecef; padding: 5px 10px; font-weight: bold; border-left: 4px solid #000; margin-top: 20px; }
        .bg-fixed { background-color: #f1f3f5 !important; font-weight: bold; color: #495057; }
        label { color: #555; margin-bottom: 2px; font-size: 11px; }
    </style>
</head>
<body>
<div class="container mt-4 mb-5">
    <form action="guardar_siniestro_armas_espejo.php" method="POST">
        <input type="hidden" name="id_arma" value="<?= $d['id_principal'] ?>">
        
        <div class="card shadow">
            <div class="card-header text-center">
                FORMULARIO DE CAPTURA Y PREVISUALIZACIÓN DE RECLAMO
            </div>
            <div class="card-body">
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>TIPO DE BIEN (SISTEMA)</label>
                        <input type="text" class="form-control form-control-sm bg-fixed" value="<?= $d['tipo_bien'] ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>EXPEDIENTE / FOLIO</label>
                        <input type="text" class="form-control form-control-sm bg-fixed" value="<?= $d['no_expediente'] ?>" readonly>
                    </div>
                    <div class="col-md-4 text-end">
                        <label>FECHA ELABORACIÓN</label>
                        <input type="text" class="form-control form-control-sm text-center bg-fixed" value="<?= date('d/m/Y') ?>" readonly>
                    </div>
                </div>

                <div class="section-title">DATOS DEL RESGUARDANTE DEL BIEN</div>
                <div class="row g-2 mt-1">
                    <div class="col-md-4">
                        <label>NOMBRE COMPLETO</label>
                        <input type="text" name="nombre_resguardante" class="form-control form-control-sm" value="<?= $d['nombre_resguardante'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label>ADSCRIPCIÓN</label>
                        <input type="text" name="adscripcion" class="form-control form-control-sm" value="<?= $d['adscripcion'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label>GRADO</label>
                        <input type="text" name="grado" class="form-control form-control-sm" value="<?= $d['grado'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label>NO. EMPLEADO</label>
                        <input type="text" name="no_empleado" class="form-control form-control-sm" value="<?= $d['no_empleado'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label>TELÉFONO CONTACTO</label>
                        <input type="text" name="telefono" class="form-control form-control-sm" value="<?= $d['telefono'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label>CORREO ELECTRÓNICO (E-MAIL)</label>
                        <input type="email" name="email" class="form-control form-control-sm" style="text-transform: lowercase;" value="<?= $d['email'] ?? '' ?>">
                    </div>
                </div>

                <div class="section-title">DATOS DEL SINIESTRO</div>
                <div class="row g-2 mt-1">
                    <div class="col-md-4">
                        <label>TIPO DE SINIESTRO</label>
                        <select name="tipo_siniestro" class="form-select form-select-sm">
                            <option value="ROBO" <?= ($d['tipo_siniestro']=='ROBO')?'selected':'' ?>>ROBO</option>
                            <option value="DAÑO" <?= ($d['tipo_siniestro']=='DAÑO')?'selected':'' ?>>DAÑO</option>
                            <option value="EXTRAVÍO" <?= ($d['tipo_siniestro']=='EXTRAVÍO')?'selected':'' ?>>EXTRAVÍO</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>FECHA DEL EVENTO</label>
                        <input type="date" name="fecha_siniestro" class="form-control form-control-sm" value="<?= $d['fecha_siniestro'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label>HORA DE OCURRENCIA</label>
                        <input type="time" name="hora_siniestro" class="form-control form-control-sm" value="<?= $d['hora_siniestro'] ?>">
                    </div>
                    <div class="col-md-12">
                        <label>LUGAR DEL SINIESTRO (DIRECCIÓN COMPLETA)</label>
                        <input type="text" name="lugar_siniestro" class="form-control form-control-sm" value="<?= $d['lugar_siniestro'] ?? '' ?>">
                    </div>
                    <div class="col-md-12">
                        <label>NARRACIÓN DE LOS HECHOS</label>
                        <textarea name="narracion" class="form-control form-control-sm" rows="3"><?= $d['narracion'] ?? '' ?></textarea>
                    </div>
                </div>

                <div class="section-title">DATOS REPORTE A LA ASEGURADORA</div>
                <div class="row g-2 mt-1">
                    <div class="col-md-4">
                        <label>COMPAÑÍA ASEGURADORA</label>
                        <input type="text" name="aseguradora" class="form-control form-control-sm" value="<?= $d['aseguradora'] ?? 'AGROASEMEX S.A.' ?>">
                    </div>
                    <div class="col-md-4">
                        <label>PÓLIZA VIGENTE</label>
                        <input type="text" name="poliza" class="form-control form-control-sm" value="<?= $d['poliza'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label>NO. SINIESTRO ASIGNADO</label>
                        <input type="text" name="no_siniestro_seguro" class="form-control form-control-sm text-primary fw-bold" value="<?= $d['no_siniestro_seguro'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label>DESPACHO AJUSTADOR (DE BASE)</label>
                        <input type="text" class="form-control form-control-sm bg-fixed" value="<?= $d['despacho'] ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>AJUSTADOR / QUIEN RECIBE</label>
                        <input type="text" name="nombre_recibe" class="form-control form-control-sm" value="<?= $d['nombre_recibe'] ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label>FECHA REPORTE</label>
                        <input type="date" name="fecha_reporte" class="form-control form-control-sm" value="<?= $d['fecha_reporte'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label>HORA REPORTE</label>
                        <input type="time" name="hora_reporte" class="form-control form-control-sm" value="<?= $d['hora_reporte'] ?>">
                    </div>
                </div>

                <div class="mt-4 p-3 border-top text-center">
                    <button type="submit" class="btn btn-dark btn-lg shadow">
                         GUARDAR Y GENERAR REPORTE PDF
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>
</body>
</html>
