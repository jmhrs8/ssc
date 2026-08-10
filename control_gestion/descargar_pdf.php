<?php
$host = '127.0.0.1'; 
$db   = 'ssc_control_gestion'; 
$user = 'root'; 
$pass = 'jmhl2474';

// Definimos la ruta absoluta correcta donde se almacenan tus PDFs
$carpeta_uploads = "/var/www/html/modulos/control_gestion/uploads/";

// OPCIÓN A: Si el visor solicita el PDF directamente por el nombre del archivo
if (isset($_GET['archivo']) && !empty($_GET['archivo'])) {
    $archivo = basename($_GET['archivo']); // Evita ataques de salto de directorio
    $ruta_completa = $carpeta_uploads . $archivo;

    if (file_exists($ruta_completa) && is_file($ruta_completa)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $archivo . '"');
        header('Content-Length: ' . filesize($ruta_completa));
        readfile($ruta_completa);
        exit;
    }
}

// OPCIÓN B: Por si alguna función vieja de tu tabla lo sigue buscando por ID de registro
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Buscamos directamente en tu tabla principal de correspondencia
        $stmt = $pdo->prepare("SELECT pdf_soporte, pdf_conclusion, numero_oficio FROM correspondencia WHERE id_registro = ? LIMIT 1");
        $stmt->execute([$id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reg) {
            // Si piden el de conclusión y existe, priorizamos ese, si no el de soporte
            $nombre_archivo = !empty($reg['pdf_conclusion']) ? $reg['pdf_conclusion'] : $reg['pdf_soporte'];
            
            if (!empty($nombre_archivo)) {
                $ruta_completa = $carpeta_uploads . $nombre_archivo;
                
                if (file_exists($ruta_completa)) {
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename="' . $reg['numero_oficio'] . '.pdf"');
                    header('Content-Length: ' . filesize($ruta_completa));
                    readfile($ruta_completa);
                    exit;
                }
            }
        }
    } catch (PDOException $e) {
        // En caso de error de BD no rompemos el flujo visual, mostramos el error abajo
    }
}

// Si no encontró el archivo por ninguna de las dos vías:
echo "<h3>El folio no cuenta con un documento PDF de soporte digitalizado o el archivo fue removido.</h3>";
