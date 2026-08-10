<?php
require_once "../../config/conexion.php";
$id = $_GET['id'] ?? null;

// Inicialización usando los nombres reales de tu Base de Datos
$r = [
    'mes' => '', 'folio' => '',
    'fecha_reporte' => date('Y-m-d'), 'hora_reporte' => date('H:i'),
    'fecha' => '', 'hora' => '', // Mapeados correctamente a fecha/hora siniestro
    'marca' => '', 'modelo' => '', 'tipo' => '', 'economico_placas' => '',
    'no_inventario' => '', 'no_serie' => '', 'adscripcion' => '',
    'nombre_elemento' => '', 'cond_no_empleado' => '', 'cond_licencia' => '',
    'no_siniestro' => '', 'taller_asignado' => '', 'taller_ingreso' => '', 'hospital' => '',
    'carp_investigacion' => '', 'propio' => 'NO', 'arrendado' => 'NO', 'aseguradora' => '',
    'declaracion_universal' => 'NO', 'pase_medicos' => 'NO', 'pase_taller' => 'NO',
    'graficas' => 'NO', 'cuadernillo' => 'NO', 'visto_bueno' => 'NO', 'fecha_visto_bueno' => '',
    'fecha_vb_taller' => '', 'fecha_oficio_recibido' => '', 'numero_expediente' => '',
    'calles' => '', 'colonia' => '', 'alcaldia' => '',
    'vehiculo_3ro' => '', 'placas_3ro' => '', 'seguro' => '', 'danos' => '', 'lesionados' => '',
    'foto_unidad' => '', 'foto_vehiculo' => '', 'observaciones' => '', 'observaciones_generales' => ''
];

$anio = 2026;
if (!$id) {
    $stmtF = $pdo->prepare("SELECT folio FROM siniestros WHERE folio LIKE ? ORDER BY id DESC LIMIT 1");
    $stmtF->execute(["%-$anio"]);
    $ultimo = $stmtF->fetchColumn();
    if ($ultimo) {
        $num = (int)explode('-', $ultimo)[0];
        $r['folio'] = str_pad($num + 1, 4, '0', STR_PAD_LEFT) . "-$anio";
    } else {
        $r['folio'] = "0001-$anio";
    }
} else {
    // --- LÓGICA DE CARGA INTEGRADA SIN TOCAR TU CÓDIGO ---
    $stmt = $pdo->prepare("SELECT * FROM siniestros WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        $r = array_merge($r, $data);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Siniestros SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; font-size: 0.82rem; }
        .card-header { background: #1a252f; color: white; border-bottom: 3px solid #f39c12; }
        .section-header { background: #e9ecef; padding: 5px 10px; font-weight: bold; border-left: 4px solid #1a252f; margin: 15px 0 10px 0; font-size: 0.88rem; }
        .bg-folio { background-color: #fff3cd !important; font-weight: bold; color: #856404; }
        .img-preview { max-height: 90px; width: auto; object-fit: contain; border: 2px dashed #ccc; border-radius: 5px; background: #fff; padding: 3px; }
        .footer-signature { margin-top: 30px; border-top: 1px solid #dee2e6; padding-top: 15px; text-align: center; color: #5a6268; font-size: 0.85rem; }
    </style>
</head>
<body class="p-3">
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0"><i class="fas fa-car-crash me-2"></i> REGISTRO INTEGRAL DE SINIESTROS - LAYOUT 2026</h6>
            <a href="index.php" class="btn btn-sm btn-outline-light">Regresar al Index Principal</a>
        </div>
        <div class="card-body py-3">
            <form action="guardar.php" method="POST" id="formSiniestro" enctype="multipart/form-data">
                <input type="hidden" name="id" id="registro_id" value="<?= htmlspecialchars($id) ?>">

                <input type="hidden" name="foto_unidad_actual" value="<?= htmlspecialchars($r['foto_unidad']) ?>">
                <input type="hidden" name="foto_vehiculo_actual" value="<?= htmlspecialchars($r['foto_vehiculo']) ?>">

                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label fw-bold mb-0">Folio de Control</label>
                        <input type="text" name="folio" class="form-control form-control-sm bg-folio" value="<?= htmlspecialchars($r['folio']) ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold mb-0">Mes</label>
                        <select name="mes" class="form-select form-select-sm" required>
                            <option value="">-- SELECCIONAR --</option>
                            <?php foreach(['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'] as $m): ?>
                                <option value="<?= $m ?>" <?= $r['mes']==$m?'selected':'' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-0">No. Expediente</label>
                        <input type="text" name="numero_expediente" class="form-control form-control-sm" value="<?= htmlspecialchars($r['numero_expediente']) ?>">
                    </div>
                </div>

                <div class="row g-2 mt-1">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted">Fecha de Reporte</label>
                        <input type="date" name="fecha_reporte" class="form-control form-control-sm" value="<?= htmlspecialchars($r['fecha_reporte']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted">Hora de Reporte</label>
                        <input type="time" name="hora_reporte" class="form-control form-control-sm" value="<?= htmlspecialchars($r['hora_reporte']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-danger fw-bold">Fecha del Siniestro</label>
                        <input type="date" name="fecha" class="form-control form-control-sm border-danger" value="<?= htmlspecialchars($r['fecha']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-danger fw-bold">Hora del Siniestro</label>
                        <input type="time" name="hora" class="form-control form-control-sm border-danger" value="<?= htmlspecialchars($r['hora']) ?>">
                    </div>
                </div>

                <div class="section-header text-primary">I. IDENTIFICACIÓN DE LA UNIDAD OFICIAL DE LA SSC</div>
                <div class="row g-2">
                    <div class="col-md-2"><label class="form-label mb-0">Marca</label><input type="text" name="marca" class="form-control form-control-sm" value="<?= htmlspecialchars($r['marca']) ?>"></div>
                    <div class="col-md-2"><label class="form-label mb-0">Modelo (Año)</label><input type="text" name="modelo" class="form-control form-control-sm" value="<?= htmlspecialchars($r['modelo']) ?>"></div>
                    <div class="col-md-2"><label class="form-label mb-0">Tipo Unidad</label><input type="text" name="tipo" class="form-control form-control-sm" value="<?= htmlspecialchars($r['tipo']) ?>"></div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold mb-0 text-dark">Económico / Placas</label>
                        <input type="text" name="economico_placas" id="placas" class="form-control form-control-sm border-dark shadow-sm" value="<?= htmlspecialchars($r['economico_placas']) ?>" required>
                    </div>
                    <div class="col-md-2"><label class="form-label mb-0">No. Inventario</label><input type="text" name="no_inventario" class="form-control form-control-sm" value="<?= htmlspecialchars($r['no_inventario']) ?>"></div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold mb-0 text-dark">Número de Serie</label>
                        <input type="text" name="no_serie" id="serie" class="form-control form-control-sm border-dark shadow-sm" value="<?= htmlspecialchars($r['no_serie']) ?>" required>
                    </div>
                </div>

                <div class="section-header text-primary">II. UBICACIÓN EXACTA DEL PERCANCE</div>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label mb-0 fw-bold">Calles / Intersección</label><input type="text" name="calles" class="form-control form-control-sm" value="<?= htmlspecialchars($r['calles']) ?>"></div>
                    <div class="col-md-3"><label class="form-label mb-0 fw-bold">Colonia</label><input type="text" name="colonia" class="form-control form-control-sm" value="<?= htmlspecialchars($r['colonia']) ?>"></div>
                    <div class="col-md-3">
                        <label class="form-label mb-0 fw-bold">Alcaldía / Municipio</label>
                        <select name="alcaldia" class="form-select form-select-sm">
                            <option value="">-- SELECCIONAR --</option>
                            <?php foreach(['ÁLVARO OBREGÓN','AZCAPOTZALCO','BENITO JUÁREZ','COYOACÁN','CUAJIMALPA DE MORELOS','CUAUHTÉMOC','GUSTAVO A. MADERO','IZTACALCO','IZTAPALAPA','LA MAGDALENA CONTRERAS','MIGUEL HIDALGO','MILPA ALTA','TLÁHUAC','TLALPAN','VENUSTIANO CARRANZA','XOCHIMILCO','ESTADO DE MÉXICO','OTRO'] as $alc): ?>
                                <option value="<?= $alc ?>" <?= $r['alcaldia']==$alc?'selected':'' ?>><?= $alc ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="section-header text-primary">III. ADSCRIPCIÓN Y PERSONAL CONDUCTOR</div>
                <div class="row g-2">
                    <div class="col-md-4"><label class="form-label fw-bold mb-0">Area de Adscripción</label><input type="text" name="adscripcion" class="form-control form-control-sm" value="<?= htmlspecialchars($r['adscripcion']) ?>"></div>
                    <div class="col-md-4"><label class="form-label fw-bold mb-0">Nombre Completo del Conductor</label><input type="text" name="nombre_elemento" class="form-control form-control-sm" value="<?= htmlspecialchars($r['nombre_elemento']) ?>"></div>
                    <div class="col-md-2"><label class="form-label mb-0">No. Empleado</label><input type="text" name="cond_no_empleado" class="form-control form-control-sm" value="<?= htmlspecialchars($r['cond_no_empleado']) ?>"></div>
                    <div class="col-md-2"><label class="form-label mb-0">No. Licencia</label><input type="text" name="cond_licencia" class="form-control form-control-sm" value="<?= htmlspecialchars($r['cond_licencia']) ?>"></div>
                </div>

                <div class="section-header text-primary">IV. DETALLES DEL TERCERO INVOLUCRADO</div>
                <div class="row g-2">
                    <div class="col-md-3"><label class="form-label mb-0 text-secondary">Vehículo Tercero (Marca/Mod/Tipo)</label><input type="text" name="vehiculo_3ro" class="form-control form-control-sm" value="<?= htmlspecialchars($r['vehiculo_3ro']) ?>"></div>
                    <div class="col-md-2"><label class="form-label mb-0 text-secondary">Placas Tercero</label><input type="text" name="placas_3ro" class="form-control form-control-sm" value="<?= htmlspecialchars($r['placas_3ro']) ?>"></div>
                    <div class="col-md-3"><label class="form-label mb-0 text-secondary">Aseguradora Tercero</label><input type="text" name="seguro" class="form-control form-control-sm" value="<?= htmlspecialchars($r['seguro']) ?>"></div>
                    <div class="col-md-4"><label class="form-label mb-0 text-secondary">Descripción de Daños Tercero</label><input type="text" name="danos" class="form-control form-control-sm" value="<?= htmlspecialchars($r['danos']) ?>"></div>
                </div>

                <div class="section-header text-primary">V. SEGUIMIENTO DE ASEGURADORA, TALLERES Y ATENCIÓN MÉDICA</div>
                <div class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label mb-0 font-weight-bold text-dark">Aseguradora SSC</label>
                        <select name="aseguradora" class="form-select form-select-sm border-dark">
                            <option value="">-- SELECCIONAR --</option>
                            <?php foreach(['POTOSI','PRIMERO SEGUROS','BANORTE','QUALITAS','TOTAL PARTS','SIN DATO'] as $aseg): ?>
                                <option value="<?= $aseg ?>" <?= $r['aseguradora']==$aseg?'selected':'' ?>><?= $aseg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-0 font-weight-bold text-dark">No. Siniestro Aseguradora</label>
                        <input type="text" name="no_siniestro" class="form-control form-control-sm border-dark" value="<?= htmlspecialchars($r['no_siniestro']) ?>">
                    </div>
                    <div class="col-md-2"><label class="form-label mb-0">Taller Asignado</label><input type="text" name="taller_asignado" class="form-control form-control-sm" value="<?= htmlspecialchars($r['taller_asignado']) ?>"></div>
                    <div class="col-md-2"><label class="form-label mb-0">Taller de Ingreso</label><input type="text" name="taller_ingreso" class="form-control form-control-sm" value="<?= htmlspecialchars($r['taller_ingreso']) ?>"></div>
                    <div class="col-md-2"><label class="form-label mb-0">Hospital / Atención</label><input type="text" name="hospital" class="form-control form-control-sm" value="<?= htmlspecialchars($r['hospital']) ?>"></div>
                    <div class="col-md-2"><label class="form-label mb-0">Carpeta de Inv. (Acta)</label><input type="text" name="carp_investigacion" class="form-control form-control-sm" value="<?= htmlspecialchars($r['carp_investigacion']) ?>"></div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-md-12"><label class="form-label mb-0 text-danger fw-bold">Nombres de Lesionados</label><input type="text" name="lesionados" class="form-control form-control-sm border-danger" value="<?= htmlspecialchars($r['lesionados']) ?>" placeholder="Especificar lesionados o colocar 'SIN LESIONADOS'"></div>
                </div>

                <div class="section-header text-primary">VI. VALIDACIONES, DOCUMENTACIÓN Y FECHAS DE CONTROL</div>
                <div class="row g-2 text-center align-items-end">
                    <?php
                    $checks = [
                        'propio' => 'Propio (SSC)',
                        'arrendado' => 'Arrendado',
                        'declaracion_universal' => 'D. Universal',
                        'pase_medicos' => 'Pase Médicos',
                        'pase_taller' => 'Pase Taller',
                        'graficas' => 'Gráficas',
                        'cuadernillo' => 'Cuadernillo',
                        'visto_bueno' => 'Visto Bueno (C.G.)'
                    ];
                    foreach($checks as $slug => $label): ?>
                    <div class="col">
                        <label class="form-block small fw-bold mb-1"><?= $label ?></label>
                        <select name="<?= $slug ?>" class="form-select form-select-sm">
                            <option value="NO" <?= $r[$slug]=='NO'?'selected':'' ?>>NO</option>
                            <option value="SI" <?= $r[$slug]=='SI'?'selected':'' ?>>SI</option>
                            <option value="PENDIENTE" <?= $r[$slug]=='PENDIENTE'?'selected':'' ?>>PENDIENTE</option>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-0">Fecha Visto Bueno (Gestión)</label>
                        <input type="date" name="fecha_visto_bueno" class="form-control form-control-sm" value="<?= htmlspecialchars($r['fecha_visto_bueno']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-0">Fecha V.B. del Taller</label>
                        <input type="date" name="fecha_vb_taller" class="form-control form-control-sm" value="<?= htmlspecialchars($r['fecha_vb_taller']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-0">Fecha Oficio Recibido (Cuadernillo)</label>
                        <input type="date" name="fecha_oficio_recibido" class="form-control form-control-sm" value="<?= htmlspecialchars($r['fecha_oficio_recibido']) ?>">
                    </div>
                </div>

                <div class="section-header text-primary">VII. EVIDENCIA FOTOGRÁFICA Y OBSERVACIONES</div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label mb-1 text-dark fw-bold"><i class="fas fa-camera me-1"></i> Subir Foto de la Unidad Oficial</label>
                        <input type="file" name="foto_unidad" id="input_foto_unidad" class="form-control form-control-sm" accept="image/*">
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <img id="preview_unidad" class="img-preview" src="<?= !empty($r['foto_unidad']) ? htmlspecialchars($r['foto_unidad']) : 'https://placehold.co/120x90/e2e8f0/64748b?text=Sin+Foto' ?>" alt="Miniatura Unidad">
                            <?php if (!empty($r['foto_unidad'])): ?>
                                <a href="<?= htmlspecialchars($r['foto_unidad']) ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 small"><i class="fas fa-expand"></i> Ampliar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1 text-dark fw-bold"><i class="fas fa-camera me-1"></i> Subir Foto del Vehículo Tercero</label>
                        <input type="file" name="foto_vehiculo" id="input_foto_vehiculo" class="form-control form-control-sm" accept="image/*">
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <img id="preview_vehiculo" class="img-preview" src="<?= !empty($r['foto_vehiculo']) ? htmlspecialchars($r['foto_vehiculo']) : 'https://placehold.co/120x90/e2e8f0/64748b?text=Sin+Foto' ?>" alt="Miniatura Tercero">
                            <?php if (!empty($r['foto_vehiculo'])): ?>
                                <a href="<?= htmlspecialchars($r['foto_vehiculo']) ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 small"><i class="fas fa-expand"></i> Ampliar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-0">Observaciones Cortas</label>
                        <textarea name="observaciones" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($r['observaciones']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-0">Observaciones Generales de Control de Gestión</label>
                        <textarea name="observaciones_generales" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($r['observaciones_generales']) ?></textarea>
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-dark btn-md px-5 shadow border-warning">
                        <i class="fas fa-save me-2"></i>GUARDAR REGISTRO COMPLETO
                    </button>
                </div>
            </form>
        </div>

        <div class="footer-signature mb-3">
            <p class="mb-1">© 2026 Subdirección de Riesgos y Aseguramiento | Control de Gestión</p>
            <p class="fw-bold text-dark">Diseñado por Ing. Juan Manuel Hernandez Lugo</p>
        </div>
    </div>
</div>

<script>
// --- MOTOR DE VISTA PREVIA EN TIEMPO REAL ---
function initThumbnailPreview(idInput, idImgPreview) {
    const fileInput = document.getElementById(idInput);
    const imgPreview = document.getElementById(idImgPreview);

    if (!fileInput || !imgPreview) return;

    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imgPreview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
}
initThumbnailPreview('input_foto_unidad', 'preview_unidad');
initThumbnailPreview('input_foto_vehiculo', 'preview_vehiculo');


// --- VALIDACIÓN DE PLACAS Y SERIE ---
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
                            text: `El ${tipoTexto} "${valor}" ya cuenta con ${data.total} siniestro(s) registrado(s) previamente. ¿Deseas capturar un nuevo siniestro para esta misma unidad?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#1a252f',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, permitir duplicado',
                            cancelButtonText: 'No, limpiar campo'
                        }).then((result) => {
                            if (!result.isConfirmed) {
                                input.value = '';
                                input.focus();
                            }
                        });
                    }
                }).catch(err => console.error("Error validando duplicado:", err));
        }
    });
}
setupValidation('placas', 'economico_placas');
setupValidation('serie', 'no_serie');


// --- VALIDACIÓN: ASEGURADORA + NO. SINIESTRO DUPLICADO ---
function setupSiniestroValidation() {
    const inputSiniestro = document.querySelector('input[name="no_siniestro"]');
    const selectAseguradora = document.querySelector('select[name="aseguradora"]');

    if (!inputSiniestro || !selectAseguradora) return;

    function verificarSiniestroDuplicado() {
        const siniestro = inputSiniestro.value.trim();
        const aseguradora = selectAseguradora.value;
        const idActual = document.getElementById('registro_id').value || 0;

        if (siniestro.length > 2 && aseguradora !== "" && aseguradora !== "SIN DATO") {
            const fd = new FormData();
            fd.append('columna', 'combinacion_siniestro');
            fd.append('no_siniestro', siniestro);
            fd.append('aseguradora', aseguradora);
            fd.append('id', idActual);

            fetch('verificar_duplicado.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.duplicado) {
                        Swal.fire({
                            title: '¡Siniestro Duplicado Detectado!',
                            text: `El No. Siniestro "${siniestro}" ya se encuentra registrado con la aseguradora "${aseguradora}". Por favor, verifique sus datos.`,
                            icon: 'error',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Corregir información'
                        }).then(() => {
                            inputSiniestro.value = '';
                            setTimeout(() => inputSiniestro.focus(), 100);
                        });
                    }
                }).catch(err => console.error("Error validando siniestro duplicado:", err));
        }
    }

    inputSiniestro.addEventListener('blur', verificarSiniestroDuplicado);
    selectAseguradora.addEventListener('change', verificarSiniestroDuplicado);
}
setupSiniestroValidation();

// Convertir a Mayúsculas automáticamente
document.querySelectorAll('input[type="text"], textarea').forEach(i => {
    i.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
});
</script>
</body>
</html>
