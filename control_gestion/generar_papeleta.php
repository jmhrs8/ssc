<?php
$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("SELECT cg.*, ca.nombre_area FROM control_gestion cg LEFT JOIN catalogo_areas ca ON cg.id_turnado_por = ca.id_area WHERE cg.id_registro = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}

if (!$r) { die("Folio no encontrado."); }

$fecha_oficio = (!empty($r['fecha_oficio']) && $r['fecha_oficio'] != '0000-00-00') ? date('d/m/Y', strtotime($r['fecha_oficio'])) : '';
$fecha_recepcion = (!empty($r['fecha_recepcion']) && $r['fecha_recepcion'] != '0000-00-00') ? date('d/m/Y', strtotime($r['fecha_recepcion'])) : '';

function imprimirFormatoOficial($data, $fecha_oficio, $fecha_recepcion) {
    $area_nombre = mb_strtoupper(trim($data['nombre_area'] ?? ''), 'UTF-8');
    $id_turnado = $data['id_turnado_por'] ?? '';

    $subdireccion = ($id_turnado == 1 || strpos($area_nombre, 'SUBDIRECCION') !== false || strpos($area_nombre, 'SUBDIRECCIÓN') !== false) ? 'X' : '';
    $aseguramiento = ($id_turnado == 2 || strpos($area_nombre, 'ASEGURAMIENTO') !== false) ? 'X' : '';
    $seguros_vida = ($id_turnado == 3 || strpos($area_nombre, 'SEGUROS DE VIDA') !== false || strpos($area_nombre, 'JUD') !== false) ? 'X' : '';

    $prioridad = isset($data['prioridad']) ? trim(mb_transform_prioridad($data['prioridad'])) : 'NORMAL';
    $p_normal = ($prioridad == 'NORMAL') ? 'X' : '';
    $p_urgente = ($prioridad == 'URGENTE') ? 'X' : '';
    $p_extra = ($prioridad == 'EXTRA-URGENTE' || $prioridad == 'EXTRA URGENTE') ? 'X' : '';

    $accion_raw = mb_strtoupper(trim($data['accion_papeleta'] ?? ''), 'UTF-8');
    $accion_clean = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $accion_raw);

    $a_archivo      = (strpos($accion_clean, 'ARCHIVO') !== false) ? 'X' : '';
    $a_tramite      = (strpos($accion_clean, 'TRAMITE') !== false) ? 'X' : '';
    $a_conocimiento = (strpos($accion_clean, 'CONOCIMIENTO') !== false) ? 'X' : '';
?>
    <div class="bloque-papeleta-completo">
        <!-- LOGO INSTITUCIONAL SUPERIOR -->
        <div class="bloque-logo-superior">
            <img src="logo_ssc.png" alt="Logo Secretaría de Seguridad Ciudadana" class="img-logo-institucional">
        </div>

        <div class="papeleta-container">
            <!-- Cenefa Lateral Izquierda -->
            <div class="cenefa-lateral">
                <div class="cenefa-lineas"></div>
            </div>

            <div class="contenido-form">
                <div class="row-grid header-main">
                    SUBDIRECCIÓN DE RIESGOS Y ASEGURAMIENTO
                </div>

                <div class="row-grid header-sub">
                    <div style="text-transform: uppercase; font-family: 'Arial Black', Arial, sans-serif;">CONTROL DE GESTIÓN 2026</div>
                    <div style="padding-right: 5px; font-family: 'Arial Black', Arial, sans-serif;">FOLIO: <span class="folio-num"><?php echo str_pad($data['id_registro'], 4, '0', STR_PAD_LEFT); ?></span></div>
                </div>

                <!-- Fila de Fechas -->
                <div class="row-grid">
                    <div class="cell label-cell" style="width: 13%;">OFICIO No.:</div>
                    <div class="cell fw-bold cell-oficio-dinamico" style="width: 32%;"><?php echo htmlspecialchars($data['numero_oficio']); ?></div>

                    <div class="cell label-cell text-center" style="width: 10%;">FECHA:</div>
                    <div class="cell text-center fw-bold" style="width: 15%; font-size: 8.5pt;"><?php echo $fecha_oficio; ?></div>

                    <div class="cell label-cell text-center" style="width: 16%; font-size: 6.5pt; padding: 1px;">FECHA DE RECEPCION</div>
                    <div class="cell text-center fw-bold" style="width: 13%; font-size: 8.5pt;"><?php echo $fecha_recepcion; ?></div>
                </div>

                <div class="row-grid">
                    <div class="cell label-cell" style="width: 13%;">REMITE:</div>
                    <div class="cell fw-bold" style="width: 45%; font-size: 8.5pt;"><?php echo htmlspecialchars($data['titular']); ?></div>
                    <div class="cell label-cell" style="width: 10%;">CARGO:</div>
                    <div class="cell fw-bold" style="width: 32%; font-size: 8pt;"><?php echo htmlspecialchars($data['cargo']); ?></div>
                </div>

                <div class="row-grid" style="min-height: 50px; flex: 1.1;">
                    <div class="block-textarea">
                        <div class="block-title">ASUNTO:</div>
                        <div class="block-content">
                            <?php echo htmlspecialchars($data['asunto']); ?>
                        </div>
                    </div>
                </div>

                <!-- Bloque de Checkboxes Estilo Excel -->
                <div class="row-grid" style="min-height: 52px; flex: 1.1;">
                    <!-- Columna 1 -->
                    <div class="col-grid" style="width: 45%;">
                        <div class="sub-row">
                            <div class="cell label-cell justify-left" style="width: 80%;">ASEGURAMIENTO DE BIENES</div>
                            <div class="cell text-center fw-bold casilla-x" style="width: 20%;"><?php echo $aseguramiento; ?></div>
                        </div>
                        <div class="sub-row">
                            <div class="cell label-cell justify-left" style="width: 80%;">JUD. DE SEGUROS DE VIDA</div>
                            <div class="cell text-center fw-bold casilla-x" style="width: 20%;"><?php echo $seguros_vida; ?></div>
                        </div>
                        <div class="sub-row">
                            <div class="cell label-cell justify-left" style="width: 80%;">SUBDIRECCION</div>
                            <div class="cell text-center fw-bold casilla-x" style="width: 20%;"><?php echo $subdireccion; ?></div>
                        </div>
                    </div>

                    <!-- Columna 2 -->
                    <div class="col-grid" style="width: 35%;">
                        <div class="sub-row">
                            <div class="cell label-cell justify-left" style="width: 80%;">PARA ARCHIVO</div>
                            <div class="cell text-center fw-bold casilla-x" style="width: 20%;"><?php echo $a_archivo; ?></div>
                        </div>
                        <div class="sub-row">
                            <div class="cell label-cell justify-left" style="width: 80%;">PARA TRÁMITE</div>
                            <div class="cell text-center fw-bold casilla-x" style="width: 20%;"><?php echo $a_tramite; ?></div>
                        </div>
                        <div class="sub-row">
                            <div class="cell label-cell justify-left" style="width: 80%;">CONOCIMIENTO</div>
                            <div class="cell text-center fw-bold casilla-x" style="width: 20%;"><?php echo $a_conocimiento; ?></div>
                        </div>
                    </div>

                    <!-- Columna 3 -->
                    <div class="col-grid" style="width: 20%;">
                        <div class="sub-row">
                            <div class="cell label-cell justify-left" style="width: 75%;">NORMAL</div>
                            <div class="cell text-center fw-bold casilla-x" style="width: 25%;"><?php echo $p_normal; ?></div>
                        </div>
                        <div class="sub-row">
                            <div class="cell label-cell justify-left" style="width: 75%;">URGENTE</div>
                            <div class="cell text-center fw-bold casilla-x" style="width: 25%;"><?php echo $p_urgente; ?></div>
                        </div>
                        <div class="sub-row">
                            <div class="cell label-cell justify-left" style="width: 75%;">EXTRA-URGENTE</div>
                            <div class="cell text-center fw-bold casilla-x" style="width: 25%;"><?php echo $p_extra; ?></div>
                        </div>
                    </div>
                </div>

                <div class="row-grid" style="min-height: 38px; flex: 0.9;">
                    <div class="block-textarea">
                        <div class="block-title">INSTRUCCIONES</div>
                        <div class="block-empty-print"></div>
                    </div>
                </div>

                <div class="row-grid" style="min-height: 35px; flex: 0.9;">
                    <div class="block-textarea">
                        <div class="block-title">INFORME DEL SEGUIMIENTO</div>
                        <div class="block-empty-print"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}

function mb_transform_prioridad($str) {
    return mb_strtoupper(trim($str), 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Papeleta Oficial - Folio <?=$id?></title>
    <style>
        @page { 
            size: letter portrait; 
            margin: 0; /* Controlamos márgenes con CSS exacto */
        }
        body { 
            font-family: Arial, sans-serif; 
            background: #fff; 
            margin: 0; 
            padding: 0; 
            color: #000; 
            -webkit-print-color-adjust: exact;
        }

        /* Envoltorio principal simétrico para cada copia */
        .bloque-papeleta-completo {
            width: 100%;
            max-width: 730px;
            height: 128mm; /* Altura fija estricta por media hoja */
            margin: 0 auto;
            box-sizing: border-box;
            padding-top: 2mm;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .bloque-logo-superior { 
            width: 100%; 
            margin: 0 auto 2mm auto; 
            text-align: left; 
        }
        .img-logo-institucional { 
            height: 22mm; 
            width: auto; 
            object-fit: contain; 
            display: block; 
        }

        .papeleta-container { 
            width: 100%; 
            height: 98mm; /* Altura estricta e idéntica de la cuadrícula */
            display: flex; 
            border: 2.5px solid #000; 
            box-sizing: border-box; 
            background: #fff; 
        }

        .cenefa-lateral { width: 28px; border-right: 2.5px solid #000; background-color: #f7f7f7; position: relative; overflow: hidden; }
        .cenefa-lineas { position: absolute; top: 0; bottom: 0; left: 0; right: 0; background: linear-gradient(45deg, #7F1D1D 25%, transparent 25%), linear-gradient(-45deg, #7F1D1D 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #14532D 75%), linear-gradient(-45deg, transparent 75%, #14532D 75%); background-size: 10px 10px; opacity: 0.22; }

        .contenido-form { flex: 1; display: flex; flex-direction: column; width: calc(100% - 28px); }
        .row-grid { display: flex; width: 100%; border-bottom: 1px solid #000; min-height: 18px; align-items: stretch; }
        .row-grid:last-child { border-bottom: none; }

        .header-main { background-color: #d9d9d9; font-weight: bold; text-align: center; justify-content: center; font-size: 10pt; padding: 1px 0; font-family: 'Arial Black', Arial, sans-serif; }
        .header-sub { background-color: #e6e6e6; font-weight: bold; display: flex; justify-content: space-between; padding: 1px 6px; font-size: 8.5pt; align-items: center; box-sizing: border-box; }
        .folio-num { font-size: 10.5pt; font-family: 'Arial Black', Arial, sans-serif; margin-left: 4px; color: #000; display: inline-block; }

        .cell { padding: 1px 3px; display: flex; align-items: center; border-right: 1px solid #000; box-sizing: border-box; font-size: 8pt; }
        .cell:last-child { border-right: none; }

        .cell-oficio-dinamico {
            font-size: 7.5pt !important;
            line-height: 1.1;
            word-break: break-all;
            overflow: visible;
        }

        .label-cell { font-weight: bold; background-color: #e6e6e6; font-size: 7pt; }
        .justify-left { justify-content: flex-start; }
        .text-center { justify-content: center; text-align: center; }
        .fw-bold { font-weight: bold; }

        .casilla-x { background-color: #fff; font-size: 9.5pt; font-family: 'Arial Black', Arial, sans-serif; }

        .block-textarea { display: flex; flex-direction: column; width: 100%; padding: 1px 4px; box-sizing: border-box; height: 100%; }
        .block-title { font-weight: bold; font-size: 7pt; margin-bottom: 1px; text-decoration: underline; }
        .block-content { flex: 1; font-size: 8pt; text-align: justify; line-height: 1.1; font-weight: bold; overflow: hidden; }
        .block-empty-print { flex: 1; background-color: #fff; }

        .col-grid { display: flex; flex-direction: column; border-right: 1px solid #000; }
        .col-grid:last-child { border-right: none; }
        .sub-row { display: flex; width: 100%; border-bottom: 1px solid #000; flex: 1; min-height: 15px; }
        .sub-row:last-child { border-bottom: none; }

        /* Barra de navegación en pantalla */
        .no-print-bar { max-width: 730px; margin: 10px auto; display: flex; justify-content: space-between; align-items: center; }
        .btn-action { background: #861532; color: #fff; border: none; padding: 6px 16px; font-weight: bold; cursor: pointer; border-radius: 4px; font-size: 9.5pt; }

        /* Línea divisoria de corte simétrica */
        .tijera-line {
            height: 10mm;
            max-width: 730px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border-top: 1.2px dashed #000;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 2px;
            box-sizing: border-box;
        }

        @media print {
            .no-print-bar { display: none !important; }
            body { padding: 0; margin: 0; }
            .bloque-papeleta-completo {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <a href="index.php" style="color: #666; text-decoration: none; font-weight: bold; font-size: 10pt;">← Regresar</a>
        <button class="btn-action" onclick="window.print()">📥 Imprimir (Coincidencia Exacta Calca)</button>
    </div>

    <!-- PAPELETA 1 (SUPERIOR) -->
    <?php imprimirFormatoOficial($r, $fecha_oficio, $fecha_recepcion); ?>

    <!-- LÍNEA DE CORTE CENTRAL -->
    <div class="tijera-line">
        
    </div>

    <!-- PAPELETA 2 (INFERIOR - IDÉNTICA) -->
    <?php imprimirFormatoOficial($r, $fecha_oficio, $fecha_recepcion); ?>

</body>
</html>
