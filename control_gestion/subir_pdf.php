<?php
header('Content-Type: application/json');
$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file']) && isset($_POST['id_registro'])) {
    $id_registro = (int)$_POST['id_registro'];
    $id_usuario = 1; // Admin por defecto o $_SESSION['id_usuario']
    
    // Crear directorio de almacenamiento seguro en Ubuntu si no existe
    $dir_destino = "/var/www/html/uploads/pdfs/";
    if (!file_exists($dir_destino)) {
        mkdir($dir_destino, 0755, true);
    }

    $nombre_original = $_FILES['pdf_file']['name'];
    $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
    
    if (strtolower($extension) !== 'pdf') {
        die(json_encode(["status" => "error", "message" => "Solo se permiten archivos formato PDF."]));
    }

    // Renombrar el archivo con el folio para evitar colisiones y caracteres raros
    $nuevo_nombre = "FOLIO_" . $id_registro . "_" . time() . ".pdf";
    $ruta_final = $dir_destino . $nuevo_nombre;

    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $ruta_final)) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            $stmt = $pdo->prepare("INSERT INTO soporte_pdf (id_registro, nombre_archivo, ruta_archivo, subido_por) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_registro, $nombre_original, $nuevo_nombre, $id_usuario]);
            
            echo json_encode(["status" => "success", "message" => "Archivo PDF anexado correctamente al folio $id_registro."]);
        } catch (\PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error en BD: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No se pudo mover el archivo al directorio de Ubuntu."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Parámetros inválidos."]);
}
