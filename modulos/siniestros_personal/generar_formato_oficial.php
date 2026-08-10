<?php
session_start();
require_once "../../config/conexion.php";

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de registro no especificado.");
}

$stmt = $pdo->prepare("SELECT * FROM siniestros_personal WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Registro no encontrado.");
}

// Separar fecha de siniestro en Día, Mes, Año
$fecha_ts = strtotime($row['fecha_de_siniestro'] ?? date('Y-m-d'));
$dia_s = date('d', $fecha_ts);
$mes_s = date('m', $fecha_ts);
$anio_s = date('Y', $fecha_ts);

// Lógica inteligente para separar el nombre si los campos de apellidos están vacíos
$nombre_completo_db = trim($row['nombre'] ?? '');
$ap_pat_db = trim($row['apellido_paterno'] ?? '');
$ap_mat_db = trim($row['apellido_materno'] ?? '');

if (empty($ap_pat_db) && !empty($nombre_completo_db)) {
    $partes_nombre = explode(' ', preg_replace('/\s+/', ' ', $nombre_completo_db));
    $total_partes = count($partes_nombre);

    if ($total_partes >= 3) {
        // Asumimos que los últimos dos son apellidos
        $ap_mat_db = array_pop($partes_nombre);
        $ap_pat_db = array_pop($partes_nombre);
        $nombre_limpio = implode(' ', $partes_nombre);
    } else if ($total_partes == 2) {
        $ap_pat_db = array_pop($partes_nombre);
        $nombre_limpio = implode(' ', $partes_nombre);
    } else {
        $nombre_limpio = $nombre_completo_db;
    }
} else {
    $nombre_limpio = $nombre_completo_db;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTE OFICIAL - <?= htmlspecialchars($row['no_folio']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #555; font-family: 'Arial', sans-serif; font-size: 9px; color: #000; text-transform: uppercase; margin: 0; padding: 20px 0; }
        .sheet { width: 215mm; height: 279mm; padding: 12mm 15mm; margin: 0 auto 20mm auto; background: white; position: relative; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.5); page-break-after: always; }
        
        .table-oficial { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .table-oficial th, .table-oficial td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; font-size: 9px; }
        
        .field-label { font-size: 7.5px; color: #333; font-weight: bold; display: block; margin-bottom: 2px; }
        .field-value { font-size: 9.5px; font-weight: bold; min-height: 12px; }
        
        .line-write { border-bottom: 1px solid #000; min-height: 22px; margin-bottom: 6px; font-weight: bold; padding-left: 3px; }
        .line-write-tall { border-bottom: 1px solid #000; min-height: 45px; margin-bottom: 6px; font-weight: bold; padding-left: 3px; }

        .codigo-footer { position: absolute; bottom: 10mm; left: 15mm; font-size: 8px; font-weight: bold; }

        @media print {
            body { background: none; padding: 0; }
            .no-print { display: none !important; }
            .sheet { margin: 0; box-shadow: none; width: 215mm; height: 279mm; page-break-after: always; padding: 10mm 12mm; }
        }
    </style>
</head>
<body>

<div class="container text-center no-print mb-4">
    <button onclick="window.print()" class="btn btn-warning fw-bold px-4 py-2 shadow"><i class="fas fa-print me-2"></i> IMPRIMIR / GUARDAR EN PDF</button>
    <a href="index.php" class="btn btn-light fw-bold px-4 py-2 ms-2 shadow"><i class="fas fa-arrow-left me-2"></i> REGRESAR</a>
</div>

<!-- ================================================================= -->
<!-- HOJA 1: REPORTE DE ELEMENTOS OPERATIVOS LESIONADOS                -->
<!-- ================================================================= -->
<div class="sheet">
    <!-- Cabecera Institucional Exacta con Logo e Imagen Superior y Folio Arriba -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; border: none;">
        <tr>
            <td style="width: 25%; border: none; vertical-align: middle;">
                <img src="../../uploads/sistema/header_logo.png" alt="Logo SSC" style="max-height: 45px; max-width: 100%; object-fit: contain;" onerror="this.style.display='none'">
            </td>
            <td style="width: 45%; border: none; text-align: center; vertical-align: middle; font-size: 9.5px; line-height: 1.2;">
                <b>CIUDAD DE MÉXICO</b><br>CAPITAL DE LA TRANSFORMACIÓN
            </td>
            <td style="width: 30%; border: none; text-align: right; vertical-align: middle; font-size: 10px;">
                <b>SECRETARÍA DE SEGURIDAD CIUDADANA</b><br>
                <span style="color: #dc3545; font-size: 11px;">FOLIO: <?= htmlspecialchars($row['no_folio']) ?></span>
            </td>
        </tr>
    </table>

    <!-- Título y Cuadros de Fecha -->
    <table class="table-oficial" style="margin-bottom: 10px;">
        <tr>
            <td style="width: 70%; vertical-align: middle; background: #eaeaea; font-weight: bold; font-size: 11px; padding: 8px;">
                REPORTE DE ELEMENTOS OPERATIVOS LESIONADOS
            </td>
            <td style="width: 30%; padding: 0; border: none;">
                <table style="width: 100%; border-collapse: collapse; text-align: center;">
                    <tr>
                        <td colspan="3" style="border: 1px solid #000; background: #eaeaea; font-size: 8px; font-weight: bold;">FECHA</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; font-size: 7.5px;">DÍA</td>
                        <td style="border: 1px solid #000; font-size: 7.5px;">MES</td>
                        <td style="border: 1px solid #000; font-size: 7.5px;">AÑO</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; font-weight: bold;"><?= $dia_s ?></td>
                        <td style="border: 1px solid #000; font-weight: bold;"><?= $mes_s ?></td>
                        <td style="border: 1px solid #000; font-weight: bold;"><?= $anio_s ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Bloque: Nombre del Elemento Separado Correctamente -->
    <div style="border: 1px solid #000; padding: 5px; margin-bottom: 6px;">
        <span style="font-size: 8px; font-weight: bold; display: block; margin-bottom: 4px;">NOMBRE DEL ELEMENTO</span>
        <table class="table-oficial" style="margin-bottom: 0;">
            <tr>
                <td style="width: 32%;">
                    <span class="field-label">NOMBRE(S)</span>
                    <div class="field-value"><?= htmlspecialchars($nombre_limpio) ?></div>
                </td>
                <td style="width: 30%;">
                    <span class="field-label">APELLIDO PATERNO</span>
                    <div class="field-value"><?= htmlspecialchars($ap_pat_db) ?></div>
                </td>
                <td style="width: 30%;">
                    <span class="field-label">APELLIDO MATERNO</span>
                    <div class="field-value"><?= htmlspecialchars($ap_mat_db) ?></div>
                </td>
                <td style="width: 8%;">
                    <span class="field-label">EDAD</span>
                    <div class="field-value"><?= htmlspecialchars($row['edad']) ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="field-label">SECTOR O UPC</span>
                    <div class="field-value"><?= htmlspecialchars($row['sector_upc'] ?? $row['area_adscripcion']) ?></div>
                </td>
                <td colspan="2">
                    <span class="field-label">N.° DE EMPLEADO</span>
                    <div class="field-value"><?= htmlspecialchars($row['no_empleado']) ?></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Campos de Líneas Sencillas -->
    <div style="margin-bottom: 4px;">
        <span class="field-label">LUGAR DE ACCIDENTE</span>
        <div class="line-write"><?= htmlspecialchars($row['lugar_accidente'] ?? '') ?></div>
    </div>

    <div style="margin-bottom: 4px;">
        <span class="field-label">DESCRIPCIÓN DEL ACCIDENTE</span>
        <div class="line-write-tall"><?= htmlspecialchars($row['observaciones'] ?? $row['lesiones']) ?></div>
    </div>

    <table class="table-oficial">
        <tr>
            <td style="width: 50%;">
                <span class="field-label">N.° ECONÓMICO</span>
                <div class="field-value"><?= htmlspecialchars($row['no_economico'] ?? $row['unidad_vehicular']) ?></div>
            </td>
            <td style="width: 50%;">
                <span class="field-label">N.° DE SINIESTRO</span>
                <div class="field-value"><?= htmlspecialchars($row['reporte']) ?></div>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 4px;">
        <span class="field-label">SUPERVISOR(A) DE RIESGOS Y ASEGURAMIENTO</span>
        <div class="line-write"><?= htmlspecialchars($row['supervisor_riesgos'] ?? '') ?></div>
    </div>

    <table class="table-oficial">
        <tr>
            <td style="width: 50%;">
                <span class="field-label">N.° DE AMBULANCIA</span>
                <div class="field-value"><?= htmlspecialchars($row['no_ambulancia'] ?? '') ?></div>
            </td>
            <td style="width: 50%;">
                <span class="field-label">HOSPITAL</span>
                <div class="field-value"><?= htmlspecialchars($row['hospital']) ?></div>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 4px;">
        <span class="field-label">DIAGNÓSTICO</span>
        <div class="line-write"><?= htmlspecialchars($row['diagnostico'] ?? $row['lesiones']) ?></div>
    </div>

    <table class="table-oficial" style="border: none; margin-bottom: 4px;">
        <tr>
            <td style="border: none; width: 50%; padding-left: 0;">
                <span class="field-label">QUIÉN REPORTA</span>
                <div class="line-write" style="margin-bottom: 0;"><?= htmlspecialchars($row['quien_reporta'] ?? '') ?></div>
            </td>
            <td style="border: none; width: 50%; padding-right: 0;">
                <span class="field-label">QUIÉN RECIBE</span>
                <div class="line-write" style="margin-bottom: 0;"><?= htmlspecialchars($row['quien_recibe'] ?? '') ?></div>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 4px;">
        <span class="field-label">OBSERVACIONES</span>
        <div class="line-write-tall"><?= htmlspecialchars($row['observaciones'] ?? '') ?></div>
    </div>

    <!-- Firma -->
    <div class="text-center" style="margin-top: 35px;">
        <div style="width: 250px; border-top: 1px solid #000; margin: 0 auto; padding-top: 3px; font-weight: bold; font-size: 9px;">NOMBRE Y FIRMA</div>
    </div>

    <div class="codigo-footer">
        FO-SSC-07-DGRMAYs-07 V2.25
    </div>
</div>


<!-- ================================================================= -->
<!-- HOJA 2: BITÁCORA DE SEGUIMIENTO                                   -->
<!-- ================================================================= -->
<div class="sheet">
    <div class="fw-bold mb-2" style="font-size: 10.5px;">BITÁCORA DE SEGUIMIENTO</div>
    
    <div style="border: 1px solid #000; padding: 5px; margin-bottom: 10px;">
        <span style="font-size: 8px; font-weight: bold; display: block; margin-bottom: 4px;">LESIONADOS ENVIADOS A HOSPITALES DE RED (SEGUROS)</span>
        
        <table class="table-oficial" style="margin-bottom: 0;">
            <tr>
                <td colspan="4">
                    <span class="field-label">NOMBRE DE LESIONADO</span>
                    <div class="field-value"><?= htmlspecialchars(trim($nombre_limpio . ' ' . $ap_pat_db . ' ' . $ap_mat_db)) ?></div>
                </td>
                <td colspan="3" class="text-center bg-light" style="font-weight: bold; vertical-align: middle;">
                    CAUSA DEL SINIESTRO
                </td>
            </tr>
            <tr class="text-center" style="background: #f2f2f2;">
                <td style="width: 25%;">NOMBRE(S)</td>
                <td style="width: 20%;">APELLIDO PATERNO</td>
                <td style="width: 20%;">APELLIDO MATERNO</td>
                <td style="width: 15%;">FECHA DE SINIESTRO</td>
                <td style="width: 7%;">G.M.A.</td>
                <td style="width: 7%;">A.P.</td>
                <td style="width: 6%;">OTROS</td>
            </tr>
            <tr class="text-center">
                <td><?= htmlspecialchars($nombre_limpio) ?></td>
                <td><?= htmlspecialchars($ap_pat_db) ?></td>
                <td><?= htmlspecialchars($ap_mat_db) ?></td>
                <td><?= htmlspecialchars($row['fecha_de_siniestro']) ?></td>
                <td><b><?= (stripos($row['causa_resumido'], 'GMA') !== false || stripos($row['causa_resumido'], 'G.M.A.') !== false)?'X':'' ?></b></td>
                <td><b><?= (stripos($row['causa_resumido'], 'AP') !== false || stripos($row['causa_resumido'], 'A.P.') !== false)?'X':'' ?></b></td>
                <td><b><?= (stripos($row['causa_resumido'], 'OTROS') !== false)?'X':'' ?></b></td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="field-label">HOSPITAL</span>
                    <div class="field-value"><?= htmlspecialchars($row['hospital']) ?></div>
                </td>
                <td colspan="2">
                    <span class="field-label">FECHA DE INGRESO</span>
                    <div class="field-value"><?= htmlspecialchars($row['fecha_ingreso_hospital'] ?? '') ?></div>
                </td>
                <td colspan="2">
                    <span class="field-label">HORA DE INGRESO</span>
                    <div class="field-value"><?= htmlspecialchars($row['hora_ingreso_hospital'] ?? '') ?></div>
                </td>
            </tr>
        </table>
    </div>

    <div style="border: 1px solid #000; padding: 5px; margin-bottom: 10px;">
        <span style="font-size: 8px; font-weight: bold; display: block; margin-bottom: 4px;">NOMBRE DE LA/EL FUNCIONARIA(O) DE CABINA QUE TOMA EL REPORTE</span>
        <table class="table-oficial" style="margin-bottom: 0;">
            <tr>
                <td style="width: 33%;">
                    <span class="field-label">NOMBRE(S)</span>
                    <div class="field-value"><?= htmlspecialchars($row['cabina_nombre'] ?? '') ?></div>
                </td>
                <td style="width: 33%;">
                    <span class="field-label">APELLIDO PATERNO</span>
                    <div class="field-value"><?= htmlspecialchars($row['cabina_ap_paterno'] ?? '') ?></div>
                </td>
                <td style="width: 34%;">
                    <span class="field-label">APELLIDO MATERNO</span>
                    <div class="field-value"><?= htmlspecialchars($row['cabina_ap_materno'] ?? '') ?></div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-bottom: 8px;">
        <span class="field-label" style="font-size: 8px;">ACTIVIDADES DESARROLLADAS POR EL FUNCIONARIO DE CABINA DE RADIO EN TURNO (DESCRIBIR DE FORMA RESUMIDA CADA ACTIVIDAD)</span>
        <div style="border: 1px solid #000; min-height: 180px; padding: 6px; font-weight: bold; background: #fff;">
            <?= nl2br(htmlspecialchars($row['actividades_cabina'] ?? '')) ?>
        </div>
    </div>

    <div>
        <span class="field-label" style="font-size: 8px;">CONCLUSIONES</span>
        <div style="border: 1px solid #000; min-height: 130px; padding: 6px; font-weight: bold; background: #fff;">
            <?= nl2br(htmlspecialchars($row['conclusiones'] ?? '')) ?>
        </div>
    </div>
</div>

</body>
</html>
