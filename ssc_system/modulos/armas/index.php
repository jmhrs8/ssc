<?php
session_start();
require_once "../../config/conexion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$nivel_actual = strtoupper(trim($_SESSION['nivel'] ?? ''));
$permiso_armas = $_SESSION['permiso_armas'] ?? 0;

if ($nivel_actual !== 'ADMIN_GENERAL' && $permiso_armas != 1) {
    header("Location: ../../index.php?error=sin_permiso");
    exit();
}

$search = $_GET['buscar'] ?? '';

try {
    // Consulta original con el ajuste de días hacia fecha_acuse
    $query = "SELECT *, 
              DATEDIFF(CURDATE(), fecha_acuse) as dias_post_acuse
              FROM inventario_armas WHERE
              poliza LIKE ? OR serie_matricula_1 LIKE ? OR no_expediente LIKE ?
              OR no_siniestro LIKE ? OR marca LIKE ? OR modelo LIKE ?
              ORDER BY id ASC";

    $stmt = $pdo->prepare($query);
    $term = "%$search%";
    $stmt->execute([$term, $term, $term, $term, $term, $term]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = $pdo->query("SELECT COUNT(*) as total FROM inventario_armas")->fetch();
} catch (PDOException $e) {
    die("ERROR EN EL SISTEMA: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ARMAMENTO | SSC SYSTEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-size: 10px; background-color: #f4f6f9; text-transform: uppercase; font-family: sans-serif; }
        .header-top { background: #1a1a1a; color: white; padding: 10px 20px; border-bottom: 4px solid #0dcaf0; }
        .registros-label { color: #ffc107; font-weight: bold; font-size: 10px; }
        .btn-header { font-weight: bold; font-size: 11px; padding: 5px 12px; border-radius: 4px; text-transform: uppercase; }

        /* Tabla extendida para todas las columnas */
        .table { min-width: 5800px; border: 1px solid #dee2e6; }
        .table th { background: #212529 !important; color: white; text-align: center; vertical-align: middle; white-space: nowrap; }
        
        /* Colores de estatus de días */
        .bg-vencido { background-color: #ff0000 !important; color: white !important; font-weight: bold; }
        .bg-proximo { background-color: #ffc107 !important; color: black !important; }
        .bg-alerta-temprana { background-color: #e3f2fd !important; border-left: 5px solid #0dcaf0 !important; }

        .wrapper-top { width: 100%; overflow-x: auto; height: 18px; margin-bottom: 5px; }
        .div-espejo { height: 18px; }
        .table-container { background: white; padding: 10px; border-radius: 8px; }
        
        .toast-container { z-index: 1060; }
    </style>
</head>
<body>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="alertaDias" class="toast shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-info text-white">
            <i class="fas fa-bell me-2"></i>
            <strong class="me-auto">AVISO DE SEGUIMIENTO</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" style="font-size: 11px;">
            HAY <b id="countAlertas">0</b> REGISTROS ENTRE 15 Y 20 DÍAS DE ACUSE. REVISE LAS FILAS EN AZUL CLARO.
        </div>
    </div>
</div>

<div class="header-top d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <i class="fas fa-shield-alt fa-2x me-2 text-info"></i>
        <div>
            <h4 class="mb-0 fw-bold">ARMAMENTO</h4>
            <div class="registros-label">REGISTROS TOTALES: <?= $stats['total'] ?></div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="../../index.php" class="btn btn-header btn-outline-light"><i class="fas fa-home"></i> INICIO</a>
        <a href="reportes_general.php" class="btn btn-header btn-info text-white"><i class="fas fa-chart-bar"></i> REPORTES</a>
        <?php if ($nivel_actual === 'ADMIN_GENERAL'): ?>
            <a href="subir_excel.php" class="btn btn-header btn-success"><i class="fas fa-file-excel"></i> IMPORTAR</a>
        <?php endif; ?>
        <a href="bajar_excel.php" class="btn btn-header btn-primary"><i class="fas fa-download"></i> EXPORTAR</a>
        <?php if ($nivel_actual === 'ADMIN_GENERAL'): ?>
            <a href="acciones.php?vaciar=1" class="btn btn-header btn-danger" onclick="return confirm('¿VACIAR TODO EL INVENTARIO DE ARMAS?')"><i class="fas fa-trash-alt"></i> VACIAR</a>
        <?php endif; ?>
    </div>
</div>

<div class="container-fluid mt-2">
    <div class="card shadow-sm mb-2">
        <div class="card-body py-2">
            <form method="GET" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="BUSCAR PÓLIZA, SERIE, EXPEDIENTE, MARCA, MODELO..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm w-100">FILTRAR</button>
                    <a href="formulario.php" class="btn btn-primary btn-sm w-100"><i class="fas fa-plus"></i> NUEVO</a>
                </div>
            </form>
        </div>
    </div>

    <div class="wrapper-top"><div class="div-espejo"></div></div>

    <div class="table-container">
        <div class="table-responsive" id="contenedor-tabla">
            <table class="table table-hover table-sm table-bordered">
                <thead>
                    <tr>
                        <th style="min-width: 120px; position: sticky; left: 0; z-index: 10; background: #212529;">ACCIONES</th>
                        <th>PÓLIZA</th>
                        <th>TIPO DE BIEN</th>
                        <th>TIPO DE SINIESTRO</th>
                        <th>AÑO/VIGENCIA</th>
                        <th>FECHA DE SINIESTRO</th>
                        <th>No. SINIESTRO</th>
                        <th>DESPACHO</th>
                        <th>ASEGURADORA</th>
                        <th>STATUS (SEGURO)</th>
                        <th>No. EXPEDIENTE</th>
                        <th>TIPO DE DAÑO O RECLAMACIÓN</th>
                        <th>FECHA DE RECLAMO</th>
                        <th>MES</th>
                        <th>MARCA</th>
                        <th>MODELO</th>
                        <th>SERIE/MATRICULA</th>
                        <th>STATUS DE TRAMITE</th>
                        <th>OBSERVACIONES</th>
                        <th>IMPORTE DEL CONVENIO</th>
                        <th>TIPO DE PAGO</th>
                        <th>COMPROBANTE DE PAGO</th>
                        <th>FECHA DE ACUSE</th>
                        <th>DÍAS TRAS ACUSE</th>
                        <th>FOLIO SDRA</th>
                        <th>OF. RECIBIDO</th>
                        <th>N° DE OFICIO</th>
                        <th>BIENES DIVERSOS</th>
                        <th>CANDADO DE MANOS</th>
                        <th>ARMAS</th>
                        <th>CARGADOR</th>
                        <th>CARTUCHOS</th>
                        <th>CASCOS</th>
                        <th>ESCUDOS</th>
                        <th>CHALECOS</th>
                        <th>ATENDIÓ</th>
                        <th>DIGITALIZADO</th>
                        <th>OBSERVACIONES GRALES.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $alertas_count = 0;
                    foreach ($resultados as $r):
                        $clase_status = "";
                        $dias = $r['dias_post_acuse'];
                        
                        if (!empty($r['fecha_acuse']) && $r['fecha_acuse'] != '0000-00-00') {
                            if ($dias > 30) $clase_status = "bg-vencido";
                            elseif ($dias >= 20) $clase_status = "bg-proximo";
                            elseif ($dias >= 15) {
                                $clase_status = "bg-alerta-temprana";
                                $alertas_count++;
                            }
                        }
                    ?>
                    <tr class="<?= ($clase_status == 'bg-alerta-temprana') ? $clase_status : '' ?>">
                        <td class="text-center" style="position: sticky; left: 0; z-index: 5; background: inherit; border-right: 2px solid #dee2e6;">
                            <div class="btn-group">
                                <a href="formulario.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                                <a href="captura_siniestro_armas.php?id=<?= $r['id'] ?>" class="btn btn-danger btn-sm" title="PDF Reclamo"><i class="fas fa-file-pdf"></i></a>
                                <?php if ($nivel_actual === 'ADMIN_GENERAL'): ?>
                                    <a href="acciones.php?eliminar=<?= $r['id'] ?>" class="btn btn-dark btn-sm" title="Borrar" onclick="return confirm('¿ELIMINAR ESTE REGISTRO?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= $r['poliza'] ?></td>
                        <td><?= $r['tipo_bien'] ?></td>
                        <td><?= $r['tipo_siniestro'] ?></td>
                        <td><?= $r['anio_vigencia'] ?></td>
                        <td><?= $r['fecha_siniestro_1'] ?></td>
                        <td><?= $r['no_siniestro'] ?></td>
                        <td><?= $r['despacho'] ?></td>
                        <td><?= $r['aseguradora'] ?></td>
                        <td><?= $r['status_seguro'] ?></td>
                        <td class="fw-bold"><?= $r['no_expediente'] ?></td>
                        <td><?= $r['tipo_dano'] ?></td>
                        <td><?= $r['fecha_reclamacion'] ?></td>
                        <td><?= $r['mes_reclamacion'] ?? '' ?></td>
                        <td><?= $r['marca'] ?></td>
                        <td><?= $r['modelo'] ?></td>
                        <td class="text-primary fw-bold"><?= $r['serie_matricula_1'] ?></td>
                        <td><?= $r['status_tramite'] ?? '' ?></td>
                        <td><?= $r['siniestro_detalle'] ?></td>
                        <td><?= number_format($r['importe_convenio'] ?? 0, 2) ?></td>
                        <td><?= $r['tipo_pago'] ?? '' ?></td>
                        <td><?= $r['comprobante_pago'] ?? '' ?></td>
                        <td class="fw-bold text-success"><?= $r['fecha_acuse'] ?></td>
                        <td class="text-center <?= ($clase_status != 'bg-alerta-temprana') ? $clase_status : '' ?>">
                            <?= (!empty($r['fecha_acuse']) && $r['fecha_acuse'] != '0000-00-00') ? $dias . " D" : "PENDIENTE" ?>
                        </td>
                        <td><?= $r['folio_sdra'] ?></td>
                        <td><?= $r['of_recibido'] ?? '' ?></td>
                        <td><?= $r['no_oficio'] ?? '' ?></td>
                        <td><?= $r['bienes_diversos'] ?? '' ?></td>
                        <td><?= $r['candado_manos'] ?? '' ?></td>
                        <td class="text-center"><?= $r['armas'] ?></td>
                        <td class="text-center"><?= $r['cargador'] ?></td>
                        <td class="text-center"><?= $r['cartuchos'] ?></td>
                        <td class="text-center"><?= $r['cascos'] ?? '' ?></td>
                        <td class="text-center"><?= $r['escudos'] ?? '' ?></td>
                        <td class="text-center"><?= $r['chalecos'] ?></td>
                        <td><?= $r['atendio'] ?></td>
                        <td><?= $r['digitalizado'] ?? '' ?></td>
                        <td><?= $r['observaciones_grales'] ?? '' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mostrar Toast si hay alertas tempranas (15-20 días)
    document.addEventListener('DOMContentLoaded', function () {
        const numAlertas = <?= $alertas_count ?>;
        if (numAlertas > 0) {
            document.getElementById('countAlertas').innerText = numAlertas;
            const toast = new bootstrap.Toast(document.getElementById('alertaDias'), { delay: 15000 });
            toast.show();
        }
    });

    // Lógica de Scroll Espejo (Mantenida)
    const wrapperTop = document.querySelector('.wrapper-top');
    const tableCont = document.querySelector('#contenedor-tabla');
    const espejo = document.querySelector('.div-espejo');
    const tablaReal = document.querySelector('.table');

    function ajustarScroll() {
        if(tablaReal && espejo) espejo.style.width = tablaReal.offsetWidth + "px";
    }
    window.onload = ajustarScroll;
    window.onresize = ajustarScroll;
    wrapperTop.onscroll = function() { tableCont.scrollLeft = wrapperTop.scrollLeft; };
    tableCont.onscroll = function() { wrapperTop.scrollLeft = tableCont.scrollLeft; };
</script>
</body>
</html>
