<?php
require_once __DIR__ . '/../../config/conexion.php';

$stmt = $pdo->query("SELECT * FROM configuracion_reportes_correo WHERE id = 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$es_prueba = isset($_GET['prueba']) && $_GET['prueba'] == '1';

if (!$config) {
    exit("No existe configuración de correos.");
}

if (!$es_prueba && $config['activo'] == 0) {
    exit("El sistema de envío automático está desactivado.");
}

$frecuencia = $config['frecuencia'];
$ultimo_envio = $config['ultimo_envio'];
$debe_enviar = false;
$hoy = new DateTime();

if ($es_prueba) {
    $debe_enviar = true;
} else {
    if (!$ultimo_envio) {
        $debe_enviar = true;
    } else {
        $fecha_ultimo = new DateTime($ultimo_envio);
        $diferencia = $hoy->diff($fecha_ultimo)->days;

        if ($frecuencia == 'semanal' && $diferencia >= 7) {
            $debe_enviar = true;
        } elseif ($frecuencia == 'mensual' && $diferencia >= 30) {
            $debe_enviar = true;
        }
    }
}

if ($debe_enviar) {
    // Las 4 ligas exactas solicitadas y separadas correctamente
    $url_personal               = "http://10.13.14.16/modulos/reportes_generales/generar_pdf_personal.php";
    $url_armamento              = "http://10.13.14.16/modulos/reportes_generales/generar_pdf_armamento.php";
    $url_siniestros             = "http://10.13.14.16/modulos/reportes_generales/generar_pdf_siniestros.php";
    $url_siniestros_personal    = "http://10.13.14.16/modulos/reportes_generales/generar_pdf_siniestros_personal.php";

    $destinatarios = explode(',', $config['destinatarios']);
    $asunto = "LIGAS DE ACCESO A INFORMES GERENCIALES - SSC (" . ucfirst($frecuencia) . ")";

    $cuerpo_html = '
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"></head>
    <body style="font-family: Arial, sans-serif; padding: 20px; color: #333;">
        <p>Estimado equipo de Alta Dirección,</p>
        <p>A continuación se enlistan los enlaces directos para consultar e imprimir los informes actualizados:</p>

        <p style="line-height: 2;">
            - <a href="' . $url_personal . '" target="_blank">Informe de Padrón de Personal</a><br>
            - <a href="' . $url_armamento . '" target="_blank">Informe de Armamento</a><br>
            - <a href="' . $url_siniestros . '" target="_blank">Informe de Siniestros Vehiculares</a><br>
            - <a href="' . $url_siniestros_personal . '" target="_blank">Informe de Personal Siniestrado</a>
        </p>

        <p style="margin-top: 20px; font-size: 11px; color: #666;">Sistema Integral de Aseguramiento SSC</p>
    </body>
    </html>';

    $headers = "From: Sistema de Aseguramiento SSC <sistema@ssc.gob.mx>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    foreach ($destinatarios as $correo) {
        $email_destino = trim($correo);
        if (!empty($email_destino)) {
            @mail($email_destino, $asunto, $cuerpo_html, $headers);
        }
    }

    if (!$es_prueba) {
        $stmt_up = $pdo->prepare("UPDATE configuracion_reportes_correo SET ultimo_envio = NOW() WHERE id = 1");
        $stmt_up->execute();
    }

    echo "<h3 style='font-family:Arial; color:green;'>¡Ligas enviadas con éxito a: " . htmlspecialchars($config['destinatarios']) . " !</h3>";
    echo "<br><a href='configurar.php' style='font-family:Arial;'>Regresar a Configuración</a>";

} else {
    echo "Aún no se cumple el periodo (" . $frecuencia . ") establecido para el próximo envío automático.";
}
