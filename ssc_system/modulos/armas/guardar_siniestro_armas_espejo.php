<?php
require_once "../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_arma = $_POST['id_arma'] ?? null;
    
    if (!$id_arma) {
        die("ERROR: ID de arma no recibido.");
    }

    // Recolectamos todos los campos del formulario de siniestro
    $data = [
        'id_arma'             => $id_arma,
        'fecha_elaboracion'   => $_POST['fecha_elaboracion'] ?? null,
        'nombre_resguardante' => mb_strtoupper($_POST['nombre_resguardante'] ?? '', 'UTF-8'),
        'grado'               => mb_strtoupper($_POST['grado'] ?? '', 'UTF-8'),
        'no_empleado'         => mb_strtoupper($_POST['no_empleado'] ?? '', 'UTF-8'),
        'adscripcion'         => mb_strtoupper($_POST['adscripcion'] ?? '', 'UTF-8'),
        'telefono'            => $_POST['telefono'] ?? '',
        'email'               => strtolower($_POST['email'] ?? ''),
        'tipo_siniestro'      => mb_strtoupper($_POST['tipo_siniestro'] ?? '', 'UTF-8'),
        'fecha_siniestro'     => $_POST['fecha_siniestro'] ?? null,
        'hora_siniestro'      => $_POST['hora_siniestro'] ?? null,
        'lugar_siniestro'     => mb_strtoupper($_POST['lugar_siniestro'] ?? '', 'UTF-8'),
        'narracion'           => mb_strtoupper($_POST['narracion'] ?? '', 'UTF-8'),
        'aseguradora'         => mb_strtoupper($_POST['aseguradora'] ?? '', 'UTF-8'),
        'fecha_reporte'       => $_POST['fecha_reporte'] ?? null,
        'hora_reporte'        => $_POST['hora_reporte'] ?? null,
        'no_siniestro_seguro' => mb_strtoupper($_POST['no_siniestro_seguro'] ?? '', 'UTF-8'),
        'despacho_ajustador'  => mb_strtoupper($_POST['despacho_ajustador'] ?? '', 'UTF-8'),
        'nombre_recibe'       => mb_strtoupper($_POST['nombre_recibe'] ?? '', 'UTF-8')
    ];

    try {
        // Preparamos la consulta para insertar o actualizar si ya existe
        $sql = "INSERT INTO espejo_siniestros_armas (
                    id_arma, fecha_elaboracion, nombre_resguardante, grado, no_empleado, 
                    adscripcion, telefono, email, tipo_siniestro, fecha_siniestro, 
                    hora_siniestro, lugar_siniestro, narracion, aseguradora, 
                    fecha_reporte, hora_reporte, no_siniestro_seguro, despacho_ajustador, nombre_recibe
                ) VALUES (
                    :id_arma, :fecha_elaboracion, :nombre_resguardante, :grado, :no_empleado, 
                    :adscripcion, :telefono, :email, :tipo_siniestro, :fecha_siniestro, 
                    :hora_siniestro, :lugar_siniestro, :narracion, :aseguradora, 
                    :fecha_reporte, :hora_reporte, :no_siniestro_seguro, :despacho_ajustador, :nombre_recibe
                ) ON DUPLICATE KEY UPDATE 
                    fecha_elaboracion = VALUES(fecha_elaboracion),
                    nombre_resguardante = VALUES(nombre_resguardante),
                    grado = VALUES(grado),
                    no_empleado = VALUES(no_empleado),
                    adscripcion = VALUES(adscripcion),
                    telefono = VALUES(telefono),
                    email = VALUES(email),
                    tipo_siniestro = VALUES(tipo_siniestro),
                    fecha_siniestro = VALUES(fecha_siniestro),
                    hora_siniestro = VALUES(hora_siniestro),
                    lugar_siniestro = VALUES(lugar_siniestro),
                    narracion = VALUES(narracion),
                    aseguradora = VALUES(aseguradora),
                    fecha_reporte = VALUES(fecha_reporte),
                    hora_reporte = VALUES(hora_reporte),
                    no_siniestro_seguro = VALUES(no_siniestro_seguro),
                    despacho_ajustador = VALUES(despacho_ajustador),
                    nombre_recibe = VALUES(nombre_recibe)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        // --- CAMBIO CLAVE ---
        // En lugar de ir al index, vamos al generador de PDF pasándole el ID
        header("Location: reporte_pdf_final.php?id=" . $id_arma);
        exit;

    } catch (PDOException $e) {
        die("Error al guardar el reporte detallado: " . $e->getMessage());
    }
}
