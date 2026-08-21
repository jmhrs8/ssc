<?php
require_once "../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id = !empty($_POST['id']) ? $_POST['id'] : null;

        $no_folio = $_POST['no_folio'] ?? '';
        $tipo = $_POST['tipo'] ?? 'OFICIAL';
        $mes_de_reporte = $_POST['mes_de_reporte'] ?? 'ENERO';
        $no_empleado = $_POST['no_empleado'] ?? '';
        $edad = $_POST['edad'] !== '' ? $_POST['edad'] : null;
        $rfc = $_POST['rfc'] ?? '';

        // Datos del Elemento (obtenidos del padrón por RFC)
        $nombre = $_POST['nombre'] ?? '';
        $apellido_paterno = $_POST['apellido_paterno'] ?? '';
        $apellido_materno = $_POST['apellido_materno'] ?? '';

        // Datos del Lesionado (Llenados aparte en el formulario)
        $lesionado_nombre = $_POST['lesionado_nombre'] ?? '';
        $lesionado_ap_paterno = $_POST['lesionado_ap_paterno'] ?? '';
        $lesionado_ap_materno = $_POST['lesionado_ap_materno'] ?? '';

        $sector_upc = $_POST['sector_upc'] ?? '';
        $fecha_de_siniestro = !empty($_POST['fecha_de_siniestro']) ? $_POST['fecha_de_siniestro'] : null;
        $reporte = $_POST['reporte'] ?? '';
        $poliza_seccion = $_POST['poliza_seccion'] ?? '';
        $aseguradora = $_POST['aseguradora'] ?? '';
        $causa_resumido = $_POST['causa_resumido'] ?? '';
        $unidad_vehicular = $_POST['unidad_vehicular'] ?? '';
        $no_economico = $_POST['no_economico'] ?? '';
        $lugar_accidente = $_POST['lugar_accidente'] ?? '';
        $supervisor_riesgos = $_POST['supervisor_riesgos'] ?? '';
        $no_ambulancia = $_POST['no_ambulancia'] ?? '';
        $hospital = $_POST['hospital'] ?? '';
        $requirio_hospitalizacion = $_POST['requirio_hospitalizacion'] ?? 'NO';
        $fecha_ingreso_hospital = !empty($_POST['fecha_ingreso_hospital']) ? $_POST['fecha_ingreso_hospital'] : null;
        $hora_ingreso_hospital = !empty($_POST['hora_ingreso_hospital']) ? $_POST['hora_ingreso_hospital'] : null;
        $diagnostico = $_POST['diagnostico'] ?? '';
        $lesiones = $_POST['lesiones'] ?? '';
        $quien_reporta = $_POST['quien_reporta'] ?? '';
        $quien_recibe = $_POST['quien_recibe'] ?? '';
        $observaciones = $_POST['observaciones'] ?? '';
        $montos_erogados = $_POST['montos_erogados'] !== '' ? $_POST['montos_erogados'] : 0.00;

        // Captura correcta del Área de Adscripción
        $area_adscripcion = $_POST['area_adscripcion'] ?? '';

        // Campos de bitácora y cabina
        $cabina_nombre = $_POST['cabina_nombre'] ?? '';
        $cabina_ap_paterno = $_POST['cabina_ap_paterno'] ?? '';
        $cabina_ap_materno = $_POST['cabina_ap_materno'] ?? '';
        $actividades_cabina = $_POST['actividades_cabina'] ?? '';
        $conclusiones = $_POST['conclusiones'] ?? '';

        // Recolectar fotos existentes que el usuario decidió conservar
        $fotos_finales = isset($_POST['fotos_existentes']) ? $_POST['fotos_existentes'] : [];

        // Directorio de subida de evidencias
        $upload_dir = "../../uploads/siniestros_personal/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Procesar nuevas imágenes subidas
        if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
            foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['fotos']['error'][$key] == UPLOAD_ERR_OK) {
                    $nombre_archivo = time() . "_" . rand(1000, 9999) . "_" . basename($_FILES['fotos']['name'][$key]);
                    $ruta_destino = $upload_dir . $nombre_archivo;

                    if (move_uploaded_file($tmp_name, $ruta_destino)) {
                        $fotos_finales[] = "uploads/siniestros_personal/" . $nombre_archivo;
                    }
                }
            }
        }

        // Convertir el arreglo de fotos en JSON para almacenarlo en la base de datos
        $foto_json = !empty($fotos_finales) ? json_encode($fotos_finales) : null;

        if (empty($id)) {
            // INSERTAR NUEVO REGISTRO
            $sql = "INSERT INTO siniestros_personal (
                no_folio, tipo, mes_de_reporte, no_empleado, edad, rfc, nombre,
                apellido_paterno, apellido_materno, lesionado_nombre, lesionado_ap_paterno, lesionado_ap_materno,
                sector_upc, fecha_de_siniestro, reporte, poliza_seccion, aseguradora, causa_resumido,
                unidad_vehicular, no_economico, lugar_accidente, supervisor_riesgos, no_ambulancia,
                hospital, requirio_hospitalizacion, fecha_ingreso_hospital, hora_ingreso_hospital,
                diagnostico, lesiones, quien_reporta, quien_recibe, observaciones,
                montos_erogados, area_adscripcion, cabina_nombre, cabina_ap_paterno, cabina_ap_materno,
                actividades_cabina, conclusiones, foto
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $no_folio, $tipo, $mes_de_reporte, $no_empleado, $edad, $rfc, $nombre,
                $apellido_paterno, $apellido_materno, $lesionado_nombre, $lesionado_ap_paterno, $lesionado_ap_materno,
                $sector_upc, $fecha_de_siniestro, $reporte, $poliza_seccion, $aseguradora, $causa_resumido,
                $unidad_vehicular, $no_economico, $lugar_accidente, $supervisor_riesgos, $no_ambulancia,
                $hospital, $requirio_hospitalizacion, $fecha_ingreso_hospital, $hora_ingreso_hospital,
                $diagnostico, $lesiones, $quien_reporta, $quien_recibe, $observaciones,
                $montos_erogados, $area_adscripcion, $cabina_nombre, $cabina_ap_paterno, $cabina_ap_materno,
                $actividades_cabina, $conclusiones, $foto_json
            ]);
        } else {
            // ACTUALIZAR REGISTRO EXISTENTE
            $sql = "UPDATE siniestros_personal SET
                tipo=?, mes_de_reporte=?, no_empleado=?, edad=?, rfc=?, nombre=?,
                apellido_paterno=?, apellido_materno=?, lesionado_nombre=?, lesionado_ap_paterno=?, lesionado_ap_materno=?,
                sector_upc=?, fecha_de_siniestro=?, reporte=?, poliza_seccion=?, aseguradora=?,
                causa_resumido=?, unidad_vehicular=?, no_economico=?, lugar_accidente=?, supervisor_riesgos=?,
                no_ambulancia=?, hospital=?, requirio_hospitalizacion=?, fecha_ingreso_hospital=?,
                hora_ingreso_hospital=?, diagnostico=?, lesiones=?, quien_reporta=?, quien_recibe=?,
                observaciones=?, montos_erogados=?, area_adscripcion=?, cabina_nombre=?, cabina_ap_paterno=?, cabina_ap_materno=?,
                actividades_cabina=?, conclusiones=?, foto=?
            WHERE id=?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $tipo, $mes_de_reporte, $no_empleado, $edad, $rfc, $nombre,
                $apellido_paterno, $apellido_materno, $lesionado_nombre, $lesionado_ap_paterno, $lesionado_ap_materno,
                $sector_upc, $fecha_de_siniestro, $reporte, $poliza_seccion, $aseguradora,
                $causa_resumido, $unidad_vehicular, $no_economico, $lugar_accidente, $supervisor_riesgos,
                $no_ambulancia, $hospital, $requirio_hospitalizacion, $fecha_ingreso_hospital,
                $hora_ingreso_hospital, $diagnostico, $lesiones, $quien_reporta, $quien_recibe,
                $observaciones, $montos_erogados, $area_adscripcion, $cabina_nombre, $cabina_ap_paterno, $cabina_ap_materno,
                $actividades_cabina, $conclusiones, $foto_json, $id
            ]);
        }

        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        echo "<div style='font-family: Arial; padding: 20px; color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb;'>";
        echo "<h3>Error al guardar el registro:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<br><a href='index.php' style='padding: 8px 15px; background: #343a40; color: white; text-decoration: none;'>Regresar al Listado</a>";
        echo "</div>";
    }
}
