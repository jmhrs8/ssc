<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $q = $pdo->query("DESCRIBE siniestros");
        $colsDB = $q->fetchAll(PDO::FETCH_COLUMN);

        $data = [];
        $columns = [];
        $placeholders = [];

        // 2. Mapeo
        $mapeo = [
            'supervisor' => 'supervisor_nombre',
            'taller_asignado' => 'taller_asignado',
            'coord_aseguradora' => 'coord_aseguradora',
            'no_siniestro' => 'no_siniestro',
            'no_siniestro_aseg' => 'no_siniestro_aseg',
            'ajustador_nombre' => 'ajustador_nombre',
            'hora_reporte_aseg' => 'hora_reporte_aseg'
        ];

        // 3. Normalización y CONCATENACIÓN DEL NOMBRE
        $postNormalizado = [];
        
        // --- LOGICA NUEVA: Unir los campos del conductor ---
        $nombre = $_POST['cond_nombre'] ?? '';
        $paterno = $_POST['cond_ap_paterno'] ?? '';
        $materno = $_POST['cond_ap_materno'] ?? '';
        $_POST['cond_nombre_completo'] = trim("$nombre $paterno $materno");
        // ---------------------------------------------------

        foreach ($_POST as $key => $value) {
            $newKey = isset($mapeo[$key]) ? $mapeo[$key] : $key;
            $postNormalizado[$newKey] = is_string($value) ? mb_strtoupper(trim($value), 'UTF-8') : $value;
        }

        // 4. Manejo de Checkboxes
        $checkboxes = [
            'acta_levantada', 'riesgo_danos_materiales', 'riesgo_robo_total',
            'riesgo_resp_civil', 'riesgo_gastos_medicos', 'riesgo_equipo_especial',
            'les_er1', 'les_er2', 'les_er3'
        ];

        foreach ($checkboxes as $chk) {
            if (isset($postNormalizado[$chk])) {
                $postNormalizado[$chk] = ($postNormalizado[$chk] === 'ON' || $postNormalizado[$chk] == 1 || $postNormalizado[$chk] === '1') ? 1 : 0;
            }
        }

        foreach ($postNormalizado as $key => $value) {
            if ($key === 'id' && empty($value)) { continue; }
            if (in_array($key, $colsDB)) {
                $columns[] = $key;
                $placeholders[] = ":$key";
                $data[":$key"] = ($value === '') ? null : $value;
            }
        }

        foreach ($checkboxes as $chk) {
            if (in_array($chk, $colsDB) && !isset($postNormalizado[$chk])) {
                $columns[] = $chk;
                $placeholders[] = ":$chk";
                $data[":$chk"] = 0;
            }
        }

        // 5. Acción
        $action = $_POST['action'] ?? 'insert';
        $id_siniestro = $_POST['id'] ?? '';

        if ($action === 'update' && !empty($id_siniestro)) {
            $updateParts = [];
            foreach ($columns as $col) {
                $updateParts[] = "$col = :$col";
            }
            $sql = "UPDATE siniestros SET " . implode(', ', $updateParts) . " WHERE id = :id_where";
            $data[':id_where'] = $id_siniestro;
        } else {
            $sql = "INSERT INTO siniestros (" . implode(',', $columns) . ")
                    VALUES (" . implode(',', $placeholders) . ")
                    ON DUPLICATE KEY UPDATE " . implode(', ', array_map(fn($c) => "$c = VALUES($c)", $columns));
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        header("Location: generar_pdf.php?folio=" . urlencode($_POST['folio']));
        exit();

    } catch (Exception $e) {
        die("ERROR CRÍTICO AL GUARDAR EN LA TABLA UNIFICADA: " . $e->getMessage());
    }
}
?>
