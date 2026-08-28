<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
$usuario_activo = $_SESSION['usuario'];
$rol_activo = $_SESSION['rol'] ?? 'USER';
$id_usuario_sesion = $_SESSION['id_usuario'] ?? 0;

$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $cat_areas = $pdo->query("SELECT id_area, nombre_area FROM catalogo_areas ORDER BY nombre_area ASC")->fetchAll(PDO::FETCH_ASSOC);
    $usuarios  = $pdo->query("SELECT id_usuario, nombre_completo FROM usuarios ORDER BY nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Extraer los roles definidos en el ENUM de la tabla usuarios de forma dinámica
    $roles_query = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'rol'")->fetch(PDO::FETCH_ASSOC);
    preg_match("/^enum\((.*)\)$/", $roles_query['Type'], $matches);
    $cat_roles = [];
    if (isset($matches[1])) {
        foreach (explode(',', $matches[1]) as $value) {
            $cat_roles[] = trim($value, "'");
        }
    } else {
        $cat_roles = ['CAPTURISTA', 'ADMIN', 'SUPERVISOR', 'CONSULTA'];
    }

} catch (PDOException $e) {
    die("Error de conexión al inicializar catálogos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SSC | Control de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; background-image: linear-gradient(rgba(244, 246, 249, 0.94), rgba(244, 246, 249, 0.94)), url('https://www.transparenttextures.com/patterns/lined-paper-2.png'); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 0.85rem; }
        .navbar-ssc { background-color: #861532; color: white; padding: 0.4rem 1rem; }
        .card-ssc { border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 0.75rem; }
        .visor-container { background-color: #4e4e4e; border-radius: 4px; padding: 0; overflow: hidden; }
        .table-sm th, .table-sm td { padding: 0.5rem 0.4rem; vertical-align: top; }
        .user-avatar { width: 30px; height: 30px; object-fit: cover; border-radius: 50%; border: 1px solid #ddd; }
        .responsable-avatar { width: 22px; height: 22px; object-fit: cover; border-radius: 50%; border: 1px solid #ccc; margin-right: 4px; }
        @keyframes pulse-danger {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }
        .animate-pulse { animation: pulse-danger 1.5s infinite ease-in-out; }

        .footer-firma {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #ffffff;
            border-top: 3px solid #861532;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.05);
        }
        .firma-avatar {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #861532;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body onload="cargarRegistros()">

<nav class="navbar navbar-ssc navbar-expand-lg mb-2">
    <div class="container-fluid">
        <span class="navbar-brand text-white fw-bold fs-6"><i class="fa-solid fa-shield-halved me-2"></i>SSC | Control de Gestión</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-dark px-2 py-1.5 fs-7"><i class="fa-solid fa-user me-1 text-warning"></i><?php echo htmlspecialchars($usuario_activo); ?> (<?php echo htmlspecialchars($rol_activo); ?>)</span>
            
            <!-- SOLO VISIBLE SI ES ADMIN -->
            <?php if ($rol_activo === 'ADMIN'): ?>
                <button class="btn btn-outline-light btn-sm px-2 py-0.5" style="font-size:0.8rem;" onclick="abrirModalImportar()"><i class="fa-solid fa-file-excel me-1"></i>Importar</button>
            <?php endif; ?>

            <!-- EXPORTAR RESPALDO EXCEL -->
            <a href="exportar_excel.php" class="btn btn-success btn-sm text-white fw-bold px-2 py-0.5" style="font-size:0.8rem; background-color: #1e7e34; border-color: #1e7e34;" title="Descargar Respaldo en Excel">
                <i class="fa-solid fa-file-export me-1"></i>Exportar Respaldo
            </a>

            <button class="btn btn-outline-light btn-sm px-2 py-0.5" style="font-size:0.8rem;" onclick="window.open('reporte_folios.php', '_blank')"><i class="fa-solid fa-file-pdf me-1"></i>Reporte</button>

            <!-- GESTIÓN DE PERSONAL SOLO PARA ADMIN -->
            <?php if ($rol_activo === 'ADMIN'): ?>
                <button class="btn btn-warning btn-sm text-dark fw-bold px-2 py-0.5" style="font-size:0.8rem;" onclick="abrirModalPersonal()"><i class="fa-solid fa-users me-1"></i>Personal</button>
            <?php endif; ?>

            <button class="btn btn-danger btn-sm text-white fw-bold px-2 py-0.5" style="font-size:0.8rem;" onclick="abrirModalNuevo()"><i class="fa-solid fa-circle-plus me-1"></i>Nuevo Oficio</button>
            <a href="logout.php" class="btn btn-danger btn-sm px-2 py-0.5" style="font-size:0.8rem;"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </div>
</nav>

<div class="container-fluid px-2">
    <div class="row g-2 align-items-start">
        <div class="col-lg-7">
            <div class="card card-ssc bg-white p-2">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-0" style="font-size:0.8rem;">Búsqueda rápida unificada:</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="txt_buscar" class="form-control" placeholder="Número de oficio, asunto, titular o ID Folio..." onkeyup="cargarRegistros()" oninput="this.value = this.value.toUpperCase()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-0" style="font-size:0.8rem;">Área Remitente:</label>
                        <select id="cmb_filtro_area" class="form-select form-select-sm" onchange="cargarRegistros()">
                            <option value="0">-- Mostrar Todas --</option>
                            <?php foreach($cat_areas as $area): ?>
                                <option value="<?php echo $area['id_area']; ?>"><?php echo htmlspecialchars($area['nombre_area']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card card-ssc bg-white p-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark" style="font-size:0.85rem;"><i class="fa-solid fa-folder-open me-1 text-secondary"></i>CORRESPONDENCIA EN MONITOREO</span>
                    <div class="d-flex gap-2 align-items-center">
                        <!-- VACIAR BASE DE DATOS SOLO VISIBLE PARA ADMIN -->
                        <?php if ($rol_activo === 'ADMIN'): ?>
                            <button class="btn btn-danger btn-sm text-white px-2 py-0.5" style="font-size:0.75rem;" onclick="vaciarBaseDatos()"><i class="fa-solid fa-trash-can me-1"></i>Vaciar BD</button>
                        <?php endif; ?>
                        <span class="badge bg-dark fs-7 px-2 py-1" id="lbl_total">Total: 0</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem; table-layout: fixed; width: 100%;">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th class="ps-2" style="width: 10%;">ID Folio</th>
                                <th style="width: 22%;">Datos del Oficio</th>
                                <th style="width: 30%;">Asunto / Procedencia</th>
                                <th style="width: 18%;">Responsable</th>
                                <th style="width: 10%;">Plazo</th>
                                <th class="text-center" style="width: 18%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_registros"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA CON STICKY TOP -->
        <div class="col-lg-5 sticky-top" style="top: 10px; z-index: 1020;">
            <div class="card card-ssc mb-2">
                <div class="card-header bg-dark text-white py-1 d-flex justify-content-between align-items-center" style="font-size:0.8rem;">
                    <span class="fw-bold"><i class="fa-solid fa-file-pdf me-1 text-warning"></i>1. DOCUMENTO DE ORIGEN</span>
                    <span id="badge_folio_origen" class="badge bg-secondary">-</span>
                </div>
                <div class="card-body p-0 visor-container">
                    <iframe id="visor_pdf_origen" src="" width="100%" height="380px" style="border:none; display:block;"></iframe>
                </div>
            </div>
            <div class="card card-ssc">
                <div class="card-header bg-dark text-white py-1 d-flex justify-content-between align-items-center" style="font-size:0.8rem;">
                    <span class="fw-bold"><i class="fa-solid fa-file-signature me-1 text-success"></i>2. EVIDENCIA DE CONCLUSIÓN</span>
                    <span id="badge_status_conclusion" class="badge bg-secondary">Ninguno</span>
                </div>
                <div class="card-body p-0 visor-container">
                    <iframe id="visor_pdf_conclusion" src="" width="100%" height="380px" style="border:none; display:block;"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistro" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formRegistro" class="modal-content" enctype="multipart/form-data">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title fw-bold" id="modalTitle">Nuevo Oficio</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-2" style="font-size:0.8rem;">
                <input type="hidden" id="id_registro" name="id_registro">
                <div class="col-md-6">
                    <label class="form-label fw-bold mb-0">Datos del Oficio:</label>
                    <input type="text" id="numero_oficio" name="numero_oficio" class="form-control form-control-sm" required oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold mb-0">Días de Término:</label>
                    <input type="number" id="dias_termino" name="dias_termino" class="form-control form-control-sm" value="5" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold mb-0">Fecha del Oficio:</label>
                    <input type="date" id="fecha_oficio" name="fecha_oficio" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold mb-0">Fecha de Recepción:</label>
                    <input type="date" id="fecha_recepcion" name="fecha_recepcion" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold mb-0">Titular que Firma:</label>
                    <input type="text" id="titular" name="titular" class="form-control form-control-sm" required oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold mb-0">Cargo del Remitente:</label>
                    <input type="text" id="cargo" name="cargo" class="form-control form-control-sm" required oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold mb-0">Servidor Público Asignado:</label>
                    <select id="id_usuario_asignado" name="id_usuario_asignado" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach($usuarios as $u): ?>
                            <option value="<?php echo $u['id_usuario']; ?>"><?php echo htmlspecialchars($u['nombre_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold mb-0">Asunto / Extracto de Instrucción:</label>
                    <textarea id="asunto" name="asunto" class="form-control form-control-sm" rows="2" required oninput="this.value = this.value.toUpperCase()"></textarea>
                </div>

                <div class="col-md-6 mt-2">
                    <label class="form-label fw-bold mb-0">1. Documento PDF Digitalizado Soporte:</label>
                    <input type="file" id="pdf_soporte" name="pdf_soporte" class="form-control form-control-sm" accept=".pdf">
                </div>
                <div class="col-md-6 mt-2">
                    <label class="form-label fw-bold mb-0">2. Documento PDF Evidencia Conclusión:</label>
                    <input type="file" id="pdf_conclusion" name="pdf_conclusion" class="form-control form-control-sm" accept=".pdf">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold mb-0 text-success">Detalle / Forma de Conclusión:</label>
                    <textarea id="observaciones_conclusion" name="observaciones_conclusion" class="form-control form-control-sm" rows="2" placeholder="ESCRIBE CÓMO O BAJO QUÉ TÉRMINOS SE CONCLUYÓ ESTE FOLIO..." oninput="this.value = this.value.toUpperCase()"></textarea>
                </div>

                <!-- RUBROS EXACTOS DE LA PAPELETA IMPRESA -->
                <div class="col-12 mt-2 border-top pt-2">
                    <span class="badge bg-secondary mb-1" style="font-size:0.75rem;">Datos de Asignación en Papeleta Oficial</span>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold mb-0 text-dark"><i class="fa-solid fa-building-flag me-1 text-danger"></i>Área / Turnado por:</label>
                    <select id="id_turnado_por" name="id_turnado_por" class="form-select form-select-sm fw-bold" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach($cat_areas as $area): ?>
                            <option value="<?php echo $area['id_area']; ?>"><?php echo htmlspecialchars($area['nombre_area']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold mb-0 text-dark"><i class="fa-solid fa-gavel me-1 text-danger"></i>Instrucción / Acción:</label>
                    <select id="accion_papeleta" name="accion_papeleta" class="form-select form-select-sm fw-bold" required>
                        <option value="">-- Seleccionar --</option>
                        <option value="PARA ARCHIVO">PARA ARCHIVO</option>
                        <option value="PARA TRÁMITE">PARA TRÁMITE</option>
                        <option value="CONOCIMIENTO">CONOCIMIENTO</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold mb-0 text-dark"><i class="fa-solid fa-circle-exclamation me-1 text-danger"></i>Prioridad:</label>
                    <select id="prioridad" name="prioridad" class="form-select form-select-sm fw-bold" required>
                        <option value="NORMAL">NORMAL</option>
                        <option value="URGENTE">URGENTE</option>
                        <option value="EXTRA-URGENTE">EXTRA-URGENTE</option>
                    </select>
                </div>

            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar Registro</button>
            </div>
        </form>
    </div>
</div>

<?php if ($rol_activo === 'ADMIN'): ?>
<div class="modal fade" id="modalPersonal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-warning py-2"><h6 class="modal-title fw-bold">Gestión de Personal</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><button class="btn btn-success btn-sm mb-2" onclick="nuevoUsuario()"><i class="fa-solid fa-plus"></i> Nuevo Usuario</button><table class="table table-sm table-hover"><thead><tr><th>Foto</th><th>Nombre</th><th>Rol</th><th>Estatus</th><th>Acciones</th></tr></thead><tbody id="tabla_usuarios_body"><tr><td colspan="5">Cargando...</td></tr></tbody></table></div></div></div></div>

<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <form id="formEditarUsuario" class="modal-content" enctype="multipart/form-data">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title">Editar/Crear Usuario</h6>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_id" name="id_usuario">
                <input type="text" name="nombre_completo" id="edit_nombre" class="form-control mb-2" placeholder="Nombre completo" required>
                <input type="text" name="username" id="edit_username" class="form-control mb-2" placeholder="Usuario" required>
                <input type="text" name="correo" id="edit_correo" class="form-control mb-2" placeholder="Correo" required>
                <input type="password" name="password" id="edit_password" class="form-control mb-2" placeholder="Nueva contraseña (opcional)">
                
                <label class="form-label fw-bold">Rol:</label>
                <select name="rol" id="edit_rol" class="form-select mb-2" required>
                    <?php foreach($cat_roles as $rol_item): ?>
                        <option value="<?php echo htmlspecialchars($rol_item); ?>"><?php echo htmlspecialchars($rol_item); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label class="form-label">Foto de perfil:</label>
                <input type="file" name="foto_perfil" class="form-control">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<footer class="footer-firma text-center">
    <div class="container d-flex flex-column align-items-center justify-content-center gap-2">
        <img src="uploads/perfiles/juan_hernandez.jpg" onerror="this.src='https://via.placeholder.com/50?text=JMH'" alt="Ing. Juan Manuel Hernández Lugo" class="firma-avatar">
        <div class="fw-bold text-dark" style="font-size: 0.85rem;">
            Diseñado por <span style="color: #861532;">Ing. Juan Manuel Hernández Lugo</span>
        </div>
        <div class="text-muted" style="font-size: 0.75rem;">
            &copy; <?php echo date('Y'); ?> SSC - Todos los derechos reservados.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ROL_ACTIVO = "<?php echo $rol_activo; ?>";
    const ID_USUARIO_SESION = parseInt("<?php echo $id_usuario_sesion; ?>");

    let modalRegObj = new bootstrap.Modal(document.getElementById('modalRegistro'));
    let modalPersonalObj = document.getElementById('modalPersonal') ? new bootstrap.Modal(document.getElementById('modalPersonal')) : null;
    let modalEditObj = document.getElementById('modalEditarUsuario') ? new bootstrap.Modal(document.getElementById('modalEditarUsuario')) : null;

    let registrosGlobales = [];

    function cargarRegistros() {
        let busqueda = document.getElementById('txt_buscar').value;
        let area = document.getElementById('cmb_filtro_area').value;
        fetch(`buscar_registros.php?q=${encodeURIComponent(busqueda)}&area=${area}`)
        .then(res => res.json())
        .then(data => {
            registrosGlobales = data;
            let html = data.map(r => {
                let imgResponsable = (r.foto_perfil && r.foto_perfil !== '') ? 'uploads/perfiles/' + r.foto_perfil : 'https://via.placeholder.com/25';
                let btnPapeleta = `<a href="generar_papeleta.php?id=${r.id_registro}" target="_blank" class="btn btn-sm btn-secondary py-0 px-1 ms-1" style="background-color: #861532; border-color: #861532;" title="Papeleta Oficial"><i class="fa-solid fa-print"></i></a>`;
                let btnTrazabilidad = `<a href="trazabilidad.php?id=${r.id_registro}" target="_blank" class="btn btn-sm btn-dark py-0 px-1 ms-1" title="Ver Trazabilidad / Historial"><i class="fa-solid fa-timeline"></i></a>`;

                let botonEditar = '';
                let botonEliminar = '';

                // PERMISO DE EDICIÓN: PERMITIR A ADMIN, A SUPERVISORES (O NUEVOS ROLES), O SI EL OFICIO FUE ASIGNADO A ESE USUARIO
                if (ROL_ACTIVO === 'ADMIN' || ROL_ACTIVO === 'SUPERVISOR' || ROL_ACTIVO !== 'CAPTURISTA' || ID_USUARIO_SESION === parseInt(r.id_usuario_asignado)) {
                    botonEditar = `<button class="btn btn-sm btn-warning py-0 px-1 ms-1" onclick="abrirEditarFolio(${r.id_registro})" title="Editar Oficio"><i class="fa-solid fa-marker"></i></button>`;
                }

                // PERMISO DE ELIMINACIÓN: SOLO PARA ADMIN
                if (ROL_ACTIVO === 'ADMIN') {
                    botonEliminar = `<button class="btn btn-sm btn-danger py-0 px-1 ms-1" onclick="eliminarFolio(${r.id_registro})" title="Eliminar Oficio"><i class="fa-solid fa-trash-can"></i></button>`;
                }

                let pdfParam = (r.pdf_soporte && r.pdf_soporte !== 'null') ? r.pdf_soporte.replace(/'/g, "\\'") : '';
                let pdfConclusionParam = (r.pdf_conclusion && r.pdf_conclusion !== 'null' ? r.pdf_conclusion.replace(/'/g, "\\'") : '');

                let diasRestantes = parseInt(r.dias_termino) || 0;
                let estiloPlazo = '';
                let badgeTiempo = '';

                let estaConcluido = (r.pdf_conclusion && r.pdf_conclusion !== 'null' && r.pdf_conclusion.trim() !== '');

                if (!estaConcluido) {
                    if (diasRestantes <= 0) {
                        estiloPlazo = 'color: #dc3545; font-weight: bold;';
                        badgeTiempo = `<br><span class="badge bg-danger text-white animate-pulse" style="font-size:0.65rem; padding: 2px 4px;"><i class="fa-solid fa-triangle-exclamation me-1"></i>FUERA DE TIEMPO</span>`;
                    } else if (diasRestantes <= 2) {
                        estiloPlazo = 'color: #dc3545; font-weight: bold;';
                    }
                }

                let badgeEstatusTabla = '';
                if (estaConcluido) {
                    badgeEstatusTabla = `<span class="badge bg-success d-inline-block mt-1" style="font-size:0.65rem;">CONCLUIDO</span>`;
                } else {
                    badgeEstatusTabla = `<span class="badge bg-secondary d-inline-block mt-1" style="font-size:0.65rem;">PENDIENTE</span>`;
                }

                return `<tr>
                    <td class="ps-2 fw-bold text-secondary">${r.id_registro}</td>
                    <td>
                        <div class="fw-bold">${r.numero_oficio}</div>
                        <div class="text-muted" style="font-size:0.75rem;">${r.fecha_oficio}</div>
                        ${badgeEstatusTabla}
                    </td>
                    <td><div class="text-truncate" style="max-width: 230px;" title="${r.asunto}">${r.asunto}</div></td>
                    <td class="d-flex align-items-center">
                        <img src="${imgResponsable}" class="responsable-avatar">
                        <span>${r.nombre_usuario || 'N/A'}</span>
                    </td>
                    <td style="${estiloPlazo}">${diasRestantes} días ${badgeTiempo}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-info py-0 px-1" onclick="visualizarAmbosDocumentos('${pdfParam}', '${pdfConclusionParam}', '${r.id_registro}')" title="Ver PDFs"><i class="fa-solid fa-eye"></i></button>
                        ${btnPapeleta}
                        ${btnTrazabilidad}
                        ${botonEditar}
                        ${botonEliminar}
                    </td>
                </tr>`;
            }).join('');
            document.getElementById('tbody_registros').innerHTML = html;
            document.getElementById('lbl_total').innerText = 'Total: ' + data.length;
        });
    }

    function visualizarAmbosDocumentos(archivoPdf, archivoConclusion, idFolio) {
        let visorOrigen = document.getElementById('visor_pdf_origen');
        let badgeOrigen = document.getElementById('badge_folio_origen');
        let visorConclusion = document.getElementById('visor_pdf_conclusion');
        let badgeConclusion = document.getElementById('badge_status_conclusion');

        if (archivoPdf && archivoPdf !== 'null' && archivoPdf !== 'undefined' && archivoPdf.trim() !== '') {
            visorOrigen.src = "uploads/oficios/" + archivoPdf;
            badgeOrigen.innerText = "Folio: " + idFolio;
        } else {
            visorOrigen.src = "";
            badgeOrigen.innerText = "-";
        }

        if (archivoConclusion && archivoConclusion !== 'null' && archivoConclusion !== 'undefined' && archivoConclusion.trim() !== '') {
            visorConclusion.src = "uploads/oficios/" + archivoConclusion;
            badgeConclusion.className = "badge bg-success text-white";
            badgeConclusion.innerText = "Concluido";
        } else {
            visorConclusion.src = "";
            badgeConclusion.className = "badge bg-secondary text-white";
            badgeConclusion.innerText = "Pendiente";
        }
    }

    function abrirEditarFolio(idRegistro) {
        let objetoRegistro = registrosGlobales.find(item => item.id_registro == idRegistro);
        if(!objetoRegistro) return;

        document.getElementById('formRegistro').reset();
        document.getElementById('modalTitle').innerText = 'Editar Oficio (Folio: ' + objetoRegistro.id_registro + ')';

        document.getElementById('id_registro').value = objetoRegistro.id_registro;
        document.getElementById('numero_oficio').value = objetoRegistro.numero_oficio;
        document.getElementById('dias_termino').value = objetoRegistro.dias_termino;
        document.getElementById('fecha_oficio').value = objetoRegistro.fecha_oficio;
        document.getElementById('fecha_recepcion').value = objetoRegistro.fecha_recepcion;
        document.getElementById('titular').value = objetoRegistro.titular;
        document.getElementById('cargo').value = objetoRegistro.cargo;
        document.getElementById('id_usuario_asignado').value = objetoRegistro.id_usuario_asignado;
        document.getElementById('asunto').value = objetoRegistro.asunto;
        document.getElementById('observaciones_conclusion').value = objetoRegistro.observaciones_conclusion || '';

        document.getElementById('id_turnado_por').value = objetoRegistro.id_turnado_por || '';
        document.getElementById('accion_papeleta').value = objetoRegistro.accion_papeleta || '';
        document.getElementById('prioridad').value = objetoRegistro.prioridad || 'NORMAL';

        modalRegObj.show();
    }

    function eliminarFolio(id) {
        if (ROL_ACTIVO !== 'ADMIN') return;
        if (confirm('¿Estás completamente seguro de eliminar el Folio ' + id + '? Esta acción borrará el registro permanentemente.')) {
            fetch(`eliminar_registro.php?id=${id}`, { method: 'GET' })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') { cargarRegistros(); }
            })
            .catch(err => { alert('Error al conectar con el servidor para eliminar.'); });
        }
    }

    function abrirModalNuevo() {
        document.getElementById('formRegistro').reset();
        document.getElementById('id_registro').value = '';
        document.getElementById('prioridad').value = 'NORMAL';
        document.getElementById('modalTitle').innerText = 'Nuevo Oficio';
        fetch('obtener_siguiente_oficio.php').then(res => res.json()).then(data => { if(data.siguiente) document.getElementById('numero_oficio').value = data.siguiente; });
        modalRegObj.show();
    }

    function abrirModalPersonal() {
        if (ROL_ACTIVO !== 'ADMIN' || !modalPersonalObj) return;
        modalPersonalObj.show();
        fetch('listar_usuarios.php').then(res => res.json()).then(data => {
            let html = data.map(u => {
                let img = (u.foto_perfil && u.foto_perfil !== '') ? 'uploads/perfiles/' + u.foto_perfil : 'https://via.placeholder.com/35';
                return `<tr>
                    <td><img src="${img}" class="user-avatar"></td>
                    <td>${u.nombre_completo}</td><td>${u.rol}</td><td>${u.activo == 1 ? 'Activo' : 'Inactivo'}</td>
                    <td>
                        <button class="btn btn-sm btn-info me-1" onclick="editarUsuario(${u.id_usuario}, '${u.nombre_completo}', '${u.username}', '${u.correo}', '${u.rol}')"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${u.id_usuario})"><i class="fa-solid fa-trash-can"></i></button>
                    </td>
                </tr>`;
            }).join('');
            document.getElementById('tabla_usuarios_body').innerHTML = html;
        });
    }

    function nuevoUsuario() {
        if (ROL_ACTIVO !== 'ADMIN' || !modalEditObj) return;
        document.getElementById('formEditarUsuario').reset();
        document.getElementById('edit_id').value = '';
        document.getElementById('edit_password').setAttribute('required', 'required');
        modalEditObj.show();
    }

    function editarUsuario(id, nombre, user, correo, rol) {
        if (ROL_ACTIVO !== 'ADMIN' || !modalEditObj) return;
        document.getElementById('formEditarUsuario').reset();
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_username').value = user;
        document.getElementById('edit_correo').value = correo;
        document.getElementById('edit_rol').value = rol;
        document.getElementById('edit_password').removeAttribute('required');
        modalEditObj.show();
    }

    function eliminarUsuario(id) {
        if (ROL_ACTIVO !== 'ADMIN') return;
        if (confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
            fetch(`eliminar_usuario.php?id=${id}`, { method: 'GET' })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') { abrirModalPersonal(); }
            });
        }
    }

    if (document.getElementById('formEditarUsuario')) {
        document.getElementById('formEditarUsuario').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('editar_usuario.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if(data.status === 'success') { modalEditObj.hide(); abrirModalPersonal(); }
            });
        });
    }

    document.getElementById('formRegistro').addEventListener('submit', async function(e) {
        e.preventDefault();
        let idActual = document.getElementById('id_registro').value;
        let formData = new FormData(this);

        if (idActual && idActual !== '') {
            ejecutarEnvioRegistro(formData, idActual);
            return;
        }

        try {
            let respuesta = await fetch('verificar_huecos.php');
            let data = await respuesta.json();

            if (data.status === 'success' && data.tiene_hueco) {
                let opcionesHtml = `<option value="${data.folio_secuencial}" selected>Seguir secuencia actual (Folio: ${data.folio_secuencial})</option>`;

                data.folios_borrados.forEach(folio => {
                    opcionesHtml += `<option value="${folio}">Reutilizar Folio Borrado: ${folio}</option>`;
                });

                Swal.fire({
                    title: 'Asignación de ID Folio',
                    html: `
                        <p class="text-start text-muted" style="font-size:0.8rem; margin-bottom: 0.5rem;">Se detectaron folios que fueron eliminados previamente. Selecciona cuál deseas asignar a este nuevo registro:</p>
                        <select id="swal_select_folio" class="form-select form-select-sm fw-bold" style="font-size:0.85rem; border-color: #861532;">
                            ${opcionesHtml}
                        </select>
                    `,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#861532',
                    cancelButtonColor: '#757575',
                    confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Confirmar Folio',
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false,
                    preConfirm: () => {
                        return document.getElementById('swal_select_folio').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        formData.append('folio_forzado', result.value);
                        ejecutarEnvioRegistro(formData, idActual);
                    }
                });
            } else {
                formData.append('folio_forzado', data.folio_secuencial);
                ejecutarEnvioRegistro(formData, idActual);
            }
        } catch (error) {
            console.error("Error al validar la lista de folios:", error);
            alert("No se pudo verificar la lista de folios eliminados en el servidor.");
        }
    });

    function ejecutarEnvioRegistro(formData, idActual) {
        fetch('guardar_registro.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert('Registro guardado exitosamente');
                modalRegObj.hide();
                cargarRegistros();

                if(idActual) {
                    setTimeout(() => {
                        let registroActualizado = registrosGlobales.find(item => item.id_registro == idActual);
                        if(registroActualizado) {
                            let p1 = registroActualizado.pdf_soporte || '';
                            let p2 = registroActualizado.pdf_conclusion || '';
                            visualizarAmbosDocumentos(p1, p2, idActual);
                        }
                    }, 400);
                }
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error en la comunicación con el servidor al intentar guardar.');
        });
    }

    function abrirModalImportar() {
        if (ROL_ACTIVO !== 'ADMIN') return;
        let inputImportar = document.createElement('input');
        inputImportar.type = 'file';
        inputImportar.accept = '.csv, .xlsx, .xls';

        inputImportar.onchange = e => {
            let archivo = e.target.files[0];
            if (!archivo) return;

            let ext = archivo.name.split('.').pop().toLowerCase();
            if (!['csv', 'xlsx', 'xls'].includes(ext)) {
                alert('Por favor, selecciona un archivo válido de Excel o CSV.');
                return;
            }

            if (confirm(`¿Estás seguro de que deseas importar el archivo "${archivo.name}" al sistema?`)) {
                let formData = new FormData();
                formData.append('archivo_excel', archivo);

                fetch('importar_registros.php', { method: 'POST', body: formData })
                .then(res => {
                    if (!res.ok) throw new Error('Respuesta HTTP no válida del servidor.');
                    return res.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        let mensajeExito = `${data.message}\n\n` +
                                           `✓ Insertados con éxito: ${data.insertados || 0}\n` +
                                           `✓ Duplicados omitidos: ${data.duplicados_omitidos || 0}`;

                        if (data.detalles && data.detalles.length > 0) {
                            mensajeExito += `\n\nNotas/Observaciones:\n- ` + data.detalles.slice(0, 5).join('\n- ');
                            if(data.detalles.length > 5) mensajeExito += `\n... y ${data.detalles.length - 5} más.`;
                        }
                        alert(mensajeExito);
                        cargarRegistros();
                    } else {
                        alert('Error devuelto por el servidor:\n' + data.message);
                    }
                })
                .catch(err => {
                    alert('Error en la comunicación o procesamiento con el servidor al importar.');
                    console.error(err);
                });
            }
        };
        inputImportar.click();
    }

    function vaciarBaseDatos() {
        if (ROL_ACTIVO !== 'ADMIN') {
            alert('No tienes los privilegios necesarios para realizar esta acción.');
            return;
        }

        if (confirm('⚠️ ¡ADVERTENCIA CRÍTICA! ⚠️\n\n¿Estás absolutamente seguro de que deseas VACIAR la Base de Datos?\nEsta acción eliminará de forma PERMANENTE todos los oficios y los folios regresarán a 1.\n\nEsto no se puede deshacer.')) {
            fetch('vaciar_base.php', { method: 'GET' })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    cargarRegistros();
                    document.getElementById('visor_pdf_origen').src = "";
                    document.getElementById('visor_pdf_conclusion').src = "";
                    document.getElementById('badge_folio_origen').innerText = "-";
                    document.getElementById('badge_status_conclusion').innerText = "Ninguno";
                    document.getElementById('badge_status_conclusion').className = "badge bg-secondary";
                }
            })
            .catch(err => {
                alert('Ocurrió un error al procesar la petición en el servidor.');
                console.error(err);
            });
        }
    }
</script>
</body>
</html>
