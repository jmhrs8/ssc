<?php
session_start();
require_once "../../config/conexion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$nivel_actual = strtoupper(trim($_SESSION['nivel'] ?? ''));
$permiso_siniestros = $_SESSION['permiso_siniestros'] ?? 0;

if ($nivel_actual !== 'ADMIN_GENERAL' && $permiso_siniestros != 1) {
    header("Location: ../../index.php?error=sin_permiso");
    exit();
}

// Definimos variables booleanas para ocultar elementos de UI
$es_admin_general = ($nivel_actual === 'ADMIN_GENERAL');
$es_solo_lectura  = ($nivel_actual === 'LECTURA');

$search = $_GET['buscar'] ?? '';

try {
    $query = "SELECT * FROM siniestros WHERE
              folio LIKE ? OR
              economico_placas LIKE ? OR
              nombre_elemento LIKE ? OR
              no_serie LIKE ? OR
              adscripcion LIKE ? OR
              no_siniestro LIKE ? OR
              taller_asignado LIKE ? OR
              alcaldia LIKE ? OR
              papeleta_control_gestion LIKE ?
              ORDER BY id ASC";

    $stmt = $pdo->prepare($query);
    $term = "%$search%";
    $stmt->execute([$term, $term, $term, $term, $term, $term, $term, $term, $term]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = $pdo->query("SELECT COUNT(*) as total FROM siniestros")->fetch();
} catch (PDOException $e) {
    die("Error en Servidor: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Siniestros | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-size: 11px; background-color: #f4f6f9; }
        .table td, .table th { padding: 3px 5px !important; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
        thead th { background-color: #2c3e50 !important; color: white !important; position: sticky; top: 0; z-index: 20; }
        .sticky-col { position: sticky; left: 0; background: #f8f9fa !important; z-index: 10; border-right: 2px solid #ddd !important; }
        .table-responsive { height: calc(100vh - 140px); overflow: auto; }
        .btn-excel { color: #198754 !important; font-weight: bold; }
        .btn-reporte { color: #0d6efd !important; font-size: 14px; }
        .miniatura-tabla { width: 35px; height: 35px; object-fit: cover; cursor: pointer; border-radius: 4px; border: 1px solid #ccc; }
    </style>
</head>
<body class="p-2">
<div class="container-fluid">
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'error_duplicado'): ?>
        <div class="alert alert-danger alert-dismissible fade show p-2 mb-2 fw-bold text-uppercase d-flex align-items-center" role="alert" style="font-size: 12px;">
            <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
            <div>Error: El No. Siniestro "<?= htmlspecialchars($_GET['siniestro'] ?? '') ?>" ya se encuentra registrado.</div>
            <button type="button" class="btn-close p-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <a href="../../index.php" class="btn btn-outline-dark btn-sm me-2"><i class="fas fa-home"></i></a>
            <span class="fw-bold text-uppercase">Control Unificado de Siniestros 2026</span>
            <span class="badge bg-dark ms-2"><?= $stats['total'] ?> registros</span>
        </div>
        <div class="btn-group shadow-sm">
            <?php if (!$es_solo_lectura): ?>
                <a href="formulario.php" class="btn btn-primary btn-sm" title="Nuevo Registro"><i class="fas fa-plus"></i></a>
            <?php endif; ?>

            <?php if (!$es_solo_lectura): ?>
                <a href="subir_excel.php" class="btn btn-info btn-sm text-white" title="Carga Masiva"><i class="fas fa-upload"></i></a>
            <?php endif; ?>

            <div class="btn-group">
                <button type="button" class="btn btn-dark btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-chart-pie"></i> Reportes / Exportar</button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="reporte_rubros.php?rubro=mes">Gráfica Mensual</a></li>
                    <li><a class="dropdown-item" href="reporte_rubros.php?rubro=marca">Gráfica Marcas</a></li>
                    <li><a class="dropdown-item" href="reporte_rubros.php?rubro=adscripcion">Gráfica Adscripción</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item btn-excel" href="bajar_excel.php"><i class="fas fa-file-excel me-2"></i>Descargar Excel</a></li>
                </ul>
            </div>
            <?php if ($es_admin_general): ?>
                <button onclick="confirmarVaciado()" class="btn btn-outline-danger btn-sm" title="Vaciar Tabla"><i class="fas fa-trash"></i></button>
            <?php endif; ?>
        </div>
    </div>

    <form method="GET" class="row g-1 mb-2">
        <div class="col-11"><input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar por folio, placas, conductor, serie, adscripción, alcaldía o papeleta de gestión..." value="<?= htmlspecialchars($search) ?>"></div>
        <div class="col-1"><button type="submit" class="btn btn-secondary btn-sm w-100"><i class="fas fa-search"></i></button></div>
    </form>

    <div class="table-container border bg-white shadow-sm rounded">
        <div class="table-responsive">
            <table class="table table-bordered mb-0 table-hover table-sm">
                <thead>
                    <tr>
                        <th class="sticky-col text-center">Acciones</th>
                        <th>Mes</th><th>Folio</th><th>Fecha Siniestro</th><th>Hora Siniestro</th>
                        <th>Fecha Reporte</th><th>Hora Reporte</th><th>Marca</th><th>Modelo</th><th>Tipo</th>
                        <th>Económico Placas</th><th>Adscripción</th><th>No. Inventario</th><th>Número Serie</th>
                        <th>Nombre Conductor</th><th>No. Empleado Conductor</th><th>No. Licencia</th><th>Tipo Siniestro</th>
                        <th>Vehículo 3ro</th><th>Tipo 3ro</th><th>Placas 3ro</th><th>Color 3ro</th>
                        <th>Seguro 3ro</th><th>Calles</th><th>Colonia</th><th>Alcaldía</th><th>Lesionados</th>
                        <th>Hospital</th><th>Taller Asignado</th><th>No. Siniestro</th>
                        <th>Carp. Investigación / Acta</th><th>Propiedad</th><th>Arrendadora</th><th>Aseguradora</th><th>Monto Daños</th>
                        <th>Declaración Universal</th><th>Pase Médicos</th><th>Pase Taller</th><th>Gráficas</th>
                        <th>Cuadernillo</th><th>Visto Bueno</th><th>Fecha Visto Bueno</th><th>Observaciones</th>
                        <th>Papeleta Gestión</th><th>Estatus</th><th>Fotos Unidad</th><th>Fotos Tercero</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($resultados) > 0): foreach ($resultados as $r): 
                        // Función local para decodificar las fotos múltiples de forma segura
                        $getFotos = function($json_str) {
                            if (empty($json_str)) return [];
                            $dec = json_decode($json_str, true);
                            if (is_array($dec)) return $dec;
                            return [$json_str]; // Compatibilidad con rutas antiguas de texto plano
                        };

                        $fotos_unidad = $getFotos($r['foto_unidad'] ?? '');
                        $fotos_vehiculo = $getFotos($r['foto_vehiculo'] ?? '');
                    ?>
                    <tr>
                        <td class="sticky-col text-center">
                            <a href="captura_siniestro.php?id=<?= $r['id'] ?>" class="btn-reporte me-2" title="Capturar"><i class="fas fa-file-signature"></i></a>
                            <?php if (!$es_solo_lectura): ?>
                                <a href="formulario.php?id=<?= $r['id'] ?>" class="text-warning me-2" title="Editar"><i class="fas fa-edit"></i></a>
                            <?php endif; ?>
                            <?php if ($es_admin_general): ?>
                                <a href="acciones.php?eliminar=<?= $r['id'] ?>" class="text-danger" onclick="return confirm('¿Eliminar registro?')" title="Eliminar"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($r['mes'] ?? '') ?></td>
                        <td class="fw-bold text-primary"><?= htmlspecialchars($r['folio'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['fecha'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['hora'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['fecha_reporte'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['hora_reporte'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['marca'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['modelo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['economico_placas'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['adscripcion'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['no_inventario'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['no_serie'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['nombre_elemento'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['cond_no_empleado'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['cond_licencia'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['tipo_siniestro'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['vehiculo_3ro'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['tercero_tipo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['placas_3ro'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['tercero_color'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['seguro'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['calles'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['colonia'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['alcaldia'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['lesionados'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['hospital'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['taller_asignado'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['no_siniestro'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['carp_investigacion'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['propio'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['arrendado'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['aseguradora'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['est_danos_materiales'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['declaracion_universal'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['pase_medicos'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['pase_taller'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['graficas'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['cuadernillo'] ?? '') ?></td>
                        <td class="fw-bold <?= ($r['visto_bueno']=='SI')?'text-success':'text-danger'?>"><?= htmlspecialchars($r['visto_bueno'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['fecha_visto_bueno'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['observaciones'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['papeleta_control_gestion'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['estatus'] ?? '') ?></td>
                        
                        <!-- Columna Fotos Unidad Oficial -->
                        <td class="text-center">
                            <?php if (!empty($fotos_unidad)): ?>
                                <img src="<?= htmlspecialchars($fotos_unidad[0]) ?>" class="miniatura-tabla" onclick='abrirModalGaleria(<?= json_encode($fotos_unidad) ?>, "Unidad Oficial - Folio: <?= $r['folio'] ?>")' title="Ver fotos de la unidad">
                                <?php if(count($fotos_unidad) > 1): ?>
                                    <span class="badge bg-secondary" style="font-size: 9px;">+<?= count($fotos_unidad)-1 ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">S/F</span>
                            <?php endif; ?>
                        </td>

                        <!-- Columna Fotos Vehículo Tercero -->
                        <td class="text-center">
                            <?php if (!empty($fotos_vehiculo)): ?>
                                <img src="<?= htmlspecialchars($fotos_vehiculo[0]) ?>" class="miniatura-tabla" onclick='abrirModalGaleria(<?= json_encode($fotos_vehiculo) ?>, "Vehículo Tercero - Folio: <?= $r['folio'] ?>")' title="Ver fotos del tercero">
                                <?php if(count($fotos_vehiculo) > 1): ?>
                                    <span class="badge bg-secondary" style="font-size: 9px;">+<?= count($fotos_vehiculo)-1 ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">S/F</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="48" class="text-center">No hay registros.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Visualizar Galería de Fotos -->
<div class="modal fade" id="modalGaleria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title fs-6" id="tituloModalGaleria">Galería de Imágenes</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-black p-3">
                <div id="contenedorImágenesGaleria" class="d-flex flex-wrap justify-content-center gap-2 align-items-center" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Las imágenes se cargan dinámicamente aquí vía JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmarVaciado() {
    if (confirm("¡ADVERTENCIA! Esta acción borrará TODOS los registros.")) {
        let pass = prompt("Escriba 'ELIMINAR TODO' para confirmar:");
        if (pass === "ELIMINAR TODO") window.location.href = "acciones.php?cmd=truncate";
    }
}

function abrirModalGaleria(arrayRutas, titulo) {
    document.getElementById('tituloModalGaleria').innerText = titulo;
    let contenedor = document.getElementById('contenedorImágenesGaleria');
    contenedor.innerHTML = '';

    arrayRutas.forEach(ruta => {
        let a = document.createElement('a');
        a.href = ruta;
        a.target = '_blank';
        a.title = 'Abrir imagen en tamaño real';
        
        let img = document.createElement('img');
        img.src = ruta;
        img.className = 'img-thumbnail m-1';
        img.style.maxHeight = '200px';
        img.style.objectFit = 'contain';
        
        a.appendChild(img);
        contenedor.appendChild(a);
    });

    let myModal = new bootstrap.Modal(document.getElementById('modalGaleria'));
    myModal.show();
}
</script>
</body>
</html>
