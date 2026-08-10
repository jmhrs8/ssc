<?php
session_start();
require_once "../../config/conexion.php";

// --- 1. VALIDACIÓN DE SEGURIDAD ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$nivel_actual = strtoupper(trim($_SESSION['nivel'] ?? ''));
$permiso_personal = $_SESSION['permiso_personal'] ?? 0;
$usuario_sesion = $_SESSION['nombre'] ?? ($_SESSION['usuario'] ?? 'INVITADO');

if (!$nivel_actual || ($nivel_actual !== 'ADMIN_GENERAL' && $permiso_personal != 1)) {
    header("Location: ../../index.php?error=sin_permiso");
    exit();
}

$es_admin_general = ($nivel_actual === 'ADMIN_GENERAL');
$es_solo_lectura  = ($nivel_actual === 'LECTURA');

// NUEVA VARIABLE: Define si el usuario tiene privilegios para importar / gestionar masivamente
$tiene_permiso_masivo = (!$es_solo_lectura && ($es_admin_general || $permiso_personal == 1));

// Si el usuario hace clic en "VER TODOS", limpiamos la sesión de selección y alertas
if (isset($_GET['limpiar_sesion']) && $_GET['limpiar_sesion'] == '1') {
    unset($_SESSION['rfc_resaltar']);
    unset($_SESSION['alerta_duplicados']);
    unset($_SESSION['alerta_no_encontrados']);
    header("Location: index.php");
    exit();
}

// Recibimos los RFCs a resaltar desde procesar_excel_seleccion.php
$rfc_a_resaltar = [];
if (isset($_SESSION['rfc_resaltar'])) {
    $rfc_a_resaltar = $_SESSION['rfc_resaltar'];
}

// Paginación
$por_pagina = 100;
$pagina = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina < 1) $pagina = 1;
$inicio = ($pagina - 1) * $por_pagina;

$search = $_GET['buscar'] ?? '';
$ver_solo_seleccionados = isset($_GET['ver_solo']) && $_GET['ver_solo'] == '1';

try {
    // BUSCADOR GLOBAL AMPLIADO A TODOS LOS CAMPOS
    $sql_search = "";
    $params = [];
    
    if (!empty($search)) {
        $palabras = explode(' ', trim($search));
        $condiciones = [];
        
        foreach ($palabras as $p) {
            $term = "%$p%";
            // Busca de forma inteligente en nombre, apellidos, rfc, área, puesto, vía pública, etc.
            $condiciones[] = "(nombre LIKE ? OR apellido_paterno LIKE ? OR apellido_materno LIKE ? OR rfc LIKE ? OR area_adscripcion LIKE ? OR puesto LIKE ? OR descripcion_via_publica LIKE ? OR quincena LIKE ?)";
            for ($i = 0; $i < 8; $i++) {
                $params[] = $term;
            }
        }
        $sql_search = " WHERE " . implode(" AND ", $condiciones);
    }

    if ($ver_solo_seleccionados && !empty($rfc_a_resaltar)) {
        $placeholders = implode(',', array_fill(0, count($rfc_a_resaltar), '?'));

        $count_sql = "SELECT COUNT(*) FROM personal WHERE rfc IN ($placeholders)";
        $stmt_count = $pdo->prepare($count_sql);
        $stmt_count->execute(array_values($rfc_a_resaltar));
        $total_registros = $stmt_count->fetchColumn();
        $total_paginas = ceil($total_registros / $por_pagina);

        $query = "SELECT * FROM personal WHERE rfc IN ($placeholders) ORDER BY id ASC LIMIT $inicio, $por_pagina";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array_values($rfc_a_resaltar));
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $count_sql = "SELECT COUNT(*) FROM personal" . $sql_search;
        $stmt_count = $pdo->prepare($count_sql);
        $stmt_count->execute($params);
        $total_registros = $stmt_count->fetchColumn();
        $total_paginas = ceil($total_registros / $por_pagina);

        $query = "SELECT * FROM personal" . $sql_search . " ORDER BY id ASC LIMIT $inicio, $por_pagina";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("ERROR DE SISTEMA: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PERSONAL | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-size: 11px; background-color: #f4f6f9; text-transform: uppercase; }
        .header-top { background: #1a1a1a; color: white; padding: 12px; }
        .wrapper-top { width: 100%; overflow-x: auto; height: 15px; background: #e9ecef; border-radius: 5px; }
        .div-espejo { height: 15px; }
        .table-container { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-responsive { overflow-x: auto; }
        .table th { background: #212529 !important; color: white !important; text-align: center; white-space: nowrap; font-weight: 600; }
        .table td { white-space: nowrap; vertical-align: middle; border: 1px solid #dee2e6; }
        .wrapper-top::-webkit-scrollbar, .table-responsive::-webkit-scrollbar { height: 10px; }
        .wrapper-top::-webkit-scrollbar-thumb, .table-responsive::-webkit-scrollbar-thumb { background: #0dcaf0; border-radius: 5px; }
        .btn-xs { padding: 1px 5px; font-size: 10px; }
        .table-hover tbody tr.table-primary-subtle > td { background-color: #cfe2ff !important; }
        .select-row-checkbox { transform: scale(1.3); cursor: pointer; }
    </style>
</head>
<body>

<div class="header-top shadow-sm mb-3">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-users"></i> CONTROL DE PERSONAL</h5>
            <small class="text-warning">TOTAL REGISTROS: <?= $total_registros ?></small>
            <small class="ms-3 text-muted">IDENTIFICADO COMO: <?= strtoupper(htmlspecialchars($usuario_sesion)) ?></small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="../../index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-home"></i> INICIO</a>
            <a href="reportes_general.php" class="btn btn-info btn-sm text-white"><i class="fas fa-chart-bar"></i> REPORTES</a>
            
            <!-- Botón agregado para acceder al módulo de duplicados -->
            <a href="duplicados.php" class="btn btn-warning btn-sm text-dark fw-bold"><i class="fas fa-copy"></i> DUPLICADOS</a>

            <!-- Botón IMPORTAR superior visible para Admin y Capturista con permiso -->
            <?php if ($tiene_permiso_masivo): ?>
                <a href="subir_excel.php" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> IMPORTAR</a>
            <?php endif; ?>

            <?php if ($es_admin_general): ?>
                <button onclick="confirmarVaciado()" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> VACIAR</button>
            <?php endif; ?>
            <a href="bajar_excel.php" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> EXPORTAR</a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <?php if (isset($_SESSION['alerta_duplicados'])): ?>
        <div class="alert alert-warning alert-dismissible fade show py-2 fw-bold" role="alert" style="font-size: 12px;">
            <?= $_SESSION['alerta_duplicados']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['alerta_no_encontrados'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 fw-bold" role="alert" style="font-size: 12px;">
            <?= $_SESSION['alerta_no_encontrados']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" name="buscar" class="form-control form-control-sm"
                           placeholder="BUSCAR EN CUALQUIER CAMPO (NOMBRE, ÁREA, RFC...)"
                           value="<?= htmlspecialchars($search) ?>"
                           onkeyup="this.value = this.value.toUpperCase();">
                </div>
                <div class="col-md-9 d-flex gap-1 justify-content-end align-items-center flex-wrap">
                    <button type="submit" class="btn btn-dark btn-sm">FILTRAR</button>

                    <!-- Controles de subida masiva de Excel visibles para Admin y Capturista -->
                    <?php if ($tiene_permiso_masivo): ?>
                        <div class="d-flex align-items-center gap-1 m-0">
                            <input type="file" id="excelFile" name="excel_file" accept=".xlsx, .xls, .csv" form="excelForm" class="form-control form-control-sm" style="width: 200px;" required>
                            <button type="submit" form="excelForm" class="btn btn-outline-info btn-sm text-dark fw-bold">
                                <i class="fas fa-file-excel"></i> SUBIR Y SELECCIONAR
                            </button>
                        </div>

                        <?php if (!empty($rfc_a_resaltar)): ?>
                            <?php if ($ver_solo_seleccionados): ?>
                                <a href="index.php?limpiar_sesion=1" class="btn btn-secondary btn-sm"><i class="fas fa-list"></i> VER TODOS</a>
                            <?php else: ?>
                                <a href="index.php?ver_solo=1" class="btn btn-warning btn-sm text-dark fw-bold"><i class="fas fa-filter"></i> VER SOLO SELECCIONADOS (<?= count($rfc_a_resaltar) ?>)</a>
                                <a href="index.php?limpiar_sesion=1" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i> LIMPIAR SELECCIÓN</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!$es_solo_lectura): ?>
                        <a href="formulario.php" class="btn btn-primary btn-sm">NUEVO REGISTRO</a>
                    <?php endif; ?>
                </div>
            </form>

            <form id="excelForm" action="procesar_excel_seleccion.php" method="POST" enctype="multipart/form-data" style="display: none;"></form>

            <!-- Formulario de Borrado Masivo de Seleccionados ampliado para Capturista autorizado -->
            <?php if ($tiene_permiso_masivo): ?>
                <form id="deleteSelectedForm" action="acciones.php" method="POST" onsubmit="return confirmarBorradoSeleccionado();" class="mt-2 text-end">
                    <input type="hidden" name="cmd" value="eliminar_seleccionados">
                    <div id="selectedIdsContainer"></div>
                    <button type="submit" id="btnConfirmDelete" class="btn btn-danger btn-sm" style="display: none;">
                        <i class="fas fa-trash"></i> Borrar Seleccionados (<span id="countSelected">0</span>)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="wrapper-top mb-1"><div class="div-espejo"></div></div>

    <div class="table-container shadow-sm">
        <div class="table-responsive" id="contenedor-tabla">
            <table class="table table-hover table-bordered mb-0" id="mi-tabla">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 40px; text-align: center;">
                            <?php if ($tiene_permiso_masivo): ?>
                                <input type="checkbox" id="selectAllCheckbox" class="select-row-checkbox" onclick="toggleSelectAll(this)">
                            <?php endif; ?>
                        </th>
                        <th rowspan="2">ACCIONES</th>
                        <th rowspan="2">APELLIDO PATERNO</th>
                        <th rowspan="2">APELLIDO MATERNO</th>
                        <th rowspan="2">NOMBRE(S)</th>
                        <th rowspan="2">R.F.C.</th>
                        <th rowspan="2">ÁREA DE ADSCRIPCIÓN</th>
                        <th rowspan="2">PUESTO</th>
                        <th rowspan="2">DESCRIPCIÓN VÍA PÚBLICA</th>
                        <th colspan="2">CONTRATACIÓN</th>
                        <th rowspan="2">FECHA DE ALTA</th>
                        <th rowspan="2">QUINCENA</th>
                    </tr>
                    <tr>
                        <th>BASE</th>
                        <th>EVENTUAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($resultados) > 0): ?>
                        <?php foreach ($resultados as $r):
                            $is_highlighted = in_array(strtoupper(trim($r['rfc'])), $rfc_a_resaltar);
                        ?>
                        <tr class="<?= $is_highlighted ? 'table-primary-subtle' : '' ?>">
                            <td class="text-center align-middle">
                                <?php if ($tiene_permiso_masivo): ?>
                                    <input type="checkbox" class="select-row-checkbox row-id-checkbox" value="<?= $r['id'] ?>" <?= $is_highlighted ? 'checked' : '' ?> onchange="updateSelectedCount()">
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (!$es_solo_lectura): ?>
                                    <a href="formulario.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-xs" title="EDITAR"><i class="fas fa-edit"></i></a>
                                <?php endif; ?>
                                <?php if ($es_admin_general): ?>
                                    <a href="acciones.php?eliminar=<?= $r['id'] ?>" class="btn btn-danger btn-xs" title="BORRAR" onclick="return confirm('¿ELIMINAR ESTE REGISTRO PERMANENTEMENTE?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['apellido_paterno']) ?></td>
                            <td><?= htmlspecialchars($r['apellido_materno']) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($r['nombre']) ?></td>
                            <td class="text-center fw-bold text-primary"><?= htmlspecialchars($r['rfc']) ?></td>
                            <td><?= htmlspecialchars($r['area_adscripcion']) ?></td>
                            <td><?= htmlspecialchars($r['puesto']) ?></td>
                            <td><small><?= htmlspecialchars($r['descripcion_via_publica']) ?></small></td>
                            <td class="text-center fw-bold text-success"><?= (strtoupper($r['tipo_contratacion'] ?? '') == 'BASE') ? 'X' : '' ?></td>
                            <td class="text-center fw-bold text-info"><?= (strtoupper($r['tipo_contratacion'] ?? '') == 'EVENTUAL') ? 'X' : '' ?></td>
                            <td class="text-center"><?= htmlspecialchars($r['fecha_alta']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($r['quincena']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="13" class="text-center py-3 text-muted">NO SE ENCONTRARON RESULTADOS</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginador -->
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <?php if ($total_paginas > 1): ?>
                <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $pagina-1 ?>&buscar=<?= urlencode($search) ?>&ver_solo=<?= $ver_solo_seleccionados ? 1 : 0 ?>">ANTERIOR</a>
                </li>
                <?php
                $rango = 4;
                for($i = max(1, $pagina - $rango); $i <= min($total_paginas, $pagina + $rango); $i++):
                ?>
                    <li class="page-item <?= ($pagina == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?p=<?= $i ?>&buscar=<?= urlencode($search) ?>&ver_solo=<?= $ver_solo_seleccionados ? 1 : 0 ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $pagina+1 ?>&buscar=<?= urlencode($search) ?>&ver_solo=<?= $ver_solo_seleccionados ? 1 : 0 ?>">SIGUIENTE</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<script>
    const wrapperTop = document.querySelector('.wrapper-top');
    const tableCont = document.querySelector('#contenedor-tabla');
    const espejo = document.querySelector('.div-espejo');
    const tabla = document.querySelector('#mi-tabla');

    function ajustarScroll() {
        if (tabla) espejo.style.width = tabla.offsetWidth + "px";
    }

    window.onload = function() {
        ajustarScroll();
        updateSelectedCount();
    };
    window.onresize = ajustarScroll;

    wrapperTop.onscroll = function() { tableCont.scrollLeft = wrapperTop.scrollLeft; };
    tableCont.onscroll = function() { wrapperTop.scrollLeft = tableCont.scrollLeft; };

    function confirmarVaciado() {
        if (confirm("¿ADVERTENCIA: BORRAR TODA LA BASE DE PERSONAL?")) {
            if (prompt("PARA CONTINUAR, ESCRIBA 'ELIMINAR TODO':") === "ELIMINAR TODO") {
                window.location.href = "acciones.php?cmd=truncate";
            }
        }
    }

    function toggleSelectAll(source) {
        checkboxes = document.querySelectorAll('.row-id-checkbox');
        checkboxes.forEach((cb) => {
            cb.checked = source.checked;
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.row-id-checkbox:checked');
        const count = checkboxes.length;
        const btnDelete = document.getElementById('btnConfirmDelete');
        const container = document.getElementById('selectedIdsContainer');

        if (!container) return;
        container.innerHTML = '';

        if (count > 0) {
            if (btnDelete) btnDelete.style.display = 'inline-block';
            if (document.getElementById('countSelected')) document.getElementById('countSelected').innerText = count;

            checkboxes.forEach((cb) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });
        } else {
            if (btnDelete) btnDelete.style.display = 'none';
        }
    }

    function confirmarBorradoSeleccionado() {
        const count = document.querySelectorAll('.row-id-checkbox:checked').length;
        return confirm(`¿ESTÁS SEGURO DE ELIMINAR PERMANENTEMENTE LOS ${count} REGISTROS SELECCIONADOS?`);
    }
</script>
</body>
</html>
