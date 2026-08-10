<?php
header('Content-Type: application/json');
session_start();

if(!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'ADMIN') {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. Privilegios insuficientes."]);
    exit;
}

$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $id_registro         = (int)$_POST['id_registro'];
    $fecha_oficio        = $_POST['fecha_oficio'];
    $fecha_recepcion     = $_POST['fecha_recepcion'];
    $titular             = trim($_POST['titular']);
    $cargo               = trim($_POST['cargo']);
    $id_usuario_asignado = (int)$_POST['id_usuario_asignado'];
    $asunto              = trim($_POST['asunto']);

    $subida_pdf_sql = "";
    $parametros_extra = [];
    
    if (isset($_FILES['pdf_soporte']) && $_FILES['pdf_soporte']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pdf_soporte']['tmp_name'];
        $fileName = $_FILES['pdf_soporte']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension === 'pdf') {
            $oficio_limpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $_POST['numero_oficio']);
            $nuevoNombrePdf = "SOPORTE_MOD_" . $oficio_limpio . "_" . time() . ".pdf";
            $dir_subida = '/var/www/html/uploads/pdfs/';
            
            if (move_uploaded_file($fileTmpPath, $dir_subida . $nuevoNombrePdf)) {
                $subida_pdf_sql = ", pdf_soporte = ?";
                $parametros_extra[] = $nuevoNombrePdf;
            }
        }
    }

    $sql = "UPDATE control_gestion SET 
                fecha_oficio = ?, 
                fecha_recepcion = ?, 
                titular = ?, 
                cargo = ?, 
                id_usuario_asignado = ?, 
                asunto = ? 
                $subida_pdf_sql 
            WHERE id_registro = ?";

    $stmt = $pdo->prepare($sql);
    
    $base_params = [$fecha_oficio, $fecha_recepcion, $titular, $cargo, $id_usuario_asignado, $asunto];
    $parametros_finales = array_merge($base_params, $parametros_extra, [$id_registro]);

    $stmt->execute($parametros_finales);

    echo json_encode(["status" => "success", "message" => "El folio fue modificado y actualizado exitosamente."]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error interno en BD: " . $e->getMessage()]);
}
