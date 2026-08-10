<?php
require_once "../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id = $_POST['id'] ?? null;

        $datos = [
            'poliza'                => mb_strtoupper(trim($_POST['poliza'] ?? '')),
            'tipo_bien'             => mb_strtoupper(trim($_POST['tipo_bien'] ?? '')),
            'tipo_siniestro'        => mb_strtoupper(trim($_POST['tipo_siniestro'] ?? '')),
            'anio_vigencia'         => mb_strtoupper(trim($_POST['anio_vigencia'] ?? '')),
            'fecha_siniestro'       => !empty($_POST['fecha_siniestro']) ? $_POST['fecha_siniestro'] : null,
            'no_siniestro'          => mb_strtoupper(trim($_POST['no_siniestro'] ?? '')),
            'despacho'              => mb_strtoupper(trim($_POST['despacho'] ?? '')),
            'aseguradora'           => mb_strtoupper(trim($_POST['aseguradora'] ?? '')),
            'estatus'               => mb_strtoupper(trim($_POST['estatus'] ?? '')),
            'no_expediente'         => mb_strtoupper(trim($_POST['no_expediente'] ?? '')),
            'tipo_dano_reclamacion' => mb_strtoupper(trim($_POST['tipo_dano_reclamacion'] ?? '')),
            'reclamo'               => mb_strtoupper(trim($_POST['reclamo'] ?? '')),
            'marca'                 => mb_strtoupper(trim($_POST['marca'] ?? '')),
            'modelo'                => mb_strtoupper(trim($_POST['modelo'] ?? '')),
            'serie_matricula'       => mb_strtoupper(trim($_POST['serie_matricula'] ?? '')),
            'estatus_tramite'       => mb_strtoupper(trim($_POST['estatus_tramite'] ?? '')),
            'observaciones'         => mb_strtoupper(trim($_POST['observaciones'] ?? '')),
            'importe_convenio'      => floatval($_POST['importe_convenio'] ?? 0),
            'tipo_pago'             => mb_strtoupper(trim($_POST['tipo_pago'] ?? '')),
            'comprobante_pago'      => mb_strtoupper(trim($_POST['comprobante_pago'] ?? ''))
        ];

        if ($id) {
            $sql = "UPDATE inventario_radio SET 
                poliza=:poliza, tipo_bien=:tipo_bien, tipo_siniestro=:tipo_siniestro, anio_vigencia=:anio_vigencia, 
                fecha_siniestro=:fecha_siniestro, no_siniestro=:no_siniestro, despacho=:despacho, aseguradora=:aseguradora, 
                estatus=:estatus, no_expediente=:no_expediente, tipo_dano_reclamacion=:tipo_dano_reclamacion, reclamo=:reclamo, 
                marca=:marca, modelo=:modelo, serie_matricula=:serie_matricula, estatus_tramite=:estatus_tramite, 
                observaciones=:observaciones, importe_convenio=:importe_convenio, tipo_pago=:tipo_pago, comprobante_pago=:comprobante_pago 
                WHERE id=:id";
            $datos['id'] = $id;
        } else {
            $sql = "INSERT INTO inventario_radio (
                poliza, tipo_bien, tipo_siniestro, anio_vigencia, fecha_siniestro, no_siniestro, despacho, aseguradora, 
                estatus, no_expediente, tipo_dano_reclamacion, reclamo, marca, modelo, serie_matricula, estatus_tramite, 
                observaciones, importe_convenio, tipo_pago, comprobante_pago
            ) VALUES (
                :poliza, :tipo_bien, :tipo_siniestro, :anio_vigencia, :fecha_siniestro, :no_siniestro, :despacho, :aseguradora, 
                :estatus, :no_expediente, :tipo_dano_reclamacion, :reclamo, :marca, :modelo, :serie_matricula, :estatus_tramite, 
                :observaciones, :importe_convenio, :tipo_pago, :comprobante_pago
            )";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($datos);
        header("Location: index.php?status=success");
    } catch (PDOException $e) { die("ERROR DB: " . $e->getMessage()); }
}
