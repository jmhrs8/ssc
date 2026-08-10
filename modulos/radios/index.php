<?php
session_start(); // Línea indispensable para leer los permisos

// 1. VALIDACIÓN DE SEGURIDAD
$nivel_actual = strtoupper(trim($_SESSION['nivel'] ?? ''));
$permiso_radios = $_SESSION['permiso_radios'] ?? 0;

// Si no ha iniciado sesión o no es admin y no tiene permiso de radios, se redirige al inicio
if (!$nivel_actual || ($nivel_actual !== 'ADMIN_GENERAL' && $permiso_radios != 1)) {
    header("Location: ../../index.php?error=acceso_denegado");
    exit();
}

require_once "../../config/conexion.php";

$search = $_GET['buscar'] ?? '';

try {
    // CONSULTA CORREGIDA: Busca en los nuevos campos de la base de datos
    $query = "SELECT * FROM inventario_radio WHERE
              poliza LIKE ? OR
              serie_matricula LIKE ? OR
              no_expediente LIKE ? OR
              no_siniestro LIKE ?
              ORDER BY id ASC";

    $stmt = $pdo->prepare($query);
    $term = "%$search%";
    $stmt->execute([$term, $term, $term, $term]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas totales
    $stats = $pdo->query("SELECT COUNT(*) as total FROM inventario_radio")->fetch();
} catch (PDOException $e) {
    die("ERROR DE CONEXIÓN: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RADIOS Y SEMOVIENTES | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-size: 11px; background-color: #f4f6f9; text-transform: uppercase; }
        input, select, textarea { text-transform: uppercase; }

        /* ESTILOS DOBLE BARRA DE NAVEGACIÓN */
        .wrapper-top {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            height: 20px;
            margin-bottom: 5px;
        }
        .div-espejo {
            width: 3500px; /* Ajustado para las 20 columnas */
            height: 20px;
        }

        .table-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .table-responsive {
            overflow-x: auto;
            border-radius: 5px;
        }
        .table {
            min-width: 3500px;
            margin-bottom: 0;
        }
        .table td, .table th {
            padding: 6px 10px !important;
            vertical-align: middle;
            white-space: nowrap;
        }

        .header-top { background: #1a1a1a; color: white; padding: 12px; }

        /* DISEÑO DE LAS BARRAS DE SCROLL */
        .wrapper-top::-webkit-scrollbar, .table-responsive::-webkit-scrollbar { height: 12px; }
        .wrapper-top::-webkit-scrollbar-thumb, .table-responsive::-webkit-scrollbar-thumb {
            background: #0dcaf0;
            border-radius: 10px;
        }
        .wrapper-top::-webkit-scrollbar-track, .table-responsive::-webkit-scrollbar-track {
            background: #e9ecef;
        }
    </style>
</head>
<body>

<div class="header-top shadow-sm mb-3">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-broadcast-tower"></i> RADIOS Y SEMOVIENTES</h5>
            <small class="text-warning">REGISTROS: <?= $stats['total'] ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="../../index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-home"></i> INICIO</a>
            <a href="reporte_rubros.php" class="btn btn-info btn-sm text-white"><i class="fas fa-chart-bar"></i> REPORTES</a>
            <a href="subir_excel.php" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> IMPORTAR</a>
            <a href="bajar_excel.php" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> EXPORTAR</a>
            <button onclick="confirmarVaciado()" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> VACIAR</button>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="buscar" class="form-control form-control-sm"
                           placeholder="BUSCAR POR PÓLIZA, SERIE, EXPEDIENTE O SINIESTRO..."
                           onkeyup="this.value = this.value.toUpperCase();"
                           value="<?= htmlspecialchars(strtoupper($search)) ?>">
                </div>
                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-dark btn-sm px-4">FILTRAR</button>
                    <a href="formulario.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> NUEVO</a>
                </div>
            </form>
        </div>
    </div>

    <div class="wrapper-top shadow-sm">
        <div class="div-espejo"></div>
    </div>

    <div class="table-container shadow-sm">
        <div class="table-responsive" id="contenedor-tabla">
            <table class="table table-hover table-bordered bg-white">
                <thead class="table-dark text-center">
                    <tr>
                        <th>ACCIONES</th>
                        <th>PÓLIZA</th>
                        <th>TIPO DE BIEN</th>
                        <th>TIPO DE SINIESTRO</th>
                        <th>AÑO/VIGENCIA</th>
                        <th>FECHA DE SINIESTRO</th>
                        <th>No. SINIESTRO</th>
                        <th>DESPACHO</th>
                        <th>ASEGURADORA</th>
                        <th>ESTATUS</th>
                        <th>No. EXPEDIENTE</th>
                        <th>TIPO DE DAÑO O RECLAMACIÓN</th>
                        <th>RECLAMO</th>
                        <th>MARCA</th>
                        <th>MODELO</th>
                        <th>SERIE/MATRÍCULA</th>
                        <th>ESTATUS DE TRÁMITE</th>
                        <th>OBSERVACIONES</th>
                        <th>IMPORTE DEL CONVENIO</th>
                        <th>TIPO DE PAGO</th>
                        <th>COMPROBANTE DE PAGO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($resultados) > 0): ?>
                        <?php foreach ($resultados as $r): ?>
                        <tr>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="formulario.php?id=<?= $r['id'] ?>" class="btn btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                    <!-- BOTÓN INTEGRADO: Formulario de Siniestros Institucional -->
                                    <a href="captura_siniestro_radios.php?id=<?= $r['id'] ?>" class="btn btn-dark text-white" title="Reportar Siniestro"><i class="fas fa-exclamation-triangle"></i></a>
                                    <a href="acciones.php?eliminar=<?= $r['id'] ?>" class="btn btn-danger" onclick="return confirm('¿BORRAR ESTE REGISTRO?')" title="Eliminar"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                            <td class="fw-bold text-primary"><?= $r['poliza'] ?></td>
                            <td><?= $r['tipo_bien'] ?></td>
                            <td><?= $r['tipo_siniestro'] ?></td>
                            <td><?= $r['anio_vigencia'] ?></td>
                            <td><?= $r['fecha_siniestro'] ?></td>
                            <td><?= $r['no_siniestro'] ?></td>
                            <td><?= $r['despacho'] ?></td>
                            <td><?= $r['aseguradora'] ?></td>
                            <td><span class="badge bg-secondary p-2"><?= $r['estatus'] ?></span></td>
                            <td class="fw-bold"><?= $r['no_expediente'] ?></td>
                            <td><?= $r['tipo_dano_reclamacion'] ?></td>
                            <td><?= $r['reclamo'] ?></td>
                            <td><?= $r['marca'] ?></td>
                            <td><?= $r['modelo'] ?></td>
                            <td class="fw-bold text-success"><?= $r['serie_matricula'] ?></td>
                            <td><?= $r['estatus_tramite'] ?></td>
                            <td><?= $r['observaciones'] ?></td>
                            <td class="text-end fw-bold">$ <?= number_format($r['importe_convenio'], 2) ?></td>
                            <td><?= $r['tipo_pago'] ?></td>
                            <td><?= $r['comprobante_pago'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="21" class="text-center py-4">NO SE ENCONTRARON REGISTROS</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const wrapperTop = document.querySelector('.wrapper-top');
    const tableCont = document.querySelector('#contenedor-tabla');
    const espejo = document.querySelector('.div-espejo');

    window.onload = function() {
        const tablaReal = document.querySelector('.table');
        espejo.style.width = tablaReal.offsetWidth + "px";
    }

    wrapperTop.onscroll = function() {
        tableCont.scrollLeft = wrapperTop.scrollLeft;
    };
    tableCont.onscroll = function() {
        wrapperTop.scrollLeft = tableCont.scrollLeft;
    };

    function confirmarVaciado() {
        if (confirm("¿BORRAR TODO EL INVENTARIO DE RADIOS Y SEMOVIENTES?")) {
            if (prompt("ESCRIBA 'ELIMINAR TODO' PARA CONFIRMAR:") === "ELIMINAR TODO") {
                window.location.href = "acciones.php?cmd=truncate";
            }
        }
    }
</script>
</body>
</html>
