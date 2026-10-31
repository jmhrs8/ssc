<?php
/**
 * ARCHIVO: formulario.php (Módulo Armas)
 * CORRECCIÓN: Error 1054 (Columna 'activo' inexistente) y optimización de carga.
 */
require_once "../../config/conexion.php";

$id = $_GET['id'] ?? null;
$d = [];

if ($id) {
    $stmt = $pdo->prepare("
        SELECT g.*, e.nombre_resguardante, e.lugar_siniestro, e.narracion
        FROM inventario_armas g
        LEFT JOIN espejo_siniestros_armas e ON g.id = e.id_arma
        WHERE g.id = ?
    ");
    $stmt->execute([$id]);
    $d = $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Función para generar select dinámico desde la tabla catalogos
 * CORRECCIÓN: Se eliminó el filtro 'activo = 1' que no existe en la BD.
 */
function generarSelectCatalogos($pdo, $categoria, $name, $valor_actual) {
    // Se quitó "AND activo = 1" para evitar el Error SQL 1054
    $stmt = $pdo->prepare("SELECT valor FROM catalogos WHERE categoria = ? ORDER BY valor ASC");
    $stmt->execute([$categoria]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<select name='$name' class='form-select form-select-sm'>";
    echo "<option value=''>-- SELECCIONE --</option>";
    foreach ($options as $opt) {
        $selected = (strtoupper(trim($valor_actual)) == strtoupper(trim($opt['valor']))) ? 'selected' : '';
        echo "<option value='".htmlspecialchars($opt['valor'])."' $selected>".htmlspecialchars($opt['valor'])."</option>";
    }
    echo "</select>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario de Armamento | SSC SYSTEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-size: 11px; text-transform: uppercase; }
        .card { border-radius: 10px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .section-header {
            background: #1a1a1a;
            color: #0dcaf0;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
            border-left: 4px solid #0dcaf0;
        }
        label { font-weight: 700; color: #444; margin-bottom: 2px; font-size: 10px; }
        input, select, textarea { text-transform: uppercase; font-size: 12px !important; }
        .form-control:focus, .form-select:focus { border-color: #0dcaf0; box-shadow: 0 0 0 0.25rem rgba(13, 202, 240, 0.1); }
        .bg-light-info { background-color: #f0faff; }
        .btn-save { color: #0dcaf0; font-weight: bold; }
    </style>
</head>
<body>

<div class="container-fluid mt-3 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3 px-3">
        <h4 class="fw-bold"><i class="fas fa-edit text-info"></i> <?= $id ? 'MODIFICAR REGISTRO #'.$id : 'NUEVO REGISTRO DE ARMAMENTO' ?></h4>
        <div class="btn-group">
            <a href="catalogos.php" class="btn btn-outline-secondary btn-sm shadow-sm"><i class="fas fa-cog"></i> CONFIGURAR LISTAS</a>
            <a href="index.php" class="btn btn-dark btn-sm shadow-sm"><i class="fas fa-arrow-left"></i> VOLVER AL LISTADO</a>
        </div>
    </div>

    <form action="guardar_armas.php" method="POST" class="card p-4 shadow-sm">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="section-header"><i class="fas fa-file-invoice"></i> 1. INFORMACIÓN DE PÓLIZA Y SINIESTRO</div>
        <div class="row g-2 mt-1">
            <div class="col-md-2">
                <label>PÓLIZA</label>
                <?php generarSelectCatalogos($pdo, 'POLIZA', 'poliza', $d['poliza'] ?? ''); ?>
            </div>
            <div class="col-md-2">
                <label>TIPO DE BIEN</label>
                <?php generarSelectCatalogos($pdo, 'TIPO_BIEN', 'tipo_bien', $d['tipo_bien'] ?? ''); ?>
            </div>
            <div class="col-md-2">
                <label>TIPO SINIESTRO</label>
                <?php generarSelectCatalogos($pdo, 'TIPO_SINIESTRO', 'tipo_siniestro', $d['tipo_siniestro'] ?? ''); ?>
            </div>
            <div class="col-md-1">
                <label>VIGENCIA</label>
                <input type="text" name="anio_vigencia" class="form-control form-control-sm" value="<?= $d['anio_vigencia'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label>FECHA SINIESTRO</label>
                <input type="date" name="fecha_siniestro_1" class="form-control form-control-sm" value="<?= $d['fecha_siniestro_1'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label>No. SINIESTRO</label>
                <input type="text" name="no_siniestro" class="form-control form-control-sm fw-bold border-info" value="<?= $d['no_siniestro'] ?? '' ?>">
            </div>
        </div>

        <div class="row g-2 mt-2">
            <div class="col-md-3">
                <label>DESPACHO (AJUSTADOR)</label>
                <?php generarSelectCatalogos($pdo, 'DESPACHO', 'despacho', $d['despacho'] ?? ''); ?>
            </div>
            <div class="col-md-3">
                <label>ASEGURADORA</label>
                <?php generarSelectCatalogos($pdo, 'ASEGURADORA', 'aseguradora', $d['aseguradora'] ?? ''); ?>
            </div>
            <div class="col-md-3">
                <label>STATUS (SEGURO)</label>
                <select name="status_seguro" class="form-select form-select-sm">
                    <option value="PENDIENTE" <?= ($d['status_seguro']??'')=='PENDIENTE'?'selected':'' ?>>PENDIENTE</option>
                    <option value="EN TRAMITE" <?= ($d['status_seguro']??'')=='EN TRAMITE'?'selected':'' ?>>EN TRAMITE</option>
                    <option value="CONCLUIDO" <?= ($d['status_seguro']??'')=='CONCLUIDO'?'selected':'' ?>>CONCLUIDO</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>No. EXPEDIENTE</label>
                <input type="text" name="no_expediente" class="form-control form-control-sm" value="<?= $d['no_expediente'] ?? '' ?>">
            </div>
        </div>

        <div class="section-header"><i class="fas fa-calendar-check"></i> 2. SEGUIMIENTO DE RECLAMACIÓN</div>
        <div class="row g-2 mt-1">
            <div class="col-md-4">
                <label>TIPO DE DAÑO / RECLAMACIÓN</label>
                <input type="text" name="tipo_dano" class="form-control form-control-sm" value="<?= $d['tipo_dano'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label class="text-danger">FECHA RECLAMO</label>
                <input type="date" name="fecha_reclamacion" class="form-control form-control-sm border-danger" value="<?= $d['fecha_reclamacion'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label>MES</label>
                <select name="mes_reclamacion" class="form-select form-select-sm">
                    <option value="">-- SELECCIONE --</option>
                    <?php
                    $meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
                    foreach($meses as $m) echo "<option value='$m' ".((($d['mes_reclamacion']??'')==$m)?'selected':'').">$m</option>";
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <label>ESTATUS DE TRÁMITE</label>
                <?php generarSelectCatalogos($pdo, 'STATUS_TRAMITE', 'status_tramite', $d['status_tramite'] ?? ''); ?>
            </div>
        </div>

        <div class="section-header"><i class="fas fa-tag"></i> 3. DETALLES DEL BIEN E IMPORTES</div>
        <div class="row g-2 mt-1">
            <div class="col-md-2">
                <label>MARCA</label>
                <input type="text" name="marca" class="form-control form-control-sm" value="<?= $d['marca'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label>MODELO</label>
                <input type="text" name="modelo" class="form-control form-control-sm" value="<?= $d['modelo'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label>SERIE / MATRÍCULA</label>
                <input type="text" name="serie_matricula_1" class="form-control form-control-sm fw-bold border-primary" value="<?= $d['serie_matricula_1'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label>IMPORTE CONVENIO</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" name="importe_convenio" class="form-control" value="<?= $d['importe_convenio'] ?? '0.00' ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label>TIPO DE PAGO</label>
                <input type="text" name="tipo_pago" class="form-control form-control-sm" value="<?= $d['tipo_pago'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label>COMPROBANTE</label>
                <input type="text" name="comprobante_pago" class="form-control form-control-sm" value="<?= $d['comprobante_pago'] ?? '' ?>">
            </div>
        </div>

        <div class="section-header"><i class="fas fa-folder-open"></i> 4. GESTIÓN DOCUMENTAL (OFICIOS)</div>
        <div class="row g-2 mt-1">
            <div class="col-md-2">
                <label>FECHA ACUSE RECLAMO</label>
                <input type="date" name="fecha_acuse" class="form-control form-control-sm" value="<?= $d['fecha_acuse'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label>FOLIO SDRA</label>
                <input type="text" name="folio_sdra" class="form-control form-control-sm" value="<?= $d['folio_sdra'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label>OF. RECIBIDO</label>
                <input type="text" name="of_recibido" class="form-control form-control-sm" value="<?= $d['of_recibido'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label>N° DE OFICIO</label>
                <input type="text" name="no_oficio" class="form-control form-control-sm" value="<?= $d['no_oficio'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label>DIGITALIZADO</label>
                <select name="digitalizado" class="form-select form-select-sm">
                    <option value="NO" <?= ($d['digitalizado']??'')=='NO'?'selected':'' ?>>NO</option>
                    <option value="SI" <?= ($d['digitalizado']??'')=='SI'?'selected':'' ?>>SI</option>
                </select>
            </div>
        </div>

        <div class="section-header"><i class="fas fa-boxes"></i> 5. INVENTARIO DE ARTÍCULOS</div>
        <div class="row g-2 mt-1 text-center bg-light-info p-2 rounded">
            <div class="col"><label>ARMAS</label><input type="number" name="armas" class="form-control form-control-sm text-center" value="<?= $d['armas'] ?? 0 ?>"></div>
            <div class="col"><label>CARGADOR</label><input type="number" name="cargador" class="form-control form-control-sm text-center" value="<?= $d['cargador'] ?? 0 ?>"></div>
            <div class="col"><label>CARTUCHOS</label><input type="number" name="cartuchos" class="form-control form-control-sm text-center" value="<?= $d['cartuchos'] ?? 0 ?>"></div>
            <div class="col"><label>CASCOS</label><input type="number" name="cascos" class="form-control form-control-sm text-center" value="<?= $d['cascos'] ?? 0 ?>"></div>
            <div class="col"><label>ESCUDOS</label><input type="number" name="escudos" class="form-control form-control-sm text-center" value="<?= $d['escudos'] ?? 0 ?>"></div>
            <div class="col"><label>CHALECOS</label><input type="number" name="chalecos" class="form-control form-control-sm text-center" value="<?= $d['chalecos'] ?? 0 ?>"></div>
        </div>
        <div class="row mt-2">
             <div class="col-md-6">
                <label>BIENES DIVERSOS</label>
                <input type="text" name="bienes_diversos" class="form-control form-control-sm" value="<?= $d['bienes_diversos'] ?? '' ?>">
            </div>
            <div class="col-md-6">
                <label>CANDADO DE MANOS</label>
                <input type="number" name="candado_manos" class="form-control form-control-sm" value="<?= $d['candado_manos'] ?? 0 ?>">
            </div>
        </div>

        <div class="section-header"><i class="fas fa-user-shield"></i> 6. RESGUARDANTE Y OBSERVACIONES</div>
        <div class="row g-2 mt-1">
            <div class="col-md-4">
                <label>NOMBRE DEL RESGUARDANTE</label>
                <input type="text" name="nombre_resguardante" class="form-control form-control-sm" value="<?= $d['nombre_resguardante'] ?? '' ?>">
            </div>
            <div class="col-md-4">
                <label>ATENDIÓ</label>
                <?php generarSelectCatalogos($pdo, 'ATENDIO', 'atendio', $d['atendio'] ?? ''); ?>
            </div>
            <div class="col-md-4">
                <label>LUGAR SINIESTRO</label>
                <input type="text" name="lugar_siniestro" class="form-control form-control-sm" value="<?= $d['lugar_siniestro'] ?? '' ?>">
            </div>
            <div class="col-md-12 mt-2">
                <label>OBSERVACIONES GENERALES (Y NARRACIÓN)</label>
                <textarea name="narracion" class="form-control" rows="3" placeholder="DESCRIBA LOS HECHOS AQUÍ..."><?= $d['narracion'] ?? '' ?></textarea>
            </div>
        </div>

        <div class="text-center mt-4 mb-2">
            <button type="submit" class="btn btn-dark btn-lg shadow px-5 btn-save">
                <i class="fas fa-save"></i> <?= $id ? 'ACTUALIZAR REGISTRO' : 'GUARDAR NUEVO REGISTRO' ?>
            </button>
        </div>
    </form>
</div>

</body>
</html>
