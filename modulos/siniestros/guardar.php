<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

    // Recuperar textos y campos del formulario
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

    // Estatus y checks
    $propio = $_POST['propio'] ?? 'NO';
    $arrendado = $_POST['arrendado'] ?? 'NO';
    $declaracion_universal = $_POST['declaracion_universal'] ?? 'NO';
    $pase_medicos = $_POST['pase_medicos'] ?? 'NO';
    $pase_taller = $_POST['pase_taller'] ?? 'NO';
    $graficas = $_POST['graficas'] ?? 'NO';
    $cuadernillo = $_POST['cuadernillo'] ?? 'NO';
    $visto_bueno = $_POST['visto_bueno'] ?? 'NO';

    // Fechas adicionales
    $fecha_visto_bueno = !empty($_POST['fecha_visto_bueno']) ? $_POST['fecha_visto_bueno'] : null;
    $fecha_vb_taller = !empty($_POST['fecha_vb_taller']) ? $_POST['fecha_vb_taller'] : null;
    $fecha_oficio_recibido = !empty($_POST['fecha_oficio_recibido']) ? $_POST['fecha_oficio_recibido'] : null;

    // --- GESTIÓN DE ARCHIVOS MÚLTIPLES ---
    $dir_subida = 'uploads/';
    if (!file_exists($dir_subida)) {
        mkdir($dir_subida, 0777, true);
    }

    function procesar_archivos_multiples($file_input, $prefijo, $dir_subida, $actual_json, $fotos_a_eliminar = []) {
        $rutas = [];
        
        // 1. Recuperar rutas anteriores si existen
        if (!empty($actual_json)) {
            $dec = json_decode($actual_json, true);
            if (is_array($dec)) {
                $rutas = $dec;
            } else {
                $rutas = [$actual_json];
            }
        }

        // 2. Procesar eliminación de fotos marcadas
        if (!empty($fotos_a_eliminar) && is_array($fotos_a_eliminar)) {
            foreach ($rutas as $index => $ruta) {
                if (in_array($ruta, $fotos_a_eliminar)) {
                    if (file_exists($ruta)) {
                        @unlink($ruta);
                    }
                    unset($rutas[$index]);
                }
            }
            $rutas = array_values($rutas); // Reindexar
        }

        // 3. Subir nuevas imágenes (Soporte nativo para múltiples archivos de PHP)
        if (isset($file_input['name']) && is_array($file_input['name'])) {
            $count = count($file_input['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($file_input['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $file_input['tmp_name'][$i];
                    $ext = pathinfo($file_input['name'][$i], PATHINFO_EXTENSION);
                    $nombre_archivo = $prefijo . '_' . uniqid() . '_' . $i . '.' . $ext;
                    $destino = $dir_subida . $nombre_archivo;

                    if (move_uploaded_file($tmp_name, $destino)) {
                        $rutas[] = $destino;
                    }
                }
            }
        }

        $rutas = array_values(array_filter($rutas));
        return !empty($rutas) ? json_encode($rutas) : '';
    }

    $eliminar_unidad = $_POST['eliminar_foto_unidad'] ?? [];
    $eliminar_vehiculo = $_POST['eliminar_foto_vehiculo'] ?? [];

    $actual_unidad = $_POST['foto_unidad_actual'] ?? '';
    $ruta_foto_unidad = procesar_archivos_multiples($_FILES['foto_unidad'] ?? [], 'unidad', $dir_subida, $actual_unidad, $eliminar_unidad);

    $actual_vehiculo = $_POST['foto_vehiculo_actual'] ?? '';
    $ruta_foto_vehiculo = procesar_archivos_multiples($_FILES['foto_vehiculo'] ?? [], 'tercero', $dir_subida, $actual_vehiculo, $eliminar_vehiculo);

    $db = isset($conexion) ? $conexion : $pdo;

    if ($id) {
        $sql = "UPDATE siniestros SET
                    mes=?, folio=?, fecha_reporte=?, hora_reporte=?, fecha=?, hora=?,
                    marca=?, modelo=?, tipo=?, economico_placas=?, no_inventario=?, no_serie=?,
                    calles=?, colonia=?, alcaldia=?, adscripcion=?, nombre_elemento=?, cond_no_empleado=?,
                    cond_licencia=?, vehiculo_3ro=?, placas_3ro=?, seguro=?, danos=?, aseguradora=?,
                    no_siniestro=?, taller_asignado=?, taller_ingreso=?, hospital=?, carp_investigacion=?,
                    lesionados=?, propio=?, arrendado=?, declaracion_universal=?, pase_medicos=?,
                    pase_taller=?, graficas=?, cuadernillo=?, visto_bueno=?, fecha_visto_bueno=?,
                    fecha_vb_taller=?, fecha_oficio_recibido=?, numero_expediente=?, observaciones=?,
                    observaciones_generales=?, foto_unidad=?, foto_vehiculo=?
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
            $observaciones_generales, $ruta_foto_unidad, $ruta_foto_vehiculo, $id
        ]);
    } else {
        $sql = "INSERT INTO siniestros (
                    mes, folio, fecha_reporte, hora_reporte, fecha, hora,
                    marca, modelo, tipo, economico_placas, no_inventario, no_serie,
                    calles, colonia, alcaldia, adscripcion, nombre_elemento, cond_no_empleado,
                    cond_licencia, vehiculo_3ro, placas_3ro, seguro, danos, aseguradora,
                    no_siniestro, taller_asignado, taller_ingreso, hospital, carp_investigacion,
                    lesionados, propio, arrendado, declaracion_universal, pase_medicos,
                    pase_taller, graficas, cuadernillo, visto_bueno, fecha_visto_bueno,
                    fecha_vb_taller, fecha_oficio_recibido, numero_expediente, observaciones,
                    observaciones_generales, foto_unidad, foto_vehiculo
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

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
            $observaciones_generales, $ruta_foto_unidad, $ruta_foto_vehiculo
        ]);
    }

    header("Location: index.php?status=success");
    exit;
}
