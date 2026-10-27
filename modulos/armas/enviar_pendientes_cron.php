<?php
require_once "../../config/conexion.php";

$modo_prueba = false;
$correo_prueba = "jmhrs8@gmail.com";

$texto_base = "Estimados todos.<br><br>" .
              "Me permito remitir a ustedes listado de siniestros pendientes y en espera de resolucion, " .
              "solicitando atentamente su apoyo para dar seguimiento puntual a los mismos, " .
              "gestionar las acciones necesarias para su pronta resolucion y, en su caso, " .
              "informar sobre los avances y el estatus actual de cada pendiente.<br><br>" .
              "<b>LISTADO DE SINIESTROS PENDIENTES / EN ESPERA DE RESOLUCION:</b><br>";

$firma_html = "<br><br><hr style='border:none; border-top:1px solid #ccc; width:100%;'>" .
              "<table style='font-family: Arial, sans-serif; font-size: 11px; color: #333; border: none;' border='0' cellspacing='0' cellpadding='0'>" .
              "<tr>" .
              "<td style='padding-right: 15px; vertical-align: top;'>" .
              "<img src='https://www.ssc.cdmx.gob.mx/storage/app/media/logo-ssc.png' width='75' alt='SSC CDMX'>" .
              "</td>" .
              "<td style='vertical-align: top; line-height: 1.4;'>" .
              "<b style='font-size: 12px; color: #000;'>Subdireccion de Riesgos y Aseguramiento</b><br>" .
              "Av. Arcos de Belen 79, Col. Centro, Alc. Cuauhtemoc, C.P. 06070, CDMX.<br>" .
              "Tels. 55 5208 9898 / 55 5242 5100 &nbsp;&nbsp;Ext. 7739<br>" .
              "<a href='https://www.ssc.cdmx.gob.mx/' target='_blank' style='color: #0d6efd; text-decoration: none;'>https://www.ssc.cdmx.gob.mx/</a>" .
              "</td>" .
              "</tr>" .
              "</table>";

$destinatarios_por_aseguradora = [
    'AGROASEMEX' => [
        'david.vazquez@qm-adjusters.com.mx',
        'lvelazquez@agroasemex.gob.mx',
        'kuriostegui@agroasemex.gob.mx'
        'erikassc88@gmail.com'
        'jesus.perez.castro0503@gmail.com'
        'jmhrs8@gmail.com'
        'gersonemm@gmail.com'
    ],
    'AZTECA' => [
        'jose.quiroz@segurosazteca.com.mx',
        'nadia.lopez@segurosazteca.com.mx'
        'erikassc88@gmail.com'
        'jesus.perez.castro0503@gmail.com'
        'jmhrs8@gmail.com'
        'gersonemm@gmail.com'
    ]
];

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Subdireccion de Riesgos y Aseguramiento <notificaciones@ssc-cdmx.gob.mx>\r\n";

$resultados_envio = [];

try {
    foreach ($destinatarios_por_aseguradora as $aseguradora => $correos_oficiales) {
        $stmt = $pdo->prepare("SELECT no_siniestro FROM inventario_armas WHERE aseguradora LIKE ? AND (status_tramite LIKE '%PENDIENTE%' OR status_tramite LIKE '%ESPERA DE RESOLUCION%' OR status_tramite = '' OR status_tramite IS NULL)");
        $stmt->execute(['%' . $aseguradora . '%']);
        $pendientes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($pendientes)) {
            continue;
        }

        $lista_siniestros = "<ol style='margin-top: 5px; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6;'>";
        foreach ($pendientes as $num_siniestro) {
            $lista_siniestros .= "<li><b>" . htmlspecialchars($num_siniestro) . "</b></li>";
        }
        $lista_siniestros .= "</ol>";

        $cuerpo_html = "<html><body style='font-family: Arial, sans-serif; font-size: 12px; color: #1a1a1a;'>" .
                       $texto_base .
                       $lista_siniestros .
                       "<br>Atentamente,<br><b>La Subdireción de Riesgos y Aseguramiento de la SSC</b>" .
                       $firma_html .
                       "</body></html>";

        $asunto = "REPORTE DE SINIESTROS PENDIENTES Y EN ESPERA DE RESOLUCION - " . $aseguradora;

        if ($modo_prueba) {
            $destinatarios_str = $correo_prueba;
            $asunto = "[PRUEBA - " . $aseguradora . "] " . $asunto;
        } else {
            $destinatarios_str = implode(',', $correos_oficiales);
        }

        $enviado = mail($destinatarios_str, $asunto, $cuerpo_html, $headers);

        $resultados_envio[] = [
            'aseguradora' => $aseguradora,
            'enviado_a' => $destinatarios_str,
            'total_registros' => count($pendientes),
            'estatus' => $enviado ? 'ENVIADO CORRECTAMENTE' : 'ERROR EN ENVIO'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'modo_prueba' => $modo_prueba ? 'ACTIVADO (Redirigido a ' . $correo_prueba . ')' : 'DESACTIVADO (Envios reales)',
        'timestamp' => date('Y-m-d H:i:s'),
        'detalle' => $resultados_envio
    ], JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]) . "\n";
}
?>
