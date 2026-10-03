<?php
session_start();
include('config.php');

// 1. Verificación de Seguridad: Cualquier usuario logueado puede procesar
if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibimos los datos básicos del formulario
    $inv = $_POST['n_inventario'];
    $pla = $_POST['n_placa'];
    $tip = $_POST['n_tipo'];
    
    // Capturamos el nombre del usuario de la sesión para saber quién registra
    $usuario_registra = $_SESSION['usuario_nombre'];

    // Capturamos el orden manual enviado desde el formulario
    $orden_manual = isset($_POST['orden_imagenes']) ? json_decode($_POST['orden_imagenes'], true) : null;

    // Procesamiento de fotos
    $fotos_mapeadas = [];
    if (!empty($_FILES['fotos']['name'][0])) {
        // Aseguramos que el directorio de subidas exista
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }

        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            $original_name = $_FILES['fotos']['name'][$key];
            $nuevo_nombre = time() . "_" . $key . "_" . $original_name;

            if (move_uploaded_file($tmp_name, "uploads/" . $nuevo_nombre)) {
                // Guardamos la relación entre nombre original y el guardado en disco
                $fotos_mapeadas[$original_name] = $nuevo_nombre;
            }
        }
    }

    // Organizamos el array final basado en el orden establecido en el formulario
    $fotos_finales = [];
    if ($orden_manual && is_array($orden_manual)) {
        foreach ($orden_manual as $nombre_orig) {
            if (isset($fotos_mapeadas[$nombre_orig])) {
                $fotos_finales[] = $fotos_mapeadas[$nombre_orig];
            }
        }
    } else {
        // Si no hay orden manual, usamos el orden de subida
        $fotos_finales = array_values($fotos_mapeadas);
    }

    $fotos_json = json_encode($fotos_finales);

    // SQL actualizado para incluir la columna 'registrado_por'
    $sql = "INSERT INTO reportes (n_inventario, n_placa, n_tipo, fotos, registrado_por) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Agregamos un quinto parámetro "s" para el nombre del usuario
    $stmt->bind_param("sssss", $inv, $pla, $tip, $fotos_json, $usuario_registra);

    if ($stmt->execute()) {
        // 2. Redirección Inteligente tras guardar según el ROL
        if ($_SESSION['rol'] === 'admin') {
            // El administrador regresa al listado general
            header("Location: index.php?msg=guardado");
        } else {
            // El operador regresa al formulario para capturar otro, con aviso de éxito
            header("Location: formulario.php?msg=guardado_exito");
        }
    } else {
        echo "Error al guardar en la base de datos: " . $conn->error;
    }
}
?>
