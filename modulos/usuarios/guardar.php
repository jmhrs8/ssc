<?php
session_start();
require_once "../../config/conexion.php";

// SEGURIDAD: Solo el admin puede procesar estos datos
if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'admin') {
    die("Acceso denegado");
}

if ($_POST) {
    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre'];
    $usuario = $_POST['usuario'];
    $nivel = $_POST['nivel'];
    $modulo = $_POST['modulo_asignado'];
    $password = $_POST['password'];

    try {
        if ($id) {
            // --- MODO EDICIÓN ---
            if (!empty($password)) {
                // Si el admin escribió una nueva contraseña, la ciframos y actualizamos todo
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nombre=?, usuario=?, password=?, nivel=?, modulo_asignado=? WHERE id=?";
                $params = [$nombre, $usuario, $hash, $nivel, $modulo, $id];
            } else {
                // Si la contraseña se dejó en blanco, no la tocamos
                $sql = "UPDATE usuarios SET nombre=?, usuario=?, nivel=?, modulo_asignado=? WHERE id=?";
                $params = [$nombre, $usuario, $nivel, $modulo, $id];
            }
        } else {
            // --- MODO NUEVO REGISTRO ---
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nombre, usuario, password, nivel, modulo_asignado) VALUES (?, ?, ?, ?, ?)";
            $params = [$nombre, $usuario, $hash, $nivel, $modulo];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header("Location: index.php?msg=success");
    } catch (PDOException $e) {
        die("Error al guardar: " . $e->getMessage());
    }
}
