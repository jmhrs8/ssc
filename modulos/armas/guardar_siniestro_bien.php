<?php
session_start();
require_once "../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Recolección y limpieza de datos (Forzando Mayúsculas)
        $tipo_modulo = $_POST['tipo_modulo'];
        $id_inventario = $_POST['id_inventario'];
        $folio_interno = mb_strtoupper(trim($_POST['folio_siniestro_interno']));
        $fecha_siniestro = $_POST['fecha_siniestro'];
        $hora_siniestro = $_POST['hora_siniestro'];
        
        $nombre = mb_strtoupper(trim($_POST['nombre_resguardante']));
        $grado = mb_strtoupper(trim($_POST['grado_resguardante']));
        $num_empleado = trim($_POST['num_empleado_resguardante']);
        $tel = trim($_POST['tel_resguardante']);
        $email = trim($_POST['email_resguardante']);
        
        $tipo_siniestro = mb_strtoupper($_POST['tipo_siniestro']);
        $lugar = mb_strtoupper(trim($_POST['lugar_siniestro']));
        $narrativa = mb_strtoupper(trim($_POST['narrativa_siniestro']));

        // Insertar en la tabla espejo
        $sql = "INSERT INTO siniestros_bienes (
                    tipo_modulo, id_inventario, folio_siniestro_interno, 
                    fecha_elaboracion, fecha_siniestro, hora_siniestro,
                    nombre_resguardante, grado_resguardante, num_empleado_resguardante,
                    tel_resguardante, email_resguardante, tipo_siniestro,
                    lugar_siniestro, narrativa_siniestro, usuario_registro
                ) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $tipo_modulo,
            $id_inventario,
            $folio_interno,
            $fecha_siniestro,
            $hora_siniestro,
            $nombre,
            $grado,
            $num_empleado,
            $tel,
            $email,
            $tipo_siniestro,
            $lugar,
            $narrativa,
            $_SESSION['user_id'] ?? 0
        ]);

        $last_id = $pdo->lastInsertId();

        // Redirigir al nuevo reporte PDF pasando el ID del registro en la tabla espejo
        header("Location: reporte_bien_pdf.php?id_siniestro=$last_id");
        exit();

    } catch (PDOException $e) {
        die("ERROR AL GUARDAR: " . $e->getMessage());
    }
}
