<?php
session_start();
require_once "../../config/conexion.php";

$id_arma = $_GET['id'] ?? null;

// Consultamos los datos técnicos que ya existen
$stmt = $pdo->prepare("SELECT * FROM inventario_armas WHERE id = ?");
$stmt->execute([$id_arma]);
$arma = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$arma) { die("Registro no encontrado."); }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Seguimiento de Siniestro</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <style>
        .section-title { background: #f8f9fa; padding: 10px; border-left: 5px solid #343a40; margin-bottom: 20px; }
    </style>
</head>
<body class="container mt-4 mb-5">
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between">
            <h4>Seguimiento de Siniestro: <?php echo $arma['no_expediente'] ?? 'Nuevo'; ?></h4>
            <span class="badge badge-info align-self-center"><?php echo $arma['marca'] . " " . $arma['serie_matricula_1']; ?></span>
        </div>
        <div class="card-body">
            <form action="guardar_siniestro_armas_espejo.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $id_arma; ?>">

                <div class="section-title"><h5>1. Información de la Póliza y Siniestro</h5></div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Póliza:</label>
                        <input type="text" name="poliza" class="form-control" value="<?php echo $arma['poliza']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Tipo de Bien:</label>
                        <input type="text" name="tipo_bien" class="form-control" value="<?php echo $arma['tipo_bien']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Tipo de Siniestro:</label>
                        <select name="tipo_siniestro" class="form-control">
                            <option value="ROBO" <?php echo ($arma['tipo_siniestro']=='ROBO')?'selected':''; ?>>ROBO</option>
                            <option value="EXTRAVÍO" <?php echo ($arma['tipo_siniestro']=='EXTRAVÍO')?'selected':''; ?>>EXTRAVÍO</option>
                            <option value="DAÑO" <?php echo ($arma['tipo_siniestro']=='DAÑO')?'selected':''; ?>>DAÑO</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Año Vigencia:</label>
                        <input type="number" name="anio_vigencia" class="form-control" value="<?php echo $arma['anio_vigencia']; ?>" placeholder="2024">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Fecha Siniestro:</label>
                        <input type="date" name="fecha_siniestro_1" class="form-control" value="<?php echo $arma['fecha_siniestro_1']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>No. Siniestro (Seguro):</label>
                        <input type="text" name="no_siniestro" class="form-control" value="<?php echo $arma['no_siniestro']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Despacho / Ajustador:</label>
                        <input type="text" name="despacho" class="form-control" value="<?php echo $arma['despacho']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Aseguradora:</label>
                        <input type="text" name="aseguradora" class="form-control" value="<?php echo $arma['aseguradora']; ?>">
                    </div>
                </div>

                <div class="section-title"><h5>2. Seguimiento Administrativo</h5></div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Estatus Seguro:</label>
                        <input type="text" name="status_seguro" class="form-control" value="<?php echo $arma['status_seguro']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>No. Expediente:</label>
                        <input type="text" name="no_expediente" class="form-control" value="<?php echo $arma['no_expediente']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Fecha Reclamación:</label>
                        <input type="date" name="fecha_reclamacion" class="form-control" value="<?php echo $arma['fecha_reclamacion']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Mes:</label>
                        <input type="text" name="mes" class="form-control" value="<?php echo $arma['mes']; ?>">
                    </div>
                </div>

                <div class="section-title"><h5>3. Inventario de Bienes Siniestrados</h5></div>
                <div class="row mb-3 text-center">
                    <div class="col-md-2"><label>Candados</label><input type="number" name="candado_manos" class="form-control" value="<?php echo $arma['candado_manos']; ?>"></div>
                    <div class="col-md-2"><label>Armas</label><input type="number" name="armas" class="form-control" value="<?php echo $arma['armas']; ?>"></div>
                    <div class="col-md-2"><label>Cargadores</label><input type="number" name="cargador" class="form-control" value="<?php echo $arma['cargador']; ?>"></div>
                    <div class="col-md-2"><label>Cartuchos</label><input type="number" name="cartuchos" class="form-control" value="<?php echo $arma['cartuchos']; ?>"></div>
                    <div class="col-md-2"><label>Chalecos</label><input type="number" name="chalecos" class="form-control" value="<?php echo $arma['chalecos']; ?>"></div>
                    <div class="col-md-2"><label>Otros</label><input type="text" name="bienes_diversos" class="form-control" value="<?php echo $arma['bienes_diversos']; ?>"></div>
                </div>

                <div class="section-title"><h5>4. Información de Pago y Cierre</h5></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Importe Convenio ($):</label>
                        <input type="number" step="0.01" name="importe_convenio" class="form-control" value="<?php echo $arma['importe_convenio']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Tipo de Pago:</label>
                        <input type="text" name="tipo_pago" class="form-control" value="<?php echo $arma['tipo_pago']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Atendió:</label>
                        <input type="text" name="atendio" class="form-control" value="<?php echo $arma['atendio']; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label>Observaciones Finales:</label>
                    <textarea name="observaciones" class="form-control" rows="3"><?php echo $arma['observaciones']; ?></textarea>
                </div>

                <div class="text-right border-top pt-3">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success btn-lg">Actualizar Base General</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
