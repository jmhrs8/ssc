<?php
session_start();
require_once "../../config/conexion.php";

// --- 1. VALIDACIÓN DE SEGURIDAD ---
// Aseguramos que existan las llaves de sesión antes de usarlas
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Extraemos nivel y permisos de la sesión con valores por defecto para evitar Warnings
$nivel_actual = strtoupper(trim($_SESSION['nivel'] ?? ''));
$permiso_personal = $_SESSION['permiso_personal'] ?? 0;
// Usamos 'nombre' que es lo que definimos en el login, o 'usuario' como respaldo
$usuario_sesion = $_SESSION['nombre'] ?? ($_SESSION['usuario'] ?? 'INVITADO');

// Solo entran: ADMIN_GENERAL o usuarios con permiso_personal activo
if (!$nivel_actual || ($nivel_actual !== 'ADMIN_GENERAL' && $permiso_personal != 1)) {
    header("Location: ../../index.php?error=sin_permiso");
    exit();
}
// --- FIN SEGURIDAD ---

// 2. PAGINACIÓN PARA VELOCIDAD
$por_pagina = 100;
$pagina = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina < 1) $pagina = 1;
$inicio = ($pagina - 1) * $por_pagina;

$search = $_GET['buscar'] ?? '';

try {
    $term = "%$search%";

    // Contar total de registros para la paginación
    $count_sql = "SELECT COUNT(*) FROM personal WHERE apellido_paterno LIKE ? OR nombre LIKE ? OR rfc LIKE ?";
    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute([$term, $term, $term]);
    $total_registros = $stmt_count->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);

    // Consulta optimizada con LIMIT
    $query = "SELECT * FROM personal WHERE 
              apellido_paterno LIKE ? OR 
              nombre LIKE ? OR 
              rfc LIKE ? 
              ORDER BY id ASC LIMIT $inicio, $por_pagina";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$term, $term, $term]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        /* Estilos para el scroll doble */
        .wrapper-top { width: 100%; overflow-x: auto; height: 15px; background: #e9ecef; border-radius: 5px; }
        .div-espejo { height: 15px; }

        .table-container { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-responsive { overflow-x: auto; }
        .table th { background: #212529 !important; color: white !important; text-align: center; white-space: nowrap; font-weight: 600; }
        .table td { white-space: nowrap; vertical-align: middle; border: 1px solid #dee2e6; }

        /* Personalización de scrollbars */
        .wrapper-top::-webkit-scrollbar, .table-responsive::-webkit-scrollbar { height: 10px; }
        .wrapper-top::-webkit-scrollbar-thumb, .table-responsive::-webkit-scrollbar-thumb { background: #0dcaf0; border-radius: 5px; }

        .btn-xs { padding: 1px 5px; font-size: 10px; }
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
        <div class="d-flex gap-2">
            <a href="../../index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-home"></i> INICIO</a>
            <a href="reportes_general.php" class="btn btn-info btn-sm text-white"><i class="fas fa-chart-bar"></i> REPORTES</a>

            <?php if ($nivel_actual === 'ADMIN_GENERAL'): ?>
                <a href="subir_excel.php" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> IMPORTAR</a>
                <button onclick="confirmarVaciado()" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> VACIAR</button>
            <?php endif; ?>

            <a href="bajar_excel.php" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> EXPORTAR</a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Buscador -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="buscar" class="form-control form-control-sm" 
                           placeholder="BUSCAR POR NOMBRE, APELLIDO O RFC..." 
                           value="<?= htmlspecialchars($search) ?>"
                           onkeyup="this.value = this.value.toUpperCase();">
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-dark btn-sm w-100">FILTRAR</button>
                    <a href="formulario.php" class="btn btn-primary btn-sm w-100">NUEVO REGISTRO</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Scroll superior sincronizado -->
    <div class="wrapper-top mb-1"><div class="div-espejo"></div></div>

    <div class="table-container shadow-sm">
        <div class="table-responsive" id="contenedor-tabla">
            <table class="table table-hover table-bordered mb-0" id="mi-tabla">
                <thead>
                    <tr>
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
                        <?php foreach ($resultados as $r): ?>
                        <tr>
                            <td class="text-center">
                                <a href="formulario.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-xs" title="EDITAR"><i class="fas fa-edit"></i></a>

                                <?php if ($nivel_actual === 'ADMIN_GENERAL'): ?>
                                    <a href="acciones.php?eliminar=<?= $r['id'] ?>" class="btn btn-danger btn-xs" title="BORRAR" 
                                       onclick="return confirm('¿ELIMINAR ESTE REGISTRO PERMANENTEMENTE?')">
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
                            <td class="text-center fw-bold text-success">
                                <?= (strtoupper($r['tipo_contratacion'] ?? '') == 'BASE') ? 'X' : '' ?>
                            </td>
                            <td class="text-center fw-bold text-info">
                                <?= (strtoupper($r['tipo_contratacion'] ?? '') == 'EVENTUAL') ? 'X' : '' ?>
                            </td>
                            <td class="text-center"><?= htmlspecialchars($r['fecha_alta']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($r['quincena']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="12" class="text-center py-3 text-muted">NO SE ENCONTRARON RESULTADOS</td></tr>
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
                    <a class="page-link" href="?p=<?= $pagina-1 ?>&buscar=<?= urlencode($search) ?>">ANTERIOR</a>
                </li>
                <?php
                $rango = 4;
                for($i = max(1, $pagina - $rango); $i <= min($total_paginas, $pagina + $rango); $i++):
                ?>
                    <li class="page-item <?= ($pagina == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?p=<?= $i ?>&buscar=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $pagina+1 ?>&buscar=<?= urlencode($search) ?>">SIGUIENTE</a>
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

    // Sincronizar el ancho del scroll superior con la tabla real
    function ajustarScroll() {
        if (tabla) espejo.style.width = tabla.offsetWidth + "px";
    }

    window.onload = ajustarScroll;
    window.onresize = ajustarScroll;

    // Sincronización bidireccional del scroll
    wrapperTop.onscroll = function() { tableCont.scrollLeft = wrapperTop.scrollLeft; };
    tableCont.onscroll = function() { wrapperTop.scrollLeft = tableCont.scrollLeft; };

    function confirmarVaciado() {
        if (confirm("¿ADVERTENCIA: BORRAR TODA LA BASE DE PERSONAL?")) {
            if (prompt("PARA CONTINUAR, ESCRIBA 'ELIMINAR TODO':") === "ELIMINAR TODO") {
                window.location.href = "acciones.php?cmd=truncate";
            }
        }
    }
</script>
</body>
</html>
