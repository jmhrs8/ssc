<?php
require_once "../../config/conexion.php";

$id = isset($_GET['id']) ? $_GET['id'] : null;

// Meses en español para autoseleccionar el mes actual
$meses_es = [
    1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
    5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
    9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
];
$mes_actual = $meses_es[(int)date('n')];
$fecha_hoy = date('Y-m-d');

$registro = [
    'no_folio' => '', 'tipo' => 'OFICIAL', 'mes_de_reporte' => $mes_actual, 'no_empleado' => '', 'edad' => '',
    'rfc' => '', 'nombre' => '', 'apellido_paterno' => '', 'apellido_materno' => '', 
    'lesionado_nombre' => '', 'lesionado_ap_paterno' => '', 'lesionado_ap_materno' => '', 'sector_upc' => '',
    'fecha_de_siniestro' => $fecha_hoy, 'reporte' => '', 'poliza_seccion' => '', 'aseguradora' => '',
    'causa_resumido' => '', 'unidad_vehicular' => '', 'no_economico' => '', 'lugar_accidente' => '',
    'supervisor_riesgos' => '', 'no_ambulancia' => '', 'hospital' => '', 'requirio_hospitalizacion' => 'NO',
    'diagnostico' => '', 'quien_reporta' => '', 'quien_recibe' => '', 'fecha_ingreso_hospital' => '',
    'hora_ingreso_hospital' => '', 'cabina_nombre' => '', 'cabina_ap_paterno' => '', 'cabina_ap_materno' => '',
    'actividades_cabina' => '', 'conclusiones' => '', 'lesiones' => '', 'observaciones' => '',
    'montos_erogados' => '', 'fotos' => []
];

if (!$id) {
    $anio = date('y');
    $stmtMax = $pdo->query("SELECT MAX(id) FROM siniestros_personal");
    $maxId = $stmtMax->fetchColumn() + 1;
    $registro['no_folio'] = str_pad($maxId, 4, '0', STR_PAD_LEFT) . '-' . $anio;
} else {
    $stmt = $pdo->prepare("SELECT * FROM siniestros_personal WHERE id = ?");
    $stmt->execute([$id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($registro['foto'])) {
        $dec = json_decode($registro['foto'], true);
        if (is_array($dec)) {
            $registro['fotos'] = $dec;
        } else {
            $registro['fotos'] = array_filter(explode(',', $registro['foto']));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REGISTRO DE PERSONAL SINIESTRADO | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { text-transform: uppercase; font-size: 11px; background: #f4f6f9; }
        input, select, textarea { text-transform: uppercase !important; }
        .card { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-radius: 8px; }
        .card-header { background-color: #212529; color: white; font-weight: 600; font-size: 12px; }
        .section-header { background: #1a1a1a; color: #ffc107; padding: 6px 12px; border-radius: 4px; font-weight: bold; margin-top: 20px; margin-bottom: 12px; font-size: 11px; }
        .img-thumbnail-container { position: relative; display: inline-block; margin: 5px; }
        .img-thumbnail-container img { width: 90px; height: 90px; object-fit: cover; border-radius: 6px; border: 2px solid #dee2e6; }
        .btn-delete-foto { position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h4 class="mb-0 fw-bold"><i class="fas fa-user-injured text-warning me-2"></i> <?= $id ? 'EDITAR REGISTRO DE SINIESTRO' : 'NUEVO REGISTRO DE PERSONAL SINIESTRADO' ?></h4>
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> REGRESAR</a>
        </div>
        <div class="card-body">
            <form action="guardar.php" method="POST" enctype="multipart/form-data" id="formSiniestro">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="row g-3">
                    <!-- CABECERA DEL FOLIO Y BUSCADOR DE ELEMENTO POR RFC -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold">No. FOLIO</label>
                        <input type="text" name="no_folio" class="form-control" value="<?= htmlspecialchars($registro['no_folio']) ?>" readonly required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-primary">RFC DEL ELEMENTO (PADRÓN)</label>
                        <div class="input-group">
                            <input type="text" id="rfc" name="rfc" class="form-control fw-bold text-uppercase" value="<?= htmlspecialchars($registro['rfc']) ?>" placeholder="INGRESA RFC">
                            <button type="button" id="btn_verificar" class="btn btn-dark"><i class="fas fa-shield-alt"></i> Buscar</button>
                        </div>
                        <small id="estado_asegurado" class="text-muted" style="font-size:9px;">Extrae datos del elemento de la base general.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">NO. EMPLEADO</label>
                        <input type="text" id="no_empleado" name="no_empleado" class="form-control" value="<?= htmlspecialchars($registro['no_empleado']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">EDAD <span class="text-muted fw-normal">(AUTOCALCULADA)</span></label>
                        <input type="number" id="edad" name="edad" class="form-control" value="<?= htmlspecialchars($registro['edad']) ?>">
                    </div>

                    <!-- SECCIÓN 1: DATOS DEL ELEMENTO (OBTENIDOS DESDE EL PADRÓN POR RFC) -->
                    <div class="col-md-12">
                        <div class="section-header bg-secondary text-white"><i class="fas fa-id-badge me-1"></i> 1. DATOS DEL ELEMENTO (OBTENIDOS DEL PADRÓN POR RFC)</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">NOMBRE(S) DEL ELEMENTO</label>
                        <input type="text" id="nombre" name="nombre" class="form-control fw-bold" value="<?= htmlspecialchars($registro['nombre']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">APELLIDO PATERNO DEL ELEMENTO</label>
                        <input type="text" id="apellido_paterno" name="apellido_paterno" class="form-control fw-bold" value="<?= htmlspecialchars($registro['apellido_paterno'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">APELLIDO MATERNO DEL ELEMENTO</label>
                        <input type="text" id="apellido_materno" name="apellido_materno" class="form-control fw-bold" value="<?= htmlspecialchars($registro['apellido_materno'] ?? '') ?>">
                    </div>

                    <!-- SECCIÓN 2: DETALLES DEL REPORTE Y SINIESTRO -->
                    <div class="col-md-12">
                        <div class="section-header"><i class="fas fa-car-crash me-1"></i> 2. DETALLES DEL REPORTE Y SINIESTRO</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">TIPO</label>
                        <select name="tipo" class="form-select">
                            <option value="OFICIAL" <?= $registro['tipo']=='OFICIAL'?'selected':'' ?>>OFICIAL</option>
                            <option value="CIVIL" <?= $registro['tipo']=='CIVIL'?'selected':'' ?>>CIVIL</option>
                            <option value="COMISIONADO" <?= $registro['tipo']=='COMISIONADO'?'selected':'' ?>>COMISIONADO</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">MES DE REPORTE</label>
                        <select name="mes_de_reporte" class="form-select">
                            <?php foreach(['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'] as $m): ?>
                            <option value="<?= $m ?>" <?= $registro['mes_de_reporte']==$m?'selected':'' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">FECHA DE SINIESTRO</label>
                        <input type="date" name="fecha_de_siniestro" class="form-control" value="<?= htmlspecialchars($registro['fecha_de_siniestro']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">REPORTE (FOLIO CABINA)</label>
                        <input type="text" name="reporte" class="form-control" value="<?= htmlspecialchars($registro['reporte']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">PÓLIZA Y SECCIÓN QUE SE AFECTA</label>
                        <input type="text" name="poliza_seccion" class="form-control" value="<?= htmlspecialchars($registro['poliza_seccion']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">ASEGURADORA QUE ATIENDE</label>
                        <input type="text" name="aseguradora" class="form-control" value="<?= htmlspecialchars($registro['aseguradora']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">CAUSA RESUMIDO</label>
                        <input type="text" name="causa_resumido" class="form-control" value="<?= htmlspecialchars($registro['causa_resumido']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">UNIDAD VEHICULAR</label>
                        <input type="text" name="unidad_vehicular" class="form-control" value="<?= htmlspecialchars($registro['unidad_vehicular']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">ÁREA DE ADSCRIPCIÓN</label>
                        <input type="text" id="area_adscripcion" name="area_adscripcion" class="form-control" value="<?= htmlspecialchars($registro['area_adscripcion']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">HOSPITAL</label>
                        <input type="text" name="hospital" class="form-control" value="<?= htmlspecialchars($registro['hospital']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">¿REQUIRIÓ HOSPITALIZACIÓN?</label>
                        <select name="requirio_hospitalizacion" class="form-select">
                            <option value="NO" <?= $registro['requirio_hospitalizacion']=='NO'?'selected':'' ?>>NO</option>
                            <option value="SI" <?= $registro['requirio_hospitalizacion']=='SI'?'selected':'' ?>>SI</option>
                            <option value="SE DESCONOCE" <?= $registro['requirio_hospitalizacion']=='SE DESCONOCE'?'selected':'' ?>>SE DESCONOCE</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">MONTOS EROGADOS ($)</label>
                        <input type="number" step="0.01" name="montos_erogados" class="form-control" value="<?= htmlspecialchars($registro['montos_erogados']) ?>">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">LESIONES</label>
                        <textarea name="lesiones" class="form-control" rows="2"><?= htmlspecialchars($registro['lesiones']) ?></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">OBSERVACIONES</label>
                        <textarea name="observaciones" class="form-control" rows="2"><?= htmlspecialchars($registro['observaciones']) ?></textarea>
                    </div>

                    <!-- SECCIÓN 3: COMPLEMENTO Y DATOS ESPECÍFICOS DEL LESIONADO (LLENADO APARTE) -->
                    <div class="col-md-12">
                        <div class="section-header"><i class="fas fa-file-alt me-1"></i> 3. COMPLEMENTO, FORMATO OFICIAL Y DATOS DEL LESIONADO</div>
                    </div>

                    <!-- CAMPOS EXCLUSIVOS PARA EL LESIONADO (INDEPENDIENTES DEL ELEMENTO) -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-danger">NOMBRE(S) DEL LESIONADO</label>
                        <input type="text" name="lesionado_nombre" class="form-control border-danger" value="<?= htmlspecialchars($registro['lesionado_nombre'] ?? '') ?>" placeholder="LLENAR APARTE SI ES DISTINTO">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-danger">APELLIDO PATERNO (LESIONADO)</label>
                        <input type="text" name="lesionado_ap_paterno" class="form-control border-danger" value="<?= htmlspecialchars($registro['lesionado_ap_paterno'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-danger">APELLIDO MATERNO (LESIONADO)</label>
                        <input type="text" name="lesionado_ap_materno" class="form-control border-danger" value="<?= htmlspecialchars($registro['lesionado_ap_materno'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">SECTOR O UPC</label>
                        <input type="text" name="sector_upc" class="form-control" value="<?= htmlspecialchars($registro['sector_upc'] ?? '') ?>">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">LUGAR DEL ACCIDENTE</label>
                        <input type="text" name="lugar_accidente" class="form-control" value="<?= htmlspecialchars($registro['lugar_accidente'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">N.° ECONÓMICO</label>
                        <input type="text" name="no_economico" class="form-control" value="<?= htmlspecialchars($registro['no_economico'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">SUPERVISOR DE RIESGOS</label>
                        <input type="text" name="supervisor_riesgos" class="form-control" value="<?= htmlspecialchars($registro['supervisor_riesgos'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">N.° DE AMBULANCIA</label>
                        <input type="text" name="no_ambulancia" class="form-control" value="<?= htmlspecialchars($registro['no_ambulancia'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">FECHA INGRESO HOSPITAL</label>
                        <input type="date" name="fecha_ingreso_hospital" class="form-control" value="<?= htmlspecialchars($registro['fecha_ingreso_hospital'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">HORA INGRESO HOSPITAL</label>
                        <input type="time" name="hora_ingreso_hospital" class="form-control" value="<?= htmlspecialchars($registro['hora_ingreso_hospital'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">QUIÉN REPORTA</label>
                        <input type="text" name="quien_reporta" class="form-control" value="<?= htmlspecialchars($registro['quien_reporta'] ?? '') ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">QUIÉN RECIBE</label>
                        <input type="text" name="quien_recibe" class="form-control" value="<?= htmlspecialchars($registro['quien_recibe'] ?? '') ?>">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">DIAGNÓSTICO</label>
                        <textarea name="diagnostico" class="form-control" rows="2"><?= htmlspecialchars($registro['diagnostico'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">CABINA: NOMBRE(S)</label>
                        <input type="text" name="cabina_nombre" class="form-control" value="<?= htmlspecialchars($registro['cabina_nombre'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">CABINA: APELLIDO PATERNO</label>
                        <input type="text" name="cabina_ap_paterno" class="form-control" value="<?= htmlspecialchars($registro['cabina_ap_paterno'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">CABINA: APELLIDO MATERNO</label>
                        <input type="text" name="cabina_ap_materno" class="form-control" value="<?= htmlspecialchars($registro['cabina_ap_materno'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">ACTIVIDADES DESARROLLADAS POR CABINA</label>
                        <textarea name="actividades_cabina" class="form-control" rows="3"><?= htmlspecialchars($registro['actividades_cabina'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">CONCLUSIONES</label>
                        <textarea name="conclusiones" class="form-control" rows="3"><?= htmlspecialchars($registro['conclusiones'] ?? '') ?></textarea>
                    </div>

                    <!-- FOTOGRAFÍAS -->
                    <div class="col-md-12 mt-3">
                        <label class="form-label fw-bold text-danger"><i class="fas fa-camera me-1"></i> FOTOGRAFÍAS DE EVIDENCIA / LESIONES</label>
                        <input type="file" id="input_fotos" name="fotos[]" class="form-control mb-2" multiple accept="image/*">
                        <div id="contenedor_miniaturas" class="d-flex flex-wrap gap-2">
                            <?php if(!empty($registro['fotos'])): ?>
                                <?php foreach($registro['fotos'] as $index => $ruta_foto):
                                    $ruta_limpia = trim($ruta_foto);
                                    if(empty($ruta_limpia)) continue;
                                ?>
                                    <div class="img-thumbnail-container" id="foto_item_ex_<?= $index ?>">
                                        <input type="hidden" name="fotos_existentes[]" value="<?= htmlspecialchars($ruta_limpia) ?>">
                                        <img src="../../<?= htmlspecialchars($ruta_limpia) ?>" alt="Evidencia" onerror="this.src='../../assets/img/no-image.png'">
                                        <button type="button" class="btn-delete-foto" onclick="$('#foto_item_ex_<?= $index ?>').remove(); return false;"><i class="fas fa-times"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-success fw-bold px-4"><i class="fas fa-save me-1"></i> GUARDAR REGISTRO</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let dtFiles = new DataTransfer();

document.getElementById('input_fotos').addEventListener('change', function(e) {
    let files = e.target.files;
    for (let i = 0; i < files.length; i++) {
        let file = files[i];
        if (!file.type.match('image.*')) continue;
        dtFiles.items.add(file);
        let indexId = dtFiles.files.length - 1;
        let reader = new FileReader();
        reader.onload = function(evt) {
            let divId = 'preview_new_' + Date.now() + '_' + indexId;
            let htmlCard = `
                <div class="img-thumbnail-container" id="${divId}" data-index="${indexId}">
                    <img src="${evt.target.result}" alt="Evidencia">
                    <button type="button" class="btn-delete-foto" onclick="eliminarFotoNueva('${divId}', ${indexId})"><i class="fas fa-times"></i></button>
                </div>
            `;
            $('#contenedor_miniaturas').append(htmlCard);
        }
        reader.readAsDataURL(file);
    }
    this.files = dtFiles.files;
});

function eliminarFotoNueva(divId, index) {
    $('#' + divId).remove();
    let nuevoDt = new DataTransfer();
    let currentFiles = document.getElementById('input_fotos').files;
    for (let i = 0; i < currentFiles.length; i++) {
        if (i !== index) { nuevoDt.items.add(currentFiles[i]); }
    }
    dtFiles = nuevoDt;
    document.getElementById('input_fotos').files = dtFiles.files;
}

$('#btn_verificar').click(function(e) {
    e.preventDefault();
    let rfcVal = $('#rfc').val().trim();
    if(rfcVal === '') {
        alert('INGRESE UN RFC VÁLIDO PARA BUSCAR.');
        return;
    }

    $.ajax({
        url: 'acciones.php?accion=consultar_personal_rfc',
        type: 'GET',
        data: {rfc: rfcVal},
        dataType: 'json',
        success: function(response) {
            if(response.encontrado) {
                let nombreCompletoDB = (response.nombre || '').trim();
                let apPaterno = (response.apellido_paterno || '').trim();
                let apMaterno = (response.apellido_materno || '').trim();
                let soloNombre = nombreCompletoDB;

                // Separar automáticamente si vienen unidos en la base principal
                if (apPaterno === '' && nombreCompletoDB !== '') {
                    let partes = nombreCompletoDB.replace(/\s+/g, ' ').split(' ');
                    if (partes.length >= 3) {
                        apMaterno = partes.pop();
                        apPaterno = partes.pop();
                        soloNombre = partes.join(' ');
                    } else if (partes.length === 2) {
                        apPaterno = partes.pop();
                        soloNombre = partes.join(' ');
                    }
                }

                // Asignar a los campos correspondientes del ELEMENTO
                $('#nombre').val(soloNombre.toUpperCase());
                $('#apellido_paterno').val(apPaterno.toUpperCase());
                $('#apellido_materno').val(apMaterno.toUpperCase());
                
                $('#no_empleado').val((response.no_empleado || '').toUpperCase());
                $('#area_adscripcion').val((response.area_adscripcion || '').toUpperCase());

                if (response.edad) {
                    $('#edad').val(response.edad);
                } else if (rfcVal.length >= 10) {
                    let anioStr = rfcVal.substring(4, 6);
                    let anioNum = parseInt(anioStr);
                    let anioActualDosDigitos = new Date().getFullYear() % 100;
                    let anioCompleto = (anioNum > anioActualDosDigitos ? 1900 : 2000) + anioNum;
                    let edadAprox = new Date().getFullYear() - anioCompleto;
                    if(edadAprox > 15 && edadAprox < 90) { $('#edad').val(edadAprox); }
                }

                $('#estado_asegurado').html('<span class="text-success fw-bold"><i class="fas fa-check-circle"></i> VERIFICADO: ELEMENTO ENCONTRADO EN PADRÓN.</span>');
            } else {
                let continuar = confirm('⚠️ EL RFC INGRESADO NO FUE ENCONTRADO EN LA BASE GENERAL DE ASEGURADOS.\n\n¿Desea continuar y agregarlo bajo su propio riesgo?');
                if(!continuar) {
                    $('#rfc').val('').focus();
                } else {
                    $('#estado_asegurado').html('<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> NO ENCONTRADO EN BASE GENERAL.</span>');
                }
            }
        },
        error: function() {
            alert('ERROR AL CONSULTAR LA BASE DE DATOS.');
        }
    });
});
</script>
</body>
</html>
