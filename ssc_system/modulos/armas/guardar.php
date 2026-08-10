<?php
require_once "../../config/conexion.php";

$id = $_GET['id'] ?? null;
$registro = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM inventario_armas WHERE id = ?");
    $stmt->execute([$id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
}

$archivo_fondo = "../../uploads/sistema/fondo_actual.jpg";
$fondo_url = file_exists($archivo_fondo) ? $archivo_fondo : ""; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Registro de Armamento | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-size: 12px; }
        body::before {
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1; background-image: url('<?= $fondo_url ?>');
            background-size: cover; background-position: center; opacity: 0.2;
        }
        .card-custom { background: rgba(255, 255, 255, 0.95); border-radius: 15px; border: none; }
        .form-label { font-weight: bold; color: #333; margin-bottom: 2px; text-transform: uppercase; font-size: 10px; }
        .form-control-sm { border-radius: 5px; }
        .section-title { border-left: 4px solid #8b0000; padding-left: 10px; margin-bottom: 15px; font-weight: bold; color: #1a1a1a; }
    </style>
</head>
<body class="p-4">

<div class="container card-custom shadow p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0"><i class="fas fa-edit text-warning"></i> EXPEDIENTE DE ARMAMENTO</h5>
        <a href="index.php" class="btn btn-dark btn-sm"><i class="fas fa-arrow-left"></i> Volver al Listado</a>
    </div>

    <form action="guardar.php" method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">
        
        <div class="row g-2">
            <?php
            $campos = [
                'poliza' => 'Póliza', 'tipo_bien' => 'Tipo de Bien', 'tipo_siniestro' => 'Tipo de Siniestro',
                'anio_vigencia' => 'Año/Vigencia', 'fecha_siniestro_1' => 'Fecha Siniestro (1)', 'no_siniestro' => 'No. Siniestro',
                'despacho' => 'Despacho', 'aseguradora' => 'Aseguradora', 'status_seguro' => 'Status Seguro',
                'serie_matricula_1' => 'Serie/Matrícula', 'no_expediente' => 'No. Expediente', 'folio_sdra' => 'Folio SDRA',
                'of_recibido' => 'Of. Recibido', 'siniestro_detalle' => 'Siniestro', 'tipo_dano' => 'Tipo de Daño',
                'marca' => 'Marca', 'modelo' => 'Modelo', 'matricula_o_serie_2' => 'Matrícula o Serie (2)',
                'fecha_siniestro_2' => 'Fecha Siniestro (2)', 'fecha_reclamacion' => 'Fecha Reclamación', 'no_oficio' => 'N° de Oficio',
                'candado_manos' => 'Candado de Manos', 'armas' => 'Armas', 'cargador' => 'Cargador',
                'cartuchos' => 'Cartuchos', 'cascos' => 'Cascos', 'escudos' => 'Escudos',
                'chalecos' => 'Chalecos', 'atendio' => 'Atendió', 'status_interno' => 'Status Interno'
            ];

            foreach ($campos as $name => $label): ?>
                <div class="col-md-3">
                    <label class="form-label"><?= $label ?></label>
                    <input type="text" name="<?= $name ?>" class="form-control form-control-sm" 
                           value="<?= htmlspecialchars($registro[$name] ?? '') ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4 pt-3 border-top text-end">
            <button type="submit" class="btn btn-primary px-5 shadow-sm">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

</body>
</html>
