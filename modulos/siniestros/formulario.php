<?php
require_once "../../config/conexion.php";
$id = $_GET['id'] ?? null;

// Inicialización usando los nombres reales de tu Base de Datos
$r = [
    'mes' => '', 'folio' => '',
    'fecha_reporte' => date('Y-m-d'), 'hora_reporte' => date('H:i'),
    'fecha' => '', 'hora' => '', 
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
$db = isset($conexion) ? $conexion : $pdo;

if (!$id) {
    $stmtF = $db->prepare("SELECT folio FROM siniestros WHERE folio LIKE ? ORDER BY id DESC LIMIT 1");
    $stmtF->execute(["%-$anio"]);
    $ultimo = $stmtF->fetchColumn();
    if ($ultimo) {
        $num = (int)explode('-', $ultimo)[0];
        $r['folio'] = str_pad($num + 1, 4, '0', STR_PAD_LEFT) . "-$anio";
    } else {
        $r['folio'] = "0001-$anio";
    }
} else {
    $stmt = $db->prepare("SELECT * FROM siniestros WHERE id = ?");
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
        .img-preview { max-height: 90px; width: 90px; object-fit: cover; border: 2px dashed #ccc; border-radius: 5px; background: #fff; padding: 3px; cursor: pointer; transition: 0.2s; }
        .img-preview:hover { opacity: 0.8; border-color: #1a252f; }
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
                        <select name="aseguradora" id="aseguradora" class="form-select form-select-sm border-dark">
                            <option value="">-- SELECCIONAR --</option>
                            <?php foreach(['POTOSI','PRIMERO SEGUROS','BANORTE','QUALITAS','TOTAL PARTS','SIN DATO'] as $aseg): ?>
                                <option value="<?= $aseg ?>" <?= $r['aseguradora']==$aseg?'selected':'' ?>><?= $aseg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-0 font-weight-bold text-dark">No. Siniestro Aseguradora</label>
                        <input type="text" name="no_siniestro" id="no_siniestro" class="form-control form-control-sm border-dark" value="<?= htmlspecialchars($r['no_siniestro']) ?>">
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
                        <input type="date" name="fecha_vb_taller" class="form-control form-control-sm" value="<?= htmlspecialchars($r['fecha_vb_taller']) ?>"></div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-0">Fecha Oficio Recibido (Cuadernillo)</label>
                        <input type="date" name="fecha_oficio_recibido" class="form-control form-control-sm" value="<?= htmlspecialchars($r['fecha_oficio_recibido']) ?>">
                    </div>
                </div>

                <div class="section-header text-primary">VII. EVIDENCIA FOTOGRÁFICA Y OBSERVACIONES</div>
                <div class="row g-2">
                    <!-- Unidad Oficial -->
                    <div class="col-md-6">
                        <label class="form-label mb-1 text-dark fw-bold"><i class="fas fa-camera me-1"></i> Fotos de la Unidad Oficial</label>
                        <input type="file" name="foto_unidad[]" id="input_foto_unidad" class="form-control form-control-sm" accept="image/*" multiple>
                        <input type="hidden" name="foto_unidad_actual" value="<?= htmlspecialchars($r['foto_unidad']) ?>">
                        <small class="text-muted">Haz clic en cualquier imagen para verla en grande:</small>
                        <div class="mt-2 d-flex flex-wrap gap-2" id="container_preview_unidad">
                            <?php if (!empty($r['foto_unidad'])): ?>
                                <?php
                                $fotos_u = json_decode($r['foto_unidad'], true) ?: [$r['foto_unidad']];
                                foreach($fotos_u as $fu):
                                    if(!empty(trim($fu))):
                                ?>
                                    <div class="position-relative border p-1 rounded bg-white text-center">
                                        <img class="img-preview d-block mb-1" src="<?= htmlspecialchars($fu) ?>" alt="Unidad" onclick="verImagenGrande(this.src)">
                                        <div class="form-check form-check-inline small text-danger mb-0">
                                            <input class="form-check-input" type="checkbox" name="eliminar_foto_unidad[]" value="<?= htmlspecialchars($fu) ?>" id="del_u_<?= md5($fu) ?>">
                                            <label class="form-check-label" for="del_u_<?= md5($fu) ?>">Eliminar</label>
                                        </div>
                                    </div>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Vehículo Tercero -->
                    <div class="col-md-6">
                        <label class="form-label mb-1 text-dark fw-bold"><i class="fas fa-camera me-1"></i> Fotos del Vehículo Tercero</label>
                        <input type="file" name="foto_vehiculo[]" id="input_foto_vehiculo" class="form-control form-control-sm" accept="image/*" multiple>
                        <input type="hidden" name="foto_vehiculo_actual" value="<?= htmlspecialchars($r['foto_vehiculo']) ?>">
                        <small class="text-muted">Haz clic en cualquier imagen para verla en grande:</small>
                        <div class="mt-2 d-flex flex-wrap gap-2" id="container_preview_vehiculo">
                            <?php if (!empty($r['foto_vehiculo'])): ?>
                                <?php
                                $fotos_t = json_decode($r['foto_vehiculo'], true) ?: [$r['foto_vehiculo']];
                                foreach($fotos_t as $ft):
                                    if(!empty(trim($ft))):
                                ?>
                                    <div class="position-relative border p-1 rounded bg-white text-center">
                                        <img class="img-preview d-block mb-1" src="<?= htmlspecialchars($ft) ?>" alt="Tercero" onclick="verImagenGrande(this.src)">
                                        <div class="form-check form-check-inline small text-danger mb-0">
                                            <input class="form-check-input" type="checkbox" name="eliminar_foto_vehiculo[]" value="<?= htmlspecialchars($ft) ?>" id="del_t_<?= md5($ft) ?>">
                                            <label class="form-check-label" for="del_t_<?= md5($ft) ?>">Eliminar</label>
                                        </div>
                                    </div>
                                <?php
                                    endif;
                                endforeach;
                                ?>
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
                        <label class="form-label fw-bold mb-0">Observaciones Generales</label>
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
            <p class="mb-1">© 2026 Subdirección de Riesgos y Aseguramiento | Registro de Siniestros</p>
            <p class="fw-bold text-dark">Diseñado por Ing. Juan Manuel Hernandez Lugo</p>
        </div>
    </div>
</div>

<!-- Modal para ver la Imagen en Grande -->
<div class="modal fade" id="modalImagenGrande" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 py-2">
                <h5 class="modal-title text-white fs-6">Vista Detallada de Imagen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-1">
                <img id="imagenModalAmpliada" src="" class="img-fluid rounded" style="max-height: 80vh;" alt="Imagen Ampliada">
            </div>
        </div>
    </div>
</div>

<!-- Scripts de Bootstrap y Funcionalidad -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Función para abrir la imagen en grande mediante el modal
function verImagenGrande(url) {
    document.getElementById('imagenModalAmpliada').src = url;
    var myModal = new bootstrap.Modal(document.getElementById('modalImagenGrande'));
    myModal.show();
}

// Previsualización instantánea al seleccionar archivos nuevos desde el equipo
function habilitarPrevisualizacion(inputId, containerId) {
    const input = document.getElementById(inputId);
    const container = document.getElementById(containerId);
    if (!input || !container) return;

    input.addEventListener('change', function(e) {
        const archivosNuevos = e.target.files;
        for (let i = 0; i < archivosNuevos.length; i++) {
            const file = archivosNuevos[i];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const div = document.createElement('div');
                    div.className = 'position-relative border border-success p-1 rounded bg-white text-center shadow-sm';
                    div.innerHTML = `
                        <span class="badge bg-success position-absolute top-0 start-0 translate-middle-y small" style="font-size: 9px;">Nuevo</span>
                        <img class="img-preview d-block mb-1" src="${event.target.result}" alt="Preview" onclick="verImagenGrande('${event.target.result}')">
                        <small class="text-success d-block" style="font-size: 10px;">Lista para subir</small>
                    `;
                    container.appendChild(div);
                }
                reader.readAsDataURL(file);
            }
        }
    });
}

habilitarPrevisualizacion('input_foto_unidad', 'container_preview_unidad');
habilitarPrevisualizacion('input_foto_vehiculo', 'container_preview_vehiculo');

// Validaciones y Duplicados AJAX
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

// Validación específica para la combinación de Aseguradora + Número de Siniestro
function setupValidationSiniestro() {
    const inputSiniestro = document.getElementById('no_siniestro');
    const selectAseguradora = document.getElementById('aseguradora');
    if (!inputSiniestro || !selectAseguradora) return;

    const verificarSiniestroDuplicado = function() {
        const numSiniestro = inputSiniestro.value.trim();
        const aseguradora = selectAseguradora.value;
        const idActual = document.getElementById('registro_id').value || 0;

        // Validamos si ambos campos tienen información relevante
        if (numSiniestro.length > 1 && aseguradora !== '') {
            const fd = new FormData();
            // Enviamos el número de siniestro y la aseguradora para validarlo en el backend
            fd.append('valor', numSiniestro);
            fd.append('columna', 'no_siniestro');
            fd.append('aseguradora', aseguradora); // Dato extra por si tu archivo verificar_duplicado.php lo requiere
            fd.append('id', idActual);

            fetch('verificar_duplicado.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.duplicado) {
                        Swal.fire({
                            title: '¡Siniestro Duplicado!',
                            text: `El número de siniestro "${numSiniestro}" con la aseguradora "${aseguradora}" ya se encuentra registrado en el sistema.`,
                            icon: 'error',
                            confirmButtonColor: '#1a252f',
                            confirmButtonText: 'Entendido'
                        }).then(() => {
                            inputSiniestro.value = '';
                            inputSiniestro.focus();
                        });
                    }
                }).catch(err => console.error("Error validando duplicado de siniestro:", err));
        }
    };

    inputSiniestro.addEventListener('blur', verificarSiniestroDuplicado);
    selectAseguradora.addEventListener('change', verificarSiniestroDuplicado);
}

// Inicializar todas las validaciones
setupValidation('placas', 'economico_placas');
setupValidation('serie', 'no_serie');
setupValidationSiniestro();

// Mayúsculas automáticas
document.querySelectorAll('input[type="text"], textarea').forEach(i => {
    i.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
});
</script>
</body>
</html>
