<?php
// Script para ejecución en segundo plano (Cron o manual)
$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Buscamos oficios PENDIENTES (sin PDF de conclusión) que tengan 0 o menos días de término
    // Y traemos el correo y nombre del usuario asignado
    $stmt = $pdo->query("
        SELECT r.id_registro, r.numero_oficio, r.asunto, r.dias_termino, u.nombre_completo, u.correo 
        FROM registros r
        INNER JOIN usuarios u ON r.id_usuario_asignado = u.id_usuario
        WHERE (r.pdf_conclusion IS NULL OR r.pdf_conclusion = '' OR r.pdf_conclusion = 'null')
          AND r.dias_termino <= 0
    ");
    
    $vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($vencidos)) {
        echo "No hay oficios fuera de tiempo el día de hoy.\n";
        exit;
    }

    foreach ($vencidos as $oficio) {
        if (filter_var($oficio['correo'], FILTER_VALIDATE_EMAIL)) {
            $para = $oficio['correo'];
            $asunto = "⚠️ ALERTA: Oficio Fuera de Tiempo - Folio [ " . $oficio['id_registro'] . " ]";
            
            // Cuerpo del mensaje
            $mensaje = "Estimado(a) " . $oficio['nombre_completo'] . ",\n\n";
            $mensaje .= "Se le informa que el siguiente oficio asignado a su cuenta ha superado el tiempo límite de atención:\n\n";
            $mensaje .= "• Folio ID: " . $oficio['id_registro'] . "\n";
            $mensaje .= "• Número de Oficio: " . $oficio['numero_oficio'] . "\n";
            $mensaje .= "• Asunto: " . $oficio['asunto'] . "\n";
            $mensaje .= "• Días de retraso: " . abs($oficio['dias_termino']) . " día(s)\n\n";
            $mensaje .= "Por favor, proceda a concluir el trámite y subir la evidencia correspondiente al sistema SSC.\n\n";
            $mensaje .= "Atentamente,\nSistema de Control de Gestión SSC.";

            $cabeceras = "From: no-reply@ssc.cdmx.gob.mx\r\n" .
                         "Reply-To: no-reply@ssc.cdmx.gob.mx\r\n" .
                         "X-Mailer: PHP/" . phpversion();

            // Envío nativo usando la función mail de Linux
            if (mail($para, $asunto, $mensaje, $cabeceras)) {
                echo "Correo enviado con éxito a: " . $para . " por el Folio: " . $oficio['id_registro'] . "\n";
            } else {
                echo "Error al enviar correo a: " . $para . "\n";
            }
        }
    }

} catch (PDOException $e) {
    die("Error en el script de notificaciones: " . $e->getMessage());
}
