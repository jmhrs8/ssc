<?php
$host = '127.0.0.1';
$db   = 'ssc_control_gestion';
$user = 'root';
$pass = 'jmhl2474';

// Definición de la ruta absoluta donde se almacenan los folios/soportes
$carpeta_uploads = "/var/www/html/modulos/control_gestion/uploads/";

// OPCIÓN A: Solicitud directa enviando el nombre de archivo vía GET
if (isset($_GET['archivo']) && !empty($_GET['archivo'])) {
    $archivo = basename($_GET['archivo']); // Previene ataques de Traversal Path
    $ruta_completa = $carpeta_uploads . $archivo;

    if (file_exists($ruta_completa) && is_file($ruta_completa)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $archivo . '"');
        header('Content-Length: ' . filesize($ruta_completa));
        readfile($ruta_completa);
        exit;
    }
}

// OPCIÓN B: Solicitud enviando únicamente el ID del registro
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Consulta sobre la tabla de correspondencia
        $stmt = $pdo->prepare("SELECT pdf_soporte, pdf_conclusion, numero_oficio FROM correspondencia WHERE id_registro = ? LIMIT 1");
        $stmt->execute([$id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reg) {
            // Se prioriza el PDF de conclusión sobre el de soporte si está disponible
            $nombre_archivo = !empty($reg['pdf_conclusion']) ? $reg['pdf_conclusion'] : $reg['pdf_soporte'];

            if (!empty($nombre_archivo)) {
                $ruta_completa = $carpeta_uploads . $nombre_archivo;

                if (file_exists($ruta_completa) && is_file($ruta_completa)) {
                    $nombre_descarga = preg_replace('/[^A-Za-z0-9_\-]/', '_', $reg['numero_oficio']) . '.pdf';
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename="' . $nombre_descarga . '"');
                    header('Content-Length: ' . filesize($ruta_completa));
                    readfile($ruta_completa);
                    exit;
                }
            }
        }
    } catch (PDOException $e) {
        // Excepción PDO silenciada para salida HTML limpia
    }
}

// Salida por omisión si no existe soporte o parámetro
http_response_code(404);
echo "<div style='font-family: sans-serif; text-align: center; padding: 40px;'>";
echo "<h3 style='color: #861532;'>El folio no cuenta con un documento PDF de soporte digitalizado o el archivo fue removido.</h3>";
echo "</div>";
