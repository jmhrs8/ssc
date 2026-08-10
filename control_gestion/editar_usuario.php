<?php
header('Content-Type: application/json');
$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $id = $_POST['id_usuario'] ?? '';
    $nombre = $_POST['nombre_completo'] ?? '';
    $username = $_POST['username'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $rol = $_POST['rol'] ?? 'CAPTURISTA';
    $password = $_POST['password'] ?? '';
    $foto_nombre = null;

    // Lógica de archivo
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
        $foto_nombre = time() . '_' . $_FILES['foto_perfil']['name'];
        move_uploaded_file($_FILES['foto_perfil']['tmp_name'], 'uploads/perfiles/' . $foto_nombre);
    }

    if (!empty($id)) {
        // ==========================================
        // EDICIÓN DE USUARIO EXISTENTE
        // ==========================================
        $fields = ["nombre_completo=?", "username=?", "correo=?", "rol=?"];
        $params = [$nombre, $username, $correo, $rol];

        // Solo actualizar la contraseña si se escribió una nueva
        if (!empty($password)) {
            $fields[] = "password=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        // Solo actualizar foto si se subió un archivo nuevo
        if ($foto_nombre) {
            $fields[] = "foto_perfil=?";
            $params[] = $foto_nombre;
        }

        $params[] = $id;
        $sql = "UPDATE usuarios SET " . implode(", ", $fields) . " WHERE id_usuario=?";
        $pdo->prepare($sql)->execute($params);

    } else {
        // ==========================================
        // CREACIÓN DE NUEVO USUARIO
        // ==========================================
        $pass_final = !empty($password) ? $password : '123456';
        $pass_hash = password_hash($pass_final, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nombre_completo, username, correo, password, rol, foto_perfil) VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$nombre, $username, $correo, $pass_hash, $rol, $foto_nombre]);
    }

    echo json_encode(["status" => "success", "message" => "Guardado correctamente"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
