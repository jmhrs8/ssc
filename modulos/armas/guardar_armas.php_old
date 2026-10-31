<?php
/**
 * ARCHIVO: guardar_armas.php
 * FUNCIÓN: Procesa el guardado y actualización individual de armamento.
 * CORRECCIÓN: Manejo de errores 1366 (Integer vacío) y sincronización con tabla espejo.
 */
require_once "../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;

    // 1. LISTA DE CAMPOS COMPLETA (Debe coincidir con la estructura de la tabla inventario_armas)
    $campos = [
        'poliza', 'tipo_bien', 'tipo_siniestro', 'anio_vigencia', 'fecha_siniestro_1',
        'no_siniestro', 'despacho', 'aseguradora', 'status_seguro', 'no_expediente',
        'tipo_dano', 'fecha_reclamacion', 'mes_reclamacion', 'marca', 'modelo',
        'serie_matricula_1', 'status_tramite', 'siniestro_detalle', 'importe_convenio', 'tipo_pago',
        'comprobante_pago', 'fecha_acuse', 'folio_sdra', 'of_recibido', 'no_oficio',
        'bienes_diversos', 'candado_manos', 'armas', 'cargador', 'cartuchos',
        'cascos', 'escudos', 'chalecos', 'atendio', 'digitalizado', 'observaciones_grales'
    ];

    // Definimos cuáles campos son estrictamente numéricos para evitar Error 1366
    $camposNumericos = ['candado_manos', 'armas', 'cargador', 'cartuchos', 'cascos', 'escudos', 'chalecos'];

    $valores = [];
    foreach ($campos as $campo) {
        // Obtenemos el valor, si no existe ponemos cadena vacía
        $valorRaw = $_POST[$campo] ?? '';

        if (in_array($campo, $camposNumericos)) {
            // SI EL CAMPO ES NUMÉRICO Y ESTÁ VACÍO O NO ES NÚMERO, ASIGNAMOS 0
            $valores[] = (trim($valorRaw) === '' || !is_numeric($valorRaw)) ? 0 : intval($valorRaw);
        } elseif ($campo == 'importe_convenio') {
            // LIMPIEZA DE MONEDA: Quita comas, signos de pesos, etc.
            $valores[] = filter_var($valorRaw, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
        } elseif ($campo == 'fecha_siniestro_1' || $campo == 'fecha_reclamacion' || $campo == 'fecha_acuse') {
            // MANEJO DE FECHAS VACÍAS: Si está vacío, enviamos NULL para evitar 0000-00-00
            $valores[] = (empty($valorRaw)) ? null : $valorRaw;
        } else {
            // TEXTO EN MAYÚSCULAS Y LIMPIO
            $valores[] = mb_strtoupper(trim($valorRaw), 'UTF-8');
        }
    }

    // 2. DATOS EXCLUSIVOS PARA LA TABLA ESPEJO (Relacionados por id_arma)
    $nombre_resguardante = mb_strtoupper(trim($_POST['nombre_resguardante'] ?? ''), 'UTF-8');
    $lugar_siniestro     = mb_strtoupper(trim($_POST['lugar_siniestro'] ?? ''), 'UTF-8');
    $narracion           = mb_strtoupper(trim($_POST['narracion'] ?? ''), 'UTF-8');

    try {
        $pdo->beginTransaction();

        if (!empty($id)) {
            // ACCIÓN: ACTUALIZAR REGISTRO EXISTENTE
            $set_parts = [];
            foreach ($campos as $c) { $set_parts[] = "$c = ?"; }
            $sql = "UPDATE inventario_armas SET " . implode(', ', $set_parts) . " WHERE id = ?";
            
            $params_update = array_merge($valores, [$id]);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params_update);
            
            $id_final = $id;
            $mensaje = "actualizado";
        } else {
            // ACCIÓN: INSERTAR NUEVO REGISTRO
            $cols = implode(', ', $campos);
            $placeholders = implode(', ', array_fill(0, count($campos), '?'));
            $sql = "INSERT INTO inventario_armas ($cols) VALUES ($placeholders)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valores);
            
            $id_final = $pdo->lastInsertId();
            $mensaje = "guardado";
        }

        // 3. SINCRONIZAR TABLA ESPEJO (Información adicional del siniestro)
        // Usamos ON DUPLICATE KEY UPDATE por si el ID ya existe en la espejo
        $sql_espejo = "INSERT INTO espejo_siniestros_armas (id_arma, nombre_resguardante, lugar_siniestro, narracion)
                       VALUES (?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE
                       nombre_resguardante = VALUES(nombre_resguardante),
                       lugar_siniestro = VALUES(lugar_siniestro),
                       narracion = VALUES(narracion)";
        $stmt_espejo = $pdo->prepare($sql_espejo);
        $stmt_espejo->execute([$id_final, $nombre_resguardante, $lugar_siniestro, $narracion]);

        $pdo->commit();
        header("Location: index.php?status=success&res=" . $mensaje);
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error en guardar_armas.php: " . $e->getMessage());
        // En producción podrías redirigir con un error: header("Location: index.php?status=error");
        die("Error crítico al procesar la solicitud: " . $e->getMessage());
    }
}
