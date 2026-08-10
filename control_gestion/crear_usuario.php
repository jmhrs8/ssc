<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'ADMIN') {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos de administrador.']);
    exit;
}

$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$username        = isset($_POST['username']) ? trim($_POST['username']) : '';
$password        = isset($_POST['password']) ? trim($_POST['password']) : '';
$nombre_completo = isset($_POST['nombre_completo']) ? trim($_POST['nombre_completo']) : '';
$correo_input    = isset($_POST['correo']) ? trim($_POST['correo']) : '';
$rol_input       = isset($_POST['rol']) ? trim($_POST['rol']) : 'LECTURA';

if (empty($username) || empty($password) || empty($nombre_completo) || empty($correo_input)) {
    echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

try {
    // Traductor de roles corregido sin errores de sintaxis
    $rol_upper = strtoupper($rol_input);
    if ($rol_upper === 'ADMIN' || $rol_upper === 'ADMINISTRADOR') {
        $rol = 'ADMIN';
    } elseif ($rol_upper === 'OPERADOR' || $rol_upper === 'LECTURA_ESCRITURA') {
        $rol = 'LECTURA_ESCRITURA';
    } else {
        $rol = 'LECTURA';
    }

    // Verificar duplicados
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE username = ? AND activo = 1");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'El usuario ya existe.']);
        exit;
    }

    $foto_perfil = 'default_avatar.png'; 

    // Procesamiento de subida de imagen (JPG / PNG)
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['foto_perfil']['tmp_name'];
        $fileName      = $_FILES['foto_perfil']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'avatar_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/uploads/perfiles/';
            
            if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                $foto_perfil = $newFileName;
            }
        }
    }

    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (username, password, nombre_completo, correo, email, rol, foto_perfil, activo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $password_hashed, $nombre_completo, $correo_input, $correo_input, $rol, $foto_perfil]);

    echo json_encode(['status' => 'success', 'message' => '¡Usuario registrado correctamente!']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error interno: ' . $e->getMessage()]);
}
