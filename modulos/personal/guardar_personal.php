<?php
require_once "../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id = $_POST['id'] ?? null;
        $datos = [
            'paterno' => mb_strtoupper(trim($_POST['apellido_paterno'])),
            'materno' => mb_strtoupper(trim($_POST['apellido_materno'])),
            'nombre'  => mb_strtoupper(trim($_POST['nombre'])),
            'rfc'     => mb_strtoupper(trim($_POST['rfc'])),
            'area'    => mb_strtoupper(trim($_POST['area_adscripcion'])),
            'puesto'  => mb_strtoupper(trim($_POST['puesto'])),
            'desc'    => mb_strtoupper(trim($_POST['descripcion_via_publica'])),
            'tipo'    => $_POST['tipo_contratacion'],
            'fecha'   => !empty($_POST['fecha_alta']) ? $_POST['fecha_alta'] : null,
            'quin'    => mb_strtoupper(trim($_POST['quincena']))
        ];

        if ($id) {
            $sql = "UPDATE personal SET 
                    apellido_paterno=:paterno, apellido_materno=:materno, nombre=:nombre, 
                    rfc=:rfc, area_adscripcion=:area, puesto=:puesto, 
                    descripcion_via_publica=:desc, tipo_contratacion=:tipo, 
                    fecha_alta=:fecha, quincena=:quin WHERE id=:id";
            $datos['id'] = $id;
        } else {
            $sql = "INSERT INTO personal (apellido_paterno, apellido_materno, nombre, rfc, 
                    area_adscripcion, puesto, descripcion_via_publica, tipo_contratacion, 
                    fecha_alta, quincena) VALUES (:paterno, :materno, :nombre, :rfc, 
                    :area, :puesto, :desc, :tipo, :fecha, :quin)";
        }

        $pdo->prepare($sql)->execute($datos);
        header("Location: index.php?status=success");
    } catch (PDOException $e) { die("Error: " . $e->getMessage()); }
}
