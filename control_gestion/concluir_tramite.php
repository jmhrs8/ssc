// ... [Tu código existente arriba de procesamiento de archivos y UPDATE de conclusión] ...

if ($update_success) {
    $folio_formateado = str_pad($id_registro, 5, "0", STR_PAD_LEFT);
    
    // 1. Buscar los correos de TODOS los usuarios con rol 'ADMIN'
    $stmtAdmins = $pdo->query("SELECT email FROM usuarios WHERE rol = 'ADMIN' AND email IS NOT NULL AND email != ''");
    $admins = $stmtAdmins->fetchAll(PDO::FETCH_COLUMN);
    
    $mail_admin_enviado = false;
    if (count($admins) > 0) {
        // Unimos los correos separados por coma para el envío masivo
        $para_admins = implode(",", $admins);
        
        $asunto_mail = "=?UTF-8?B?".base64_encode("Trámite Concluido - Folio #".$folio_formateado)."?=";
        
        $mensaje = "
        <html>
        <head><title>Expediente Concluido</title></head>
        <body style='font-family: sans-serif; color: #333;'>
            <h2 style='color: #198754;'>SSC - Control de Gestión</h2>
            <p>Se notifica que un expediente en monitoreo ha sido concluido y archivado correctamente:</p>
            <ul>
                <li><strong>ID Folio:</strong> {$folio_formateado}</li>
                <li><strong>Oficio de Salida / Atención:</strong> {$oficio_atencion}</li>
                <li><strong>Personal que validó el cierre:</strong> {$atendio}</li>
                <li><strong>Fecha y Hora de Cierre:</strong> " . date('d/m/Y H:i') . "</li>
            </ul>
            <p>El documento de evidencia digitalizado ya se encuentra disponible para su consulta en el panel histórico.</p>
            <hr>
            <small>Aviso automático enviado a personal de administración.</small>
        </body>
        </html>";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Sistema Control de Gestión <no-reply@ssc.cdmx.gob.mx>" . "\r\n";
        
        $mail_admin_enviado = mail($para_admins, $asunto_mail, $mensaje, $headers);
    }
    
    $msg_mail = $mail_admin_enviado ? " Expediente cerrado. Se notificó a los Administradores por correo." : " Expediente cerrado. Error en pasarela de correos.";

    echo json_encode([
        'status' => 'success',
        'message' => $msg_mail
    ]);
    exit;
}
