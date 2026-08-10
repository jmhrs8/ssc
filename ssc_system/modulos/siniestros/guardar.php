<?php
require_once "../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

    // 1. Recuperar los textos normales del formulario
    $folio = $_POST['folio'] ?? '';
    $mes = $_POST['mes'] ?? '';
    $numero_expediente = $_POST['numero_expediente'] ?? '';
    $fecha_reporte = !empty($_POST['fecha_reporte']) ? $_POST['fecha_reporte'] : null;
    $hora_reporte = !empty($_POST['hora_reporte']) ? $_POST['hora_reporte'] : null;
    $fecha = !empty($_POST['fecha']) ? $_POST['fecha'] : null;
    $hora = !empty($_POST['hora']) ? $_POST['hora'] : null;
    $marca = $_POST['marca'] ?? '';
    $modelo = $_POST['modelo'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $economico_placas = $_POST['economico_placas'] ?? '';
    $no_inventario = $_POST['no_inventario'] ?? '';
    $no_serie = $_POST['no_serie'] ?? '';
    $calles = $_POST['calles'] ?? '';
    $colonia = $_POST['colonia'] ?? '';
    $alcaldia = $_POST['alcaldia'] ?? '';
    $adscripcion = $_POST['adscripcion'] ?? '';
    $nombre_elemento = $_POST['nombre_elemento'] ?? '';
    $cond_no_empleado = $_POST['cond_no_empleado'] ?? '';
    $cond_licencia = $_POST['cond_licencia'] ?? '';
    $vehiculo_3ro = $_POST['vehiculo_3ro'] ?? '';
    $placas_3ro = $_POST['placas_3ro'] ?? '';
    $seguro = $_POST['seguro'] ?? '';
    $danos = $_POST['danos'] ?? '';
    $aseguradora = $_POST['aseguradora'] ?? '';
    $no_siniestro = $_POST['no_siniestro'] ?? '';
    $taller_asignado = $_POST['taller_asignado'] ?? '';
    $taller_ingreso = $_POST['taller_ingreso'] ?? '';
    $hospital = $_POST['hospital'] ?? '';
    $carp_investigacion = $_POST['carp_investigacion'] ?? '';
    $lesionados = $_POST['lesionados'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $observaciones_generales = $_POST['observaciones_generales'] ?? '';

    // Valores de Selects/Checkboxes / Estatus por defecto
    $propio = $_POST['propio'] ?? 'NO';
    $arrendado = $_POST['arrendado'] ?? 'NO';
    $declaracion_universal = $_POST['declaracion_universal'] ?? 'NO';
    $pase_medicos = $_POST['pase_medicos'] ?? 'NO';
    $pase_taller = $_POST['pase_taller'] ?? 'NO';
    $graficas = $_POST['graficas'] ?? 'NO';
    $cuadernillo = $_POST['cuadernillo'] ?? 'NO';
    $visto_bueno = $_POST['visto_bueno'] ?? 'NO';
    $estatus = $_POST['estatus'] ?? 'PENDIENTE'; // Consistencia con tu base de datos

    // Fechas adicionales
    $fecha_visto_bueno = !empty($_POST['fecha_visto_bueno']) ? $_POST['fecha_visto_bueno'] : null;
    $fecha_vb_taller = !empty($_POST['fecha_vb_taller']) ? $_POST['fecha_vb_taller'] : null;
    $fecha_oficio_recibido = !empty($_POST['fecha_oficio_recibido']) ? $_POST['fecha_oficio_recibido'] : null;

    // --- 2. GESTIÓN Y CARGA DE IMÁGENES ---
    $dir_subida = 'uploads/';
    if (!file_exists($dir_subida)) {
        mkdir($dir_subida, 0777, true);
    }

    // Inicializar rutas con lo que ya venía del formulario (campos ocultos en edición)
    $ruta_foto_unidad = $_POST['foto_unidad_actual'] ?? '';
    $ruta_foto_vehiculo = $_POST['foto_vehiculo_actual'] ?? '';

    // Procesar Foto de la Unidad Oficial
    if (isset($_FILES['foto_unidad']) && $_FILES['foto_unidad']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto_unidad']['name'], PATHINFO_EXTENSION);
        $nombre_archivo_unidad = 'unidad_' . uniqid() . '.' . $ext;
        $destino_unidad = $dir_subida . $nombre_archivo_unidad;

        if (move_uploaded_files_oficiales($_FILES['foto_unidad']['tmp_name'], $destino_unidad)) {
            $ruta_foto_unidad = $destino_unidad;
        }
    }

    // Procesar Foto del Vehículo Tercero
    if (isset($_FILES['foto_vehiculo']) && $_FILES['foto_vehiculo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto_vehiculo']['name'], PATHINFO_EXTENSION);
        $nombre_archivo_vehiculo = 'tercero_' . uniqid() . '.' . $ext;
        $destino_vehiculo = $dir_subida . $nombre_archivo_vehiculo;

        if (move_uploaded_files_oficiales($_FILES['foto_vehiculo']['tmp_name'], $destino_vehiculo)) {
            $ruta_foto_vehiculo = $destino_vehiculo;
        }
    }

    // Usamos $conexion (cambiar a $pdo si en conexion.php explícitamente se llama $pdo)
    $db = isset($conexion) ? $conexion : $pdo;

    // --- 3. PERSISTENCIA EN LA BASE DE DATOS (INSERT / UPDATE) ---
    if ($id) {
        // Modo Edición: Actualizar registro existente
        $sql = "UPDATE siniestros SET
                    mes=?, folio=?, fecha_reporte=?, hora_reporte=?, fecha=?, hora=?,
                    marca=?, modelo=?, tipo=?, economico_placas=?, no_inventario=?, no_serie=?,
                    calles=?, colonia=?, alcaldia=?, adscripcion=?, nombre_elemento=?, cond_no_empleado=?,
                    cond_licencia=?, vehiculo_3ro=?, placas_3ro=?, seguro=?, danos=?, aseguradora=?,
                    no_siniestro=?, taller_asignado=?, taller_ingreso=?, hospital=?, carp_investigacion=?,
                    lesionados=?, propio=?, arrendado=?, declaracion_universal=?, pase_medicos=?,
                    pase_taller=?, graficas=?, cuadernillo=?, visto_bueno=?, fecha_visto_bueno=?,
                    fecha_vb_taller=?, fecha_oficio_recibido=?, numero_expediente=?, observaciones=?,
                    observaciones_generales=?, foto_unidad=?, foto_vehiculo=?, estatus=?
                WHERE id=?";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $mes, $folio, $fecha_reporte, $hora_reporte, $fecha, $hora,
            $marca, $modelo, $tipo, $economico_placas, $no_inventario, $no_serie,
            $calles, $colonia, $alcaldia, $adscripcion, $nombre_elemento, $cond_no_empleado,
            $cond_licencia, $vehiculo_3ro, $placas_3ro, $seguro, $danos, $aseguradora,
            $no_siniestro, $taller_asignado, $taller_ingreso, $hospital, $carp_investigacion,
            $lesionados, $propio, $arrendado, $declaracion_universal, $pase_medicos,
            $pase_taller, $graficas, $cuadernillo, $visto_bueno, $fecha_visto_bueno,
            $fecha_vb_taller, $fecha_oficio_recibido, $numero_expediente, $observaciones,
            $observaciones_generales, $ruta_foto_unidad, $ruta_foto_vehiculo, $estatus, $id
        ]);
    } else {
        // Modo Nuevo: Insertar nuevo siniestro (47 columnas mapeadas uno a uno)
        $sql = "INSERT INTO siniestros (
                    mes, folio, fecha_reporte, hora_reporte, fecha, hora,
                    marca, modelo, tipo, economico_placas, no_inventario, no_serie,
                    calles, colonia, alcaldia, adscripcion, nombre_elemento, cond_no_empleado,
                    cond_licencia, vehiculo_3ro, placas_3ro, seguro, danos, aseguradora,
                    no_siniestro, taller_asignado, taller_ingreso, hospital, carp_investigacion,
                    lesionados, propio, arrendado, declaracion_universal, pase_medicos,
                    pase_taller, graficas, cuadernillo, visto_bueno, fecha_visto_bueno,
                    fecha_vb_taller, fecha_oficio_recibido, numero_expediente, observaciones,
                    observaciones_generales, foto_unidad, foto_vehiculo, estatus
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $mes, $folio, $fecha_reporte, $hora_reporte, $fecha, $hora,
            $marca, $modelo, $tipo, $economico_placas, $no_inventario, $no_serie,
            $calles, $colonia, $alcaldia, $adscripcion, $nombre_elemento, $cond_no_empleado,
            $cond_licencia, $vehiculo_3ro, $placas_3ro, $seguro, $danos, $aseguradora,
            $no_siniestro, $taller_asignado, $taller_ingreso, $hospital, $carp_investigacion,
            $lesionados, $propio, $arrendado, $declaracion_universal, $pase_medicos,
            $pase_taller, $graficas, $cuadernillo, $visto_bueno, $fecha_visto_bueno,
            $fecha_vb_taller, $fecha_oficio_recibido, $numero_expediente, $observaciones,
            $observaciones_generales, $ruta_foto_unidad, $ruta_foto_vehiculo, $estatus
        ]);
    }

    // Redireccionar al index tras guardar con éxito
    header("Location: index.php?status=success");
    exit;
}

// Función auxiliar para asegurar compatibilidad de nombres
function move_uploaded_files_oficiales($tmp, $dest) {
    return move_uploaded_file($tmp, $dest);
}
