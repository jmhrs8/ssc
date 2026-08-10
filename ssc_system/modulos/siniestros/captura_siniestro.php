<?php
session_start();
require_once "../../config/conexion.php";

$id = $_GET['id'] ?? null;
$modo_edicion = ($id && $id !== 'nuevo');

if ($modo_edicion) {
    // Modo Edición: Cargar datos desde la tabla unificada
    $stmt = $pdo->prepare("SELECT * FROM siniestros WHERE id = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) die("Error: Siniestro no encontrado.");
} else {
    // Modo Alta: Valores iniciales por defecto y generación de Folio Automático
    $anio_actual = 2026;

    // Buscar el último consecutivo del año para calcular el folio dinámicamente
    $stmtFolio = $pdo->prepare("SELECT folio FROM siniestros WHERE folio LIKE ? ORDER BY id DESC LIMIT 1");
    $stmtFolio->execute(["%/$anio_actual"]);
    $ultimo = $stmtFolio->fetch(PDO::FETCH_ASSOC);

    if ($ultimo) {
        $partes = explode('/', $ultimo['folio']);
        $siguiente_numero = intval($partes[0]) + 1;
    } else {
        $siguiente_numero = 1;
    }

    $folio_automatico = str_pad($siguiente_numero, 3, '0', STR_PAD_LEFT) . '/' . $anio_actual;

    // Estructura original mapeada a los nombres vacíos de la BD
    $r = [
        'id' => '', 'folio' => $folio_automatico, 'fecha' => date('Y-m-d'), 'hora' => date('H:i:s'),
        'marca' => '', 'modelo' => '', 'tipo' => '', 'economico_placas' => '',
        'no_inventario' => '', 'no_serie' => '', 'adscripcion' => '', 'kilometraje_unidad' => '',
        'cond_nombre' => '', 'cond_ap_paterno' => '', 'cond_ap_materno' => '',
        'cond_no_empleado' => '', 'cond_licencia' => '', 'cond_tel_area' => '', 'cond_tel_particular' => '',
        'quien_reporta_nombre' => '', 
        // Mapeo correcto para Tercero en modo alta (vacío)
        'vehiculo_3ro' => '', 'tercero_tipo' => '', 'placas_3ro' => '', 'tercero_color' => '',
        // Mapeo correcto para Ubicación en modo alta (vacío)
        'calles' => '', 'colonia' => '', 'alcaldia' => '', 'ubi_referencias' => '', 
        'supervisor' => '', 'taller_asignado' => '',
        'hora_asignado' => '', 'hora_llegada' => '', 'hora_terminado' => '',
        // Mapeo correcto para Aseguradora en modo alta (vacío)
        'seguro' => '', 'coord_aseguradora' => '', 'no_siniestro' => '', 'no_siniestro_aseg' => '',
        'ajustador_nombre' => '', 'hora_reporte_aseg' => '', 'observaciones' => '',
        'riesgo_danos_materiales' => 0, 'est_danos_materiales' => '0.00',
        'riesgo_robo_total' => 0, 'est_robo_total' => '0.00',
        'riesgo_resp_civil' => 0, 'est_resp_civil' => '0.00',
        'riesgo_gastos_medicos' => 0, 'est_gastos_medicos' => '0.00',
        'riesgo_equipo_especial' => 0, 'est_equipo_especial' => '0.00',
        'resp_tipo' => 'tercero', 'acta_levantada' => 0, 'no_acta' => '', 'aplica_deducible' => 'NO',
        'concluyo_atencion' => '',
        'les_n1' => '', 'les_e1' => '', 'les_er1' => 0, 'les_h1' => '',
        'les_n2' => '', 'les_e2' => '', 'les_er2' => 0, 'les_h2' => '',
        'les_n3' => '', 'les_e3' => '', 'les_er3' => 0, 'les_h3' => '',
        // --- NUEVOS CAMPOS ADICIONALES (Vacíos) ---
        'zona' => '', 'tipo_siniestro' => '', 'taller_ingreso' => '', 'operador_cabina' => '',
        'declaracion_universal' => 'NO', 'pase_medicos' => 'NO', 'pase_taller' => 'NO',
        'cuadernillo' => 'NO', 'graficas' => 'NO', 'visto_bueno' => 'NO',
        'fecha_visto_bueno' => '', 'fecha_vb_taller' => '', 'fecha_oficio_recibido' => '',
        'numero_expediente' => '', 'papeleta_control_gestion' => '', 'observaciones_generales' => '', 'estatus' => 'PENDIENTE'
    ];
}

$f = !empty($r['fecha']) ? strtotime($r['fecha']) : time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $modo_edicion ? 'Modificar' : 'Capturar' ?> Siniestro - SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background:#f4f6f9; font-size: 11px; font-family: 'Arial Narrow', Arial, sans-serif; }
        .form-container { background:#fff; border: 1px solid #000; padding: 15px; }
        .section-header { background:#1a252f; color:#fff; padding:4px 10px; font-weight:bold; border: 1px solid #000; margin-top: 8px; text-transform: uppercase; }
        .row-border { border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; margin: 0; }
        .col-border { border-right: 1px solid #000; padding: 3px 6px; }
        .col-border:last-child { border-right: none; }
        label { font-weight: bold; display: block; margin-bottom: 1px; color: #333; font-size: 10px; }
        .form-control-sm, .form-select-sm { border: none; border-radius: 0; padding: 0; font-size: 11px; background: transparent; }
        .form-control-sm:focus, .form-select-sm:focus { box-shadow: none; background: #fffde7; }
        .table-lesionados input { border: none; width: 100%; background: transparent; }
        .table-lesionados input:focus { box-shadow: none; background: #fffde7; outline: none; }
        textarea.obs-field { border: none; width: 100%; resize: none; font-size: 11px; }
        .signature-section { margin-top: 30px; page-break-inside: avoid; }
        .signature-box { border-top: 1px solid #000; text-align: center; padding-top: 5px; font-weight: bold; font-size: 10px; }
        .author-footer { font-size: 10px; color: #555; text-align: center; margin-top: 25px; font-style: italic; border-top: 1px dashed #ccc; padding-top: 8px; }
    </style>
</head>
<body class="p-3">
<div class="container-fluid form-container">
    <form action="guardar_reporte_detallado.php" method="POST" id="formSiniestro">
        <input type="hidden" name="id" id="registro_id" value="<?= $r['id'] ?>">
        <input type="hidden" name="action" value="<?= $modo_edicion ? 'update' : 'insert' ?>">

        <div class="row align-items-end mb-2">
            <div class="col-8">
                <h5 class="fw-bold m-0">REPORTE TELEFÓNICO DE SINIESTRO DEL SISTEMA</h5>
            </div>
            <div class="col-4">
                <table class="table table-bordered border-dark table-sm m-0 text-center" style="font-size: 9px;">
                    <tr class="bg-light"><th>DÍA</th><th>MES</th><th>AÑO</th><th>HORA SINIESTRO</th></tr>
                    <tr>
                        <td><?= date('d', $f) ?></td>
                        <td><?= date('m', $f) ?></td>
                        <td><?= date('Y', $f) ?></td>
                        <td><input type="time" name="hora" class="form-control-sm text-center w-100" value="<?= !empty($r['hora']) ? substr($r['hora'], 0, 5) : date('H:i') ?>"></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="section-header">Datos del Vehículo Asegurado</div>
        <div class="row row-border">
            <div class="col-3 col-border"><label>MARCA</label><input type="text" name="marca" class="form-control-sm w-100" value="<?= htmlspecialchars($r['marca']) ?>" required></div>
            <div class="col-3 col-border"><label>MODELO</label><input type="text" name="modelo" class="form-control-sm w-100" value="<?= htmlspecialchars($r['modelo']) ?>"></div>
            <div class="col-3 col-border"><label>TIPO</label><input type="text" name="tipo" class="form-control-sm w-100" value="<?= htmlspecialchars($r['tipo']) ?>"></div>
            <div class="col-3 col-border bg-light"><label class="text-primary">FOLIO DE CONTROL</label><input type="text" name="folio" class="form-control-sm w-100 fw-bold text-danger" value="<?= htmlspecialchars($r['folio']) ?>" readonly></div>
        </div>
        <div class="row row-border">
            <div class="col-4 col-border bg-light"><label class="text-danger">ECONÓMICO / PLACAS</label><input type="text" name="economico_placas" id="placas" class="form-control-sm w-100 fw-bold" value="<?= htmlspecialchars($r['economico_placas']) ?>" required></div>
            <div class="col-5 col-border"><label>ADSCRIPCIÓN</label><input type="text" name="adscripcion" class="form-control-sm w-100" value="<?= htmlspecialchars($r['adscripcion']) ?>"></div>
            <div class="col-3 col-border bg-light"><label>ZONA</label><input type="text" name="zona" class="form-control-sm w-100" value="<?= htmlspecialchars($r['zona'] ?? '') ?>"></div>
        </div>
        <div class="row row-border">
            <div class="col-3 col-border"><label>NÚMERO DE INVENTARIO</label><input type="text" name="no_inventario" class="form-control-sm w-100" value="<?= htmlspecialchars($r['no_inventario']) ?>"></div>
            <div class="col-5 col-border bg-light"><label class="text-danger">NÚMERO DE SERIE Y/O MOTOR</label><input type="text" name="no_serie" id="serie" class="form-control-sm w-100 fw-bold" value="<?= htmlspecialchars($r['no_serie']) ?>" required></div>
            <div class="col-4 col-border"><label>KILOMETRAJE DE LA UNIDAD</label><input type="text" name="kilometraje_unidad" class="form-control-sm w-100" value="<?= htmlspecialchars($r['kilometraje_unidad']) ?>"></div>
        </div>

        <div class="section-header">Datos del Conductor</div>
        <div class="row row-border">
            <div class="col-4 col-border"><label>NOMBRE(S)</label><input type="text" name="cond_nombre" class="form-control-sm w-100" value="<?= htmlspecialchars($r['cond_nombre']) ?>"></div>
            <div class="col-4 col-border"><label>APELLIDO PATERNO</label><input type="text" name="cond_ap_paterno" class="form-control-sm w-100" value="<?= htmlspecialchars($r['cond_ap_paterno']) ?>"></div>
            <div class="col-4 col-border"><label>APELLIDO MATERNO</label><input type="text" name="cond_ap_materno" class="form-control-sm w-100" value="<?= htmlspecialchars($r['cond_ap_materno']) ?>"></div>
        </div>
        <div class="row row-border">
            <div class="col-3 col-border"><label>N.º DE EMPLEADO</label><input type="text" name="cond_no_empleado" class="form-control-sm w-100" value="<?= htmlspecialchars($r['cond_no_empleado']) ?>"></div>
            <div class="col-3 col-border"><label>LICENCIA NÚMERO</label><input type="text" name="cond_licencia" class="form-control-sm w-100" value="<?= htmlspecialchars($r['cond_licencia']) ?>"></div>
            <div class="col-3 col-border"><label>N.º TELEFÓNICO DEL ÁREA</label><input type="text" name="cond_tel_area" class="form-control-sm w-100" value="<?= htmlspecialchars($r['cond_tel_area']) ?>"></div>
            <div class="col-3 col-border"><label>N.º TELEFÓNICO PARTICULAR</label><input type="text" name="cond_tel_particular" class="form-control-sm w-100" value="<?= htmlspecialchars($r['cond_tel_particular']) ?>"></div>
        </div>

        <div class="section-header">Quién Reporta</div>
        <div class="row row-border">
            <div class="col-12 col-border"><label>NOMBRE COMPLETO</label><input type="text" name="quien_reporta_nombre" class="form-control-sm w-100" value="<?= htmlspecialchars($r['quien_reporta_nombre']) ?>"></div>
        </div>

        <div class="section-header">Datos del Tercero</div>
        <div class="row row-border">
            <div class="col-4 col-border"><label>MARCA Y MODELO</label><input type="text" name="tercero_marca_modelo" class="form-control-sm w-100" value="<?= htmlspecialchars($r['vehiculo_3ro']) ?>"></div>
            <div class="col-2 col-border"><label>TIPO</label><input type="text" name="tercero_tipo" class="form-control-sm w-100" value="<?= htmlspecialchars($r['tercero_tipo']) ?>"></div>
            <div class="col-3 col-border"><label>PLACAS</label><input type="text" name="tercero_placas" class="form-control-sm w-100" value="<?= htmlspecialchars($r['placas_3ro']) ?>"></div>
            <div class="col-3 col-border"><label>COLOR</label><input type="text" name="tercero_color" class="form-control-sm w-100" value="<?= htmlspecialchars($r['tercero_color']) ?>"></div>
        </div>

        <div class="section-header">Ubicación del Accidente</div>
        <div class="row row-border">
            <div class="col-4 col-border"><label>CALLE(S)</label><input type="text" name="ubi_calle" class="form-control-sm w-100" value="<?= htmlspecialchars($r['calles']) ?>"></div>
            <div class="col-4 col-border"><label>COLONIA</label><input type="text" name="ubi_colonia" class="form-control-sm w-100" value="<?= htmlspecialchars($r['colonia']) ?>"></div>
            <div class="col-4 col-border">
                <label>ALCALDÍA / MUNICIPIO</label>
                <select name="ubi_alcaldia" class="form-select form-select-sm w-100">
                    <option value="">-- SELECCIONAR --</option>
                    <?php foreach(['ÁLVARO OBREGÓN','AZCAPOTZALCO','BENITO JUÁREZ','COYOACÁN','CUAJIMALPA DE MORELOS','CUAUHTÉMOC','GUSTAVO A. MADERO','IZTACALCO','IZTAPALAPA','LA MAGDALENA CONTRERAS','MIGUEL HIDALGO','MILPA ALTA','TLÁHUAC','TLALPAN','VENUSTIANO CARRANZA','XOCHIMILCO','ESTADO DE MÉXICO','OTRO'] as $alc): ?>
                        <option value="<?= $alc ?>" <?= ($r['alcaldia'] ?? '') ===$alc?'selected':'' ?>><?= $alc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row row-border">
            <div class="col-12 col-border"><label>REFERENCIAS</label><input type="text" name="ubi_referencias" class="form-control-sm w-100" value="<?= htmlspecialchars($r['ubi_referencias']) ?>"></div>
        </div>

        <div class="section-header">Lesionados</div>
        <table class="table table-bordered border-dark table-sm m-0 table-lesionados" style="font-size: 10px;">
            <tr class="text-center bg-light">
                <th width="50%">NOMBRE</th><th width="15%">N.º DE EMPLEADO</th><th width="10%">ERUM</th><th width="25%">HOSPITAL</th>
            </tr>
            <?php for($i=1; $i<=3; $i++): ?>
            <tr>
                <td><input type="text" name="les_n<?= $i ?>" value="<?= htmlspecialchars($r["les_n$i"] ?? '') ?>"></td>
                <td><input type="text" name="les_e<?= $i ?>" value="<?= htmlspecialchars($r["les_e$i"] ?? '') ?>"></td>
                <td class="text-center"><input type="checkbox" name="les_er<?= $i ?>" value="1" <?= (isset($r["les_er$i"]) && $r["les_er$i"] == 1) ? 'checked' : '' ?>></td>
                <td><input type="text" name="les_h<?= $i ?>" value="<?= htmlspecialchars($r["les_h$i"] ?? '') ?>"></td>
            </tr>
            <?php endfor; ?>
        </table>

        <div class="section-header">Control Operativo</div>
        <div class="row row-border">
            <div class="col-4 col-border"><label>OPERADOR DE CABINA</label><input type="text" name="operador_cabina" class="form-control-sm w-100" value="<?= htmlspecialchars($r['operador_cabina'] ?? '') ?>"></div>
            <div class="col-4 col-border"><label>SUPERVISOR</label><input type="text" name="supervisor" class="form-control-sm w-100" value="<?= htmlspecialchars($r['supervisor']) ?>"></div>
            <div class="col-4 col-border"><label>TIPO DE SINIESTRO</label><input type="text" name="tipo_siniestro" class="form-control-sm w-100" value="<?= htmlspecialchars($r['tipo_siniestro'] ?? '') ?>"></div>
        </div>
        <div class="row row-border">
            <div class="col-3 col-border"><label>TALLER ASIGNADO</label><input type="text" name="taller_asignado" class="form-control-sm w-100" value="<?= htmlspecialchars($r['taller_asignado']) ?>"></div>
            <div class="col-3 col-border"><label>TALLER INGRESO</label><input type="text" name="taller_ingreso" class="form-control-sm w-100" value="<?= htmlspecialchars($r['taller_ingreso'] ?? '') ?>"></div>
            <div class="col-2 col-border"><label>ASIGNADO A LAS</label><input type="time" name="hora_asignado" class="form-control-sm w-100" value="<?= !empty($r['hora_asignado']) ? substr($r['hora_asignado'],0,5) : '' ?>"></div>
            <div class="col-2 col-border"><label>LLEGADA A LAS</label><input type="time" name="hora_llegada" class="form-control-sm w-100" value="<?= !empty($r['hora_llegada']) ? substr($r['hora_llegada'],0,5) : '' ?>"></div>
            <div class="col-2 col-border"><label>TERMINADO</label><input type="time" name="hora_terminado" class="form-control-sm w-100" value="<?= !empty($r['hora_terminado']) ? substr($r['hora_terminado'],0,5) : '' ?>"></div>
        </div>

        <div class="section-header">Reporte a la Aseguradora</div>
        <div class="row row-border">
            <div class="col-8 col-border">
                <label class="text-primary fw-bold">NOMBRE DE LA ASEGURADORA</label>
                <select name="aseguradora" class="form-select form-select-sm w-100 fw-bold text-primary" required>
                    <option value="">-- SELECCIONAR --</option>
                    <?php foreach(['POTOSI','PRIMERO SEGUROS','BANORTE','QUALITAS','TOTAL PARTS','SIN DATO'] as $aseg): ?>
                        <option value="<?= $aseg ?>" <?= ($r['seguro'] ?? '') ===$aseg?'selected':'' ?>><?= $aseg ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-border"><label>N.º DE SINIESTRO INTERNO</label><input type="text" name="no_siniestro" class="form-control-sm w-100 fw-bold text-danger" value="<?= htmlspecialchars($r['no_siniestro'] ?? '') ?>"></div>
        </div>
        <div class="row row-border">
            <div class="col-4 col-border"><label>COORDINADOR</label><input type="text" name="coord_aseguradora" class="form-control-sm w-100" value="<?= htmlspecialchars($r['coord_aseguradora']) ?>"></div>
            <div class="col-4 col-border"><label>AJUSTADOR</label><input type="text" name="ajustador_nombre" class="form-control-sm w-100" value="<?= htmlspecialchars($r['ajustador_nombre']) ?>"></div>
            <div class="col-2 col-border"><label class="text-primary fw-bold">N.º SINIESTRO ASEG.</label><input type="text" name="no_siniestro_aseg" class="form-control-sm w-100 fw-bold border-bottom border-primary" value="<?= htmlspecialchars($r['no_siniestro_aseg']) ?>"></div>
            <div class="col-2 col-border"><label>REPORTE A LAS</label><input type="time" name="hora_reporte_aseg" class="form-control-sm w-100" value="<?= !empty($r['hora_reporte_aseg']) ? substr($r['hora_reporte_aseg'],0,5) : '' ?>"></div>
        </div>

        <div class="section-header">Pases, Dictámenes y Vistos Buenos de Control</div>
        <div class="row row-border bg-light text-center">
            <div class="col-2 col-border"><label>DECLARACIÓN UNIV.</label></div>
            <div class="col-2 col-border"><label>PASE MÉDICOS</label></div>
            <div class="col-2 col-border"><label>PASE TALLER</label></div>
            <div class="col-2 col-border"><label>CUADERNILLO</label></div>
            <div class="col-2 col-border"><label>GRÁFICAS</label></div>
            <div class="col-2 col-border"><label>VISTO BUENO</label></div>
        </div>
        <div class="row row-border text-center">
            <div class="col-2 col-border">
                <select name="declaracion_universal" class="form-select form-select-sm w-100">
                    <option value="NO" <?= ($r['declaracion_universal'] ?? 'NO')=='NO'?'selected':'' ?>>NO</option>
                    <option value="SI" <?= ($r['declaracion_universal'] ?? 'NO')=='SI'?'selected':'' ?>>SI</option>
                </select>
            </div>
            <div class="col-2 col-border">
                <select name="pase_medicos" class="form-select form-select-sm w-100">
                    <option value="NO" <?= ($r['pase_medicos'] ?? 'NO')=='NO'?'selected':'' ?>>NO</option>
                    <option value="SI" <?= ($r['pase_medicos'] ?? 'NO')=='SI'?'selected':'' ?>>SI</option>
                </select>
            </div>
            <div class="col-2 col-border">
                <select name="pase_taller" class="form-select form-select-sm w-100">
                    <option value="NO" <?= ($r['pase_taller'] ?? 'NO')=='NO'?'selected':'' ?>>NO</option>
                    <option value="SI" <?= ($r['pase_taller'] ?? 'NO')=='SI'?'selected':'' ?>>SI</option>
                </select>
            </div>
            <div class="col-2 col-border">
                <select name="cuadernillo" class="form-select form-select-sm w-100">
                    <option value="NO" <?= ($r['cuadernillo'] ?? 'NO')=='NO'?'selected':'' ?>>NO</option>
                    <option value="SI" <?= ($r['cuadernillo'] ?? 'NO')=='SI'?'selected':'' ?>>SI</option>
                </select>
            </div>
            <div class="col-2 col-border">
                <select name="graficas" class="form-select form-select-sm w-100">
                    <option value="NO" <?= ($r['graficas'] ?? 'NO')=='NO'?'selected':'' ?>>NO</option>
                    <option value="SI" <?= ($r['graficas'] ?? 'NO')=='SI'?'selected':'' ?>>SI</option>
                </select>
            </div>
            <div class="col-2 col-border">
                <select name="visto_bueno" class="form-select form-select-sm w-100">
                    <option value="NO" <?= ($r['visto_bueno'] ?? 'NO')=='NO'?'selected':'' ?>>NO</option>
                    <option value="SI" <?= ($r['visto_bueno'] ?? 'NO')=='SI'?'selected':'' ?>>SI</option>
                </select>
            </div>
        </div>
        <div class="row row-border">
            <div class="col-4 col-border"><label>FECHA VISTO BUENO</label><input type="date" name="fecha_visto_bueno" class="form-control-sm w-100" value="<?= htmlspecialchars($r['fecha_visto_bueno'] ?? '') ?>"></div>
            <div class="col-4 col-border"><label>FECHA VB TALLER</label><input type="date" name="fecha_vb_taller" class="form-control-sm w-100" value="<?= htmlspecialchars($r['fecha_vb_taller'] ?? '') ?>"></div>
            <div class="col-4 col-border bg-light"><label class="text-primary">PAPELETA CONTROL GESTIÓN</label><input type="text" name="papeleta_control_gestion" class="form-control-sm w-100 fw-bold" value="<?= htmlspecialchars($r['papeleta_control_gestion'] ?? '') ?>"></div>
        </div>
        <div class="row row-border">
            <div class="col-6 col-border"><label>NÚMERO EXPEDIENTE</label><input type="text" name="numero_expediente" class="form-control-sm w-100" value="<?= htmlspecialchars($r['numero_expediente'] ?? '') ?>"></div>
            <div class="col-6 col-border"><label>FECHA OFICIO RECIBIDO</label><input type="date" name="fecha_oficio_recibido" class="form-control-sm w-100" value="<?= htmlspecialchars($r['fecha_oficio_recibido'] ?? '') ?>"></div>
        </div>

        <div class="section-header">Observaciones Siniestros e Internas</div>
        <div class="row row-border">
            <div class="col-6 col-border" style="border-right: 1px solid #000;">
                <label>OBSERVACIONES (SINIETROS ORIGINAL)</label>
                <textarea name="observaciones" class="obs-field p-2" rows="3" maxlength="500" placeholder="Escriba sus observaciones aquí..."><?= htmlspecialchars($r['observaciones']) ?></textarea>
            </div>
            <div class="col-6 col-border">
                <label>OBSERVACIONES GENERALES / CONTROL GESTIÓN</label>
                <textarea name="observaciones_generales" class="obs-field p-2" rows="3" maxlength="500" placeholder="Notas de seguimiento interno adicionales..."><?= htmlspecialchars($r['observaciones_generales'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="section-header">Riesgo Afectado / Estimación</div>
        <div class="row row-border">
            <div class="col-7 p-0" style="border-right: 1px solid #000;">
                <?php
                $riesgos = [
                    'DAÑOS MATERIALES' => ['check'=>'riesgo_danos_materiales', 'est'=>'est_danos_materiales'],
                    'ROBO TOTAL' => ['check'=>'riesgo_robo_total', 'est'=>'est_robo_total'],
                    'RESPONSABILIDAD CIVIL' => ['check'=>'riesgo_resp_civil', 'est'=>'est_resp_civil'],
                    'GASTOS MÉDICOS' => ['check'=>'riesgo_gastos_medicos', 'est'=>'est_gastos_medicos'],
                    'EQUIPO ESPECIAL' => ['check'=>'riesgo_equipo_especial', 'est'=>'est_equipo_especial']
                ];
                foreach($riesgos as $label => $fields): ?>
                <div class="d-flex border-bottom border-dark align-items-center p-1">
                    <div style="width:180px;" class="fw-bold"><?= $label ?></div>
                    <div class="px-3">( <input type="checkbox" name="<?= $fields['check'] ?>" value="1" <?= $r[$fields['check']] == 1 ? 'checked' : '' ?>> )</div>
                    <div class="d-flex align-items-center flex-grow-1">
                        <span>$</span><input type="number" step="0.01" name="<?= $fields['est'] ?>" class="form-control-sm flex-grow-1 ms-2" value="<?= $r[$fields['est']] ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="col-5 p-2 bg-light">
                <label>¿QUIÉN FUE EL RESPONSABLE?</label>
                <div class="ms-3 my-1">
                    <input type="radio" name="resp_tipo" value="policia" <?= $r['resp_tipo'] == 'policia' ? 'checked' : '' ?>> EL POLICÍA <br>
                    <input type="radio" name="resp_tipo" value="tercero" <?= $r['resp_tipo'] == 'tercero' ? 'checked' : '' ?>> EL TERCERO
                </div>
                <hr class="my-2 border-dark">
                <input type="checkbox" name="acta_levantada" value="1" <?= $r['acta_levantada'] == 1 ? 'checked' : '' ?>> SE LEVANTÓ ACTA <br>
                <label class="mt-1">NÚMERO DE ACTA</label>
                <input type="text" name="no_acta" class="form-control-sm border-bottom border-dark w-100" value="<?= htmlspecialchars($r['no_acta']) ?>">
                <label class="mt-2">APLICA DEDUCIBLE</label>
                <select name="aplica_deducible" class="form-select form-select-sm p-0 m-0" style="font-size: 10px;">
                    <option value="NO" <?= $r['aplica_deducible'] == 'NO' ? 'selected' : '' ?>>NO</option>
                    <option value="SÍ" <?= $r['aplica_deducible'] == 'SÍ' ? 'selected' : '' ?>>SÍ</option>
                </select>
                <div class="mt-2">
                    <label class="fw-bold text-danger">ESTATUS GENERAL</label>
                    <select name="estatus" class="form-select form-select-sm p-0 m-0 fw-bold text-danger" style="font-size: 10px;">
                        <option value="PENDIENTE" <?= ($r['estatus'] ?? 'PENDIENTE') == 'PENDIENTE' ? 'selected' : '' ?>>PENDIENTE</option>
                        <option value="CONCLUIDO" <?= ($r['estatus'] ?? 'PENDIENTE') == 'CONCLUIDO' ? 'selected' : '' ?>>CONCLUIDO</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="section-header">Cómo concluyó la atención del siniestro</div>
        <div class="row row-border">
            <div class="col-12 p-0">
                <textarea name="concluyo_atencion" class="w-100 border-0 p-2 obs-field" rows="3" placeholder="Detalles de cierre de atención..."><?= htmlspecialchars($r['concluyo_atencion']) ?></textarea>
            </div>
        </div>

        <div class="row signature-section justify-content-between mx-0 my-4">
            <div class="col-3 text-center px-4">
                <div style="height: 45px;"></div>
                <div class="signature-box">CONSTRUCTOR / ELEMENTO</div>
            </div>
            <div class="col-3 text-center px-4">
                <div style="height: 45px;"></div>
                <div class="signature-box">SUPERVISOR EN TURNO</div>
            </div>
            <div class="col-3 text-center px-4">
                <div style="height: 45px;"></div>
                <div class="signature-box">CONTROL DE GESTIÓN</div>
            </div>
        </div>

        <div class="mt-3 text-center">
            <button type="submit" class="btn btn-dark btn-md px-5 fw-bold shadow">
                GUARDAR REPORTE OFICIAL UNIFICADO
            </button>
        </div>
    </form>

    <div class="author-footer">
        Diseñado por Ing. Juan Manuel Hernandez Lugo
    </div>
</div>

<script>
// Validaciones de duplicados JS asíncronas con SweetAlert2 intactas
function setupValidation(idInput, campoBD) {
    const input = document.getElementById(idInput);
    if (!input) return;
    input.addEventListener('blur', function() {
        const valor = this.value.trim();
        const idActual = document.getElementById('registro_id').value || 0;
        if (valor.length > 2) {
            const fd = new FormData();
            fd.append('valor', valor);
            fd.append('columna', campoBD);
            fd.append('id', idActual);
            fetch('verificar_duplicado.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.duplicado) {
                        const tipoTexto = (campoBD === 'economico_placas') ? 'Económico/Placa' : 'Número de Serie';
                        Swal.fire({
                            title: '¡Unidad con Historial!',
                            text: `El ${tipoTexto} "${valor}" ya cuenta con ${data.total} siniestro(s) registrado(s) en el sistema.`,
                            icon: 'warning',
                            confirmButtonColor: '#1a252f',
                            confirmButtonText: 'Entendido'
                        });
                    }
                }).catch(err => console.error("Error validando duplicado:", err));
        }
    });
}
setupValidation('placas', 'economico_placas');
setupValidation('serie', 'no_serie');

function setupSiniestroValidation() {
    const inputSiniestro = document.querySelector('input[name="no_siniestro_aseg"]');
    const selectAseguradora = document.querySelector('select[name="aseguradora"]');

    if (!inputSiniestro || !selectAseguradora) return;

    function verificarSiniestroDuplicado() {
        const siniestro = inputSiniestro.value.trim();
        const aseguradora = selectAseguradora.value;
        const idActual = document.getElementById('registro_id').value || 0;

        if (siniestro.length > 2 && aseguradora !== "") {
            const fd = new FormData();
            fd.append('columna', 'combinacion_siniestro');
            fd.append('no_siniestro_aseg', siniestro);
            fd.append('aseguradora', aseguradora);
            fd.append('id', idActual);

            fetch('verificar_duplicado.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.duplicado) {
                        Swal.fire({
                            title: '¡Siniestro Duplicado Detectado!',
                            text: `El No. Siniestro Aseguradora "${siniestro}" ya se encuentra registrado con "${aseguradora}".`,
                            icon: 'error',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Corregir información'
                        }).then(() => {
                            inputSiniestro.value = '';
                            setTimeout(() => inputSiniestro.focus(), 100);
                        });
                    }
                }).catch(err => console.error("Error validando siniestro:", err));
        }
    }

    inputSiniestro.addEventListener('blur', verificarSiniestroDuplicado);
    selectAseguradora.addEventListener('change', verificarSiniestroDuplicado);
}
setupSiniestroValidation();

// Todo el texto ingresado pasa automáticamente a MAYÚSCULAS
document.querySelectorAll('input[type="text"], textarea').forEach(i => {
    i.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
});
</script>
</body>
</html>
