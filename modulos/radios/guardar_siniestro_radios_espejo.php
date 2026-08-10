<?php
session_start();
require_once "../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Asegúrate de que la tabla tenga la columna 'poliza'
        $sql = "REPLACE INTO espejo_siniestros_radios (
            id_radio, fecha_elaboracion, nombre_resguardante, adscripcion, grado, 
            no_empleado, telefono, email, tipo_siniestro, fecha_siniestro, 
            hora_siniestro, lugar_siniestro, narracion, aseguradora, poliza,
            fecha_reporte, hora_reporte, nombre_recibe, no_siniestro_seguro, despacho_ajustador
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['id_radio'],
            $_POST['fecha_elaboracion'],
            mb_strtoupper($_POST['nombre_resguardante']),
            mb_strtoupper($_POST['adscripcion']),
            mb_strtoupper($_POST['grado']),
            $_POST['no_empleado'],
            $_POST['telefono'],
            strtolower($_POST['email']),
            mb_strtoupper($_POST['tipo_siniestro']),
            $_POST['fecha_siniestro'],
            $_POST['hora_siniestro'],
            mb_strtoupper($_POST['lugar_siniestro']),
            mb_strtoupper($_POST['narracion']),
            mb_strtoupper($_POST['aseguradora']),
            mb_strtoupper($_POST['poliza']), // NUEVO
            $_POST['fecha_reporte'],
            $_POST['hora_reporte'],
            mb_strtoupper($_POST['nombre_recibe']),
            mb_strtoupper($_POST['no_siniestro_seguro']),
            mb_strtoupper($_POST['despacho_ajustador'])
        ]);

        header("Location: reporte_pdf_final.php?id=" . $_POST['id_radio']);
        exit();
    } catch (PDOException $e) {
        die("ERROR: " . $e->getMessage());
    }
}
