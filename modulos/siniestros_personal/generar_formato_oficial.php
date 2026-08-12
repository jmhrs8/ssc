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

// --- 1. PROCESAMIENTO DE DATOS DEL ELEMENTO ---
$elem_nombre_db = trim($row['nombre'] ?? '');
$elem_ap_pat    = trim($row['apellido_paterno'] ?? '');
$elem_ap_mat    = trim($row['apellido_materno'] ?? '');
$elem_nombres   = $elem_nombre_db;

if (empty($elem_ap_pat) && $elem_nombre_db !== '') {
    $partes = preg_replace('/\s+/', ' ', $elem_nombre_db);
    $arr = explode(' ', $partes);
    if (count($arr) >= 3) {
        $elem_ap_mat = array_pop($arr);
        $elem_ap_pat = array_pop($arr);
        $elem_nombres = implode(' ', $arr);
    } elseif (count($arr) === 2) {
        $elem_ap_pat = array_pop($arr);
        $elem_nombres = implode(' ', $arr);
    }
}

// --- 2. PROCESAMIENTO DE DATOS DEL LESIONADO ---
$les_nombre_db = trim($row['lesionado_nombre'] ?? '');
$les_ap_pat    = trim($row['lesionado_ap_paterno'] ?? '');
$les_ap_mat    = trim($row['lesionado_ap_materno'] ?? '');

if (!empty($les_nombre_db) || !empty($les_ap_pat) || !empty($les_ap_mat)) {
    $les_nombres = $les_nombre_db;
    $lesionado_completo = trim($les_nombres . ' ' . $les_ap_pat . ' ' . $les_ap_mat);
} else {
    // Si no se capturó lesionado aparte, toma los datos del elemento
    $les_nombres = $elem_nombres;
    $les_ap_pat  = $elem_ap_pat;
    $les_ap_mat  = $elem_ap_mat;
    $lesionado_completo = trim($les_nombres . ' ' . $les_ap_pat . ' ' . $les_ap_mat);
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
        body { background: #444; font-family: 'Arial', sans-serif; font-size: 10px; color: #000; text-transform: uppercase; margin: 0; padding: 20px 0; }
        
        .sheet { 
            width: 215mm; 
            min-height: 279mm; 
            padding: 15mm 18mm; 
            margin: 0 auto 20mm auto; 
            background: white; 
            position: relative; 
            box-sizing: border-box; 
            box-shadow: 0 0 12px rgba(0,0,0,0.6); 
            page-break-after: always; 
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .table-oficial { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .table-oficial th, .table-oficial td { border: 1px solid #000; padding: 5px 7px; vertical-align: top; font-size: 10px; }

        .field-label { font-size: 8px; color: #333; font-weight: bold; display: block; margin-bottom: 3px; }
        .field-value { font-size: 11px; font-weight: bold; min-height: 15px; }

        .line-write { border-bottom: 1px solid #000; min-height: 28px; margin-bottom: 8px; font-weight: bold; padding-left: 4px; font-size: 10.5px; }
        .line-write-tall { border-bottom: 1px solid #000; min-height: 55px; margin-bottom: 8px; font-weight: bold; padding-left: 4px; font-size: 10.5px; }

        .firma-container { text-align: center; margin-top: auto; padding-top: 25px; }
        .firma-linea { width: 280px; border-top: 1px solid #000; margin: 0 auto; padding-top: 4px; font-weight: bold; font-size: 10px; }

        .codigo-footer { font-size: 9px; font-weight: bold; margin-top: 10px; }

        @media print {
            body { background: none; padding: 0; }
            .no-print { display: none !important; }
            .sheet { margin: 0; box-shadow: none; width: 215mm; height: 279mm; page-break-after: always; padding: 12mm 15mm; }
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
    <div>
        <!-- Cabecera Institucional con Logotipo Ajustado y Grande -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px; border: none;">
            <tr>
                <td style="width: 28%; border: none; vertical-align: middle;">
                    <img src="../../uploads/sistema/logo_ssc.png" alt="Logo SSC" style="max-height: 100px; max-width: 100%; object-fit: contain;" onerror="this.style.display='none'">
                </td>
                <td style="width: 42%; border: none; text-align: center; vertical-align: middle; font-size: 11px; line-height: 1.3;">
                    <b>CIUDAD DE MÉXICO</b><br><span style="font-size: 9.5px; color: #444;">CAPITAL DE LA TRANSFORMACIÓN</span>
                </td>
                <td style="width: 30%; border: none; text-align: right; vertical-align: middle; font-size: 10.5px;">
                    <b>SECRETARÍA DE SEGURIDAD CIUDADANA</b><br>
                    <span style="color: #dc3545; font-size: 12px; font-weight: bold;">FOLIO: <?= htmlspecialchars($row['no_folio']) ?></span>
                </td>
            </tr>
        </table>

        <table class="table-oficial" style="margin-bottom: 12px;">
            <tr>
                <td style="width: 70%; vertical-align: middle; background: #eaeaea; font-weight: bold; font-size: 12px; padding: 8px;">
                    REPORTE DE ELEMENTOS OPERATIVOS LESIONADOS
                </td>
                <td style="width: 30%; padding: 0; border: none;">
                    <table style="width: 100%; border-collapse: collapse; text-align: center;">
                        <tr>
                            <td colspan="3" style="border: 1px solid #000; background: #eaeaea; font-size: 8.5px; font-weight: bold;">FECHA</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; font-size: 8px;">DÍA</td>
                            <td style="border: 1px solid #000; font-size: 8px;">MES</td>
                            <td style="border: 1px solid #000; font-size: 8px;">AÑO</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; font-weight: bold; font-size: 11px;"><?= $dia_s ?></td>
                            <td style="border: 1px solid #000; font-weight: bold; font-size: 11px;"><?= $mes_s ?></td>
                            <td style="border: 1px solid #000; font-weight: bold; font-size: 11px;"><?= $anio_s ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Datos del Elemento -->
        <div style="border: 1px solid #000; padding: 6px; margin-bottom: 10px;">
            <span style="font-size: 8.5px; font-weight: bold; display: block; margin-bottom: 4px; color: #333;">NOMBRE DEL ELEMENTO (REGISTRO / PADRÓN)</span>
            <table class="table-oficial" style="margin-bottom: 0;">
                <tr>
                    <td style="width: 32%;">
                        <span class="field-label">NOMBRE(S)</span>
                        <div class="field-value"><?= htmlspecialchars($elem_nombres) ?></div>
                    </td>
                    <td style="width: 30%;">
                        <span class="field-label">APELLIDO PATERNO</span>
                        <div class="field-value"><?= htmlspecialchars($elem_ap_pat) ?></div>
                    </td>
                    <td style="width: 30%;">
                        <span class="field-label">APELLIDO MATERNO</span>
                        <div class="field-value"><?= htmlspecialchars($elem_ap_mat) ?></div>
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

        <div style="margin-bottom: 6px;">
            <span class="field-label">LUGAR DE ACCIDENTE</span>
            <div class="line-write"><?= htmlspecialchars($row['lugar_accidente'] ?? '') ?></div>
        </div>

        <div style="margin-bottom: 6px;">
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

        <div style="margin-bottom: 6px;">
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

        <div style="margin-bottom: 6px;">
            <span class="field-label">DIAGNÓSTICO</span>
            <div class="line-write"><?= htmlspecialchars($row['diagnostico'] ?? $row['lesiones']) ?></div>
        </div>

        <table class="table-oficial" style="border: none; margin-bottom: 6px;">
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

        <div style="margin-bottom: 6px;">
            <span class="field-label">OBSERVACIONES</span>
            <div class="line-write-tall"><?= htmlspecialchars($row['observaciones'] ?? '') ?></div>
        </div>
    </div>

    <div>
        <div class="firma-container">
            <div class="firma-linea">NOMBRE Y FIRMA</div>
        </div>
        <div class="codigo-footer">
            FO-SSC-07-DGRMAYs-07 V2.25
        </div>
    </div>
</div>


<!-- ================================================================= -->
<!-- HOJA 2: BITÁCORA DE SEGUIMIENTO                                   -->
<!-- ================================================================= -->
<div class="sheet">
    <div>
        <div class="fw-bold mb-3" style="font-size: 12px;">BITÁCORA DE SEGUIMIENTO</div>

        <div style="border: 1px solid #000; padding: 6px; margin-bottom: 12px;">
            <span style="font-size: 8.5px; font-weight: bold; display: block; margin-bottom: 4px;">LESIONADOS ENVIADOS A HOSPITALES DE RED (SEGUROS)</span>

            <table class="table-oficial" style="margin-bottom: 0;">
                <tr>
                    <td colspan="4">
                        <span class="field-label">NOMBRE DE LESIONADO</span>
                        <div class="field-value"><?= htmlspecialchars($lesionado_completo) ?></div>
                    </td>
                    <td colspan="3" class="text-center bg-light" style="font-weight: bold; vertical-align: middle; font-size: 10px;">
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
                <tr class="text-center" style="font-size: 10.5px;">
                    <td><?= htmlspecialchars($les_nombres) ?></td>
                    <td><?= htmlspecialchars($les_ap_pat) ?></td>
                    <td><?= htmlspecialchars($les_ap_mat) ?></td>
                    <td><?= htmlspecialchars($row['fecha_de_siniestro']) ?></td>
                    <td><b><?= (stripos($row['causa_resumido'] ?? '', 'GMA') !== false || stripos($row['causa_resumido'] ?? '', 'G.M.A.') !== false)?'X':'' ?></b></td>
                    <td><b><?= (stripos($row['causa_resumido'] ?? '', 'AP') !== false || stripos($row['causa_resumido'] ?? '', 'A.P.') !== false)?'X':'' ?></b></td>
                    <td><b><?= (stripos($row['causa_resumido'] ?? '', 'OTROS') !== false)?'X':'' ?></b></td>
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

        <div style="border: 1px solid #000; padding: 6px; margin-bottom: 12px;">
            <span style="font-size: 8.5px; font-weight: bold; display: block; margin-bottom: 4px;">NOMBRE DE LA/EL FUNCIONARIA(O) DE CABINA QUE TOMA EL REPORTE</span>
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

        <div style="margin-bottom: 10px;">
            <span class="field-label" style="font-size: 8.5px;">ACTIVIDADES DESARROLLADAS POR EL FUNCIONARIO DE CABINA DE RADIO EN TURNO</span>
            <div style="border: 1px solid #000; min-height: 180px; padding: 8px; font-weight: bold; background: #fff; font-size: 11px;">
                <?= nl2br(htmlspecialchars($row['actividades_cabina'] ?? '')) ?>
            </div>
        </div>

        <div>
            <span class="field-label" style="font-size: 8.5px;">CONCLUSIONES</span>
            <div style="border: 1px solid #000; min-height: 130px; padding: 8px; font-weight: bold; background: #fff; font-size: 11px;">
                <?= nl2br(htmlspecialchars($row['conclusiones'] ?? '')) ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
