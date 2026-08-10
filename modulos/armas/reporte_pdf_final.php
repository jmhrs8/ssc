<?php
require_once "../../config/conexion.php";

$id = $_GET['id'] ?? null;
if (!$id) die("ID NO PROPORCIONADO");

// Consulta que une Inventario (Datos técnicos) y Espejo (Datos de captura)
$stmt = $pdo->prepare("SELECT a.*, e.* FROM inventario_armas a 
                       LEFT JOIN espejo_siniestros_armas e ON a.id = e.id_arma 
                       WHERE a.id = ?");
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$d) die("REGISTRO NO ENCONTRADO");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0.8cm; }
        body { font-family: Arial, sans-serif; font-size: 9.5px; text-transform: uppercase; line-height: 1.1; }
        .t-bold { font-weight: bold; }
        .tabla { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .tabla td { border: 1px solid #000; padding: 4px; vertical-align: middle; }
        .bg-head { background-color: #f2f2f2; font-weight: bold; width: 180px; }
        .header-table td { border: none; }
        .title-sec { background: #000; color: #fff; font-weight: bold; padding: 5px; margin-top: 15px; letter-spacing: 1px; }
        .footer-firma { margin-top: 40px; text-align: center; }
        .linea { border-top: 1px solid #000; width: 220px; margin: 0 auto; margin-bottom: 5px; }
    </style>
</head>
<body onload="window.print();">

    <table class="header-table" style="width: 100%;">
        <tr>
            <td width="50%">
                <div class="t-bold" style="font-size: 14px;">CIUDAD DE MÉXICO</div>
                <div style="font-size: 10px;">CAPITAL DE LA TRANSFORMACIÓN</div>
                <div class="t-bold" style="margin-top:10px; font-size: 12px;">REPORTE DE SINIESTRO 2026</div>
                <div style="margin-top:15px;">
                    <span class="t-bold">TIPO DE BIEN:</span><br>
                    <span style="font-size: 11px;"><?= $d['tipo_bien'] ?></span>
                </div>
            </td>
            <td width="50%" align="right">
                <table class="tabla" style="width: 250px; float: right;">
                    <tr>
                        <td class="bg-head" style="width: 120px;">FECHA ELABORACIÓN:</td>
                        <td align="center"><?= date('d/m/Y') ?></td>
                    </tr>
                    <tr>
                        <td class="bg-head">FECHA SINIESTRO:</td>
                        <td align="center"><?= $d['fecha_siniestro'] ?></td>
                    </tr>
                    <tr>
                        <td class="bg-head">HORA OCURRENCIA:</td>
                        <td align="center"><?= $d['hora_siniestro'] ?></td>
                    </tr>
                </table>
                <div style="clear: both; margin-top: 10px; font-weight: bold; font-size: 11px;">
                    EXPEDIENTE: <?= $d['no_expediente'] ?>
                </div>
            </td>
        </tr>
    </table>

    <div class="title-sec">DATOS DEL RESGUARDANTE DEL BIEN</div>
    <table class="tabla">
        <tr><td class="bg-head">NOMBRE:</td><td><?= $d['nombre_resguardante'] ?></td></tr>
        <tr><td class="bg-head">ADSCRIPCIÓN:</td><td><?= $d['adscripcion'] ?></td></tr>
        <tr><td class="bg-head">GRADO:</td><td><?= $d['grado'] ?></td></tr>
        <tr><td class="bg-head">NO. EMPLEADO:</td><td><?= $d['no_empleado'] ?></td></tr>
        <tr><td class="bg-head">TELÉFONO:</td><td><?= $d['telefono'] ?></td></tr>
        <tr><td class="bg-head">E-MAIL:</td><td style="text-transform: lowercase;"><?= $d['email'] ?></td></tr>
    </table>

    <div class="title-sec">DATOS DEL SINIESTRO</div>
    <table class="tabla">
        <tr><td class="bg-head">TIPO DE SINIESTRO:</td><td><?= $d['tipo_siniestro'] ?></td></tr>
        <tr>
            <td class="bg-head">NARRACIÓN SINIESTRO:</td>
            <td style="height: 90px; vertical-align: top; text-align: justify;"><?= $d['narracion'] ?></td>
        </tr>
        <tr><td class="bg-head">LUGAR DEL SINIESTRO:</td><td><?= $d['lugar_siniestro'] ?></td></tr>
        <tr>
            <td class="bg-head">DESCRIPCIÓN DEL BIEN:</td>
            <td><?= $d['marca'] ?> MODELO <?= $d['modelo'] ?> SERIE: <?= $d['serie_matricula_1'] ?></td>
        </tr>
    </table>

    <div class="title-sec">DATOS REPORTE A LA ASEGURADORA</div>
    <table class="tabla">
        <tr>
            <td class="bg-head">ASEGURADORA:</td>
            <td><?= $d['aseguradora'] ?? 'SEGUROS AGROASEMEX' ?></td>
        </tr>
        <tr>
            <td class="bg-head">PÓLIZA:</td>
            <td><?= $d['poliza'] ?? 'S/D' ?></td>
        </tr>
        <tr>
            <td class="bg-head">NOMBRE DE QUIEN RECIBE:</td>
            <td><?= $d['nombre_recibe'] ?></td>
        </tr>
        <tr>
            <td class="bg-head">NO. DE SINIESTRO ASIGNADO:</td>
            <td><?= $d['no_siniestro_seguro'] ?></td>
        </tr>
        <tr>
            <td class="bg-head">DESPACHO AJUSTADOR:</td>
            <td><?= $d['despacho'] ?></td> </tr>
        <tr>
            <td class="bg-head">FECHA / HORA REPORTE:</td>
            <td><?= $d['fecha_reporte'] ?> / <?= $d['hora_reporte'] ?></td>
        </tr>
    </table>

    <table style="width: 100%; margin-top: 60px;">
        <tr>
            <td align="center" width="50%">
                <div class="linea"></div>
                <div class="t-bold">NOMBRE Y FIRMA DE QUIEN REPORTA</div>
            </td>
            <td align="center" width="50%">
                <div class="linea"></div>
                <div class="t-bold">VoBo J.U.D DE ASEGURAMIENTO DE BIENES</div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin-top: 40px; font-weight: bold; border-top: 1px solid #ccc; padding-top: 10px;">
        2026 "AÑO DE MARGARITA MAZA" / AÑO MUNDIALISTA
    </div>

</body>
</html>
