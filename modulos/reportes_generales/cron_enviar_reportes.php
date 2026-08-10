<?php
// Script automatizado de envío del informe gerencial unificado (3 módulos con diseño de tarjetas y gráficas)
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
    // --- 1. CONSULTAR DATOS Y MÉTRICAS DE LOS 3 MÓDULOS ---
    try {
        // Armamento
        $tot_armas = $pdo->query("SELECT SUM(CAST(armas AS UNSIGNED)) FROM inventario_armas")->fetchColumn() ?: 0;
        $tot_cartuchos = $pdo->query("SELECT SUM(CAST(cartuchos AS UNSIGNED)) FROM inventario_armas")->fetchColumn() ?: 0;
        $monto_convenio = $pdo->query("SELECT SUM(importe_convenio) FROM inventario_armas")->fetchColumn() ?: 0;
        $caninos = $pdo->query("SELECT COUNT(*) FROM inventario_armas WHERE tipo_bien LIKE '%CANINO%'")->fetchColumn() ?: 0;
        $equinos = $pdo->query("SELECT COUNT(*) FROM inventario_armas WHERE tipo_bien LIKE '%EQUINO%'")->fetchColumn() ?: 0;

        // Personal
        $total_personal = $pdo->query("SELECT COUNT(*) FROM personal")->fetchColumn() ?: 0;
        $base = $pdo->query("SELECT COUNT(*) FROM personal WHERE tipo_contratacion = 'BASE'")->fetchColumn() ?: 0;
        $eventual = $pdo->query("SELECT COUNT(*) FROM personal WHERE tipo_contratacion = 'EVENTUAL'")->fetchColumn() ?: 0;

        // Siniestros
        $total_siniestros = $pdo->query("SELECT COUNT(*) FROM siniestros")->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $tot_armas = $tot_cartuchos = $monto_convenio = $caninos = $equinos = $total_personal = $base = $eventual = $total_siniestros = 0;
    }

    // --- 2. CONSTRUIR CORREO ELECTRÓNICO CON ESTILO GERENCIAL Y TARJETAS ---
    $destinatarios = explode(',', $config['destinatarios']);
    $asunto = "PANEL GERENCIAL CONSOLIDADO - SSC (" . ucfirst($frecuencia) . ")";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Sistema de Aseguramiento SSC <sistema@ssc.gob.mx>\r\n";

    $url_tablero = "http://" . $_SERVER['HTTP_HOST'] . "/modulos/reportes_generales/ver_informe_gerencial.php";

    $cuerpo_html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: "Segoe UI", Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; text-transform: uppercase; }
            .container { max-width: 700px; background: #ffffff; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
            .header { background: #1a1a1a; color: #ffffff; padding: 25px; text-align: center; border-bottom: 4px solid #8b0000; }
            .content { padding: 30px; }
            .card-grid { display: flex; justify-content: space-between; margin-bottom: 15px; gap: 12px; }
            .metric-card { background: #f8f9fa; border-top: 4px solid #8b0000; padding: 15px; flex: 1; text-align: center; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
            .metric-value { font-size: 22px; font-weight: 800; color: #1a1a1a; margin-top: 5px; display: block; }
            .metric-label { font-size: 10px; color: #6c757d; font-weight: bold; }
            .btn-graficas { display: block; background: #8b0000; color: #ffffff !important; text-align: center; padding: 14px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 25px; font-size: 13px; }
            .footer { background: #1a1a1a; color: #adb5bd; text-align: center; padding: 15px; font-size: 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2 style="margin:0; font-size: 18px;">SECRETARÍA DE SEGURIDAD CIUDADANA</h2>
                <p style="margin:5px 0 0 0; font-size: 11px; color: #adb5bd;">INFORME GERENCIAL CONSOLIDADO - ' . strtoupper($frecuencia) . '</p>
            </div>
            <div class="content">
                <p style="font-size: 12px; color: #333;">Estimado equipo directivo:</p>
                <p style="font-size: 11px; color: #555;">Se comparte el resumen ejecutivo con los indicadores clave de los tres módulos del sistema:</p>
                
                <div style="margin-top: 20px;">
                    <div class="card-grid">
                        <div class="metric-card">
                            <span class="metric-label">PERSONAL ACTIVO</span>
                            <span class="metric-value">' . number_format($total_personal) . '</span>
                            <small style="font-size:9px; color:#6c757d;">Base: ' . number_format($base) . ' | Ev: ' . number_format($eventual) . '</small>
                        </div>
                        <div class="metric-card" style="border-top-color: #0dcaf0;">
                            <span class="metric-label">VEHÍCULOS SINIESTRADOS</span>
                            <span class="metric-value" style="color: #0dcaf0;">' . number_format($total_siniestros) . '</span>
                        </div>
                    </div>

                    <div class="card-grid">
                        <div class="metric-card" style="border-top-color: #198754;">
                            <span class="metric-label">TOTAL ARMAS</span>
                            <span class="metric-value" style="color: #198754;">' . number_format($tot_armas) . '</span>
                            <small style="font-size:9px; color:#6c757d;">Caninos: ' . $caninos . ' | Equinos: ' . $equinos . '</small>
                        </div>
                        <div class="metric-card" style="border-top-color: #fd7e14;">
                            <span class="metric-label">MONTO CONVENIO ARMAMENTO</span>
                            <span class="metric-value" style="font-size: 15px; color: #fd7e14;">$' . number_format($monto_convenio, 2) . '</span>
                        </div>
                    </div>
                </div>

                <p style="font-size: 11px; color: #555; margin-top: 20px;">Para consultar el tablero gerencial interactivo completo con todas las gráficas estadísticas profesionales de cada módulo, haga clic en el siguiente botón:</p>
                
                <a href="' . $url_tablero . '" class="btn-graficas" target="_blank">VER TABLERO GERENCIAL CON GRÁFICAS EN VIVO</a>
            </div>
            <div class="footer">
                Sistema Integral de Aseguramiento SSC &copy; ' . date('Y') . ' - Envío Automático
            </div>
        </div>
    </body>
    </html>';

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

    echo "<h3 style='font-family:Arial; color:green;'>¡Informe gerencial con diseño de tarjetas y enlace directo al tablero gráfico enviado con éxito a: " . htmlspecialchars($config['destinatarios']) . " !</h3>";
    echo "<br><a href='configurar_correo_reportes.php' style='font-family:Arial;'>Regresar a Configuración</a>";

} else {
    echo "Aún no se cumple el periodo (" . $frecuencia . ") establecido para el próximo envío automático.";
}
