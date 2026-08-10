<?php
require_once "../../config/conexion.php";

// 1. ELIMINAR REGISTRO (GET)
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    try {
        $stmt = $pdo->prepare("DELETE FROM personal WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?status=deleted");
        exit;
    } catch (PDOException $e) {
        die("ERROR AL ELIMINAR: " . $e->getMessage());
    }
}

// 2. VACIAR TABLA COMPLETA (GET)
if (isset($_GET['cmd']) && $_GET['cmd'] === 'truncate') {
    try {
        $pdo->exec("TRUNCATE TABLE personal");
        header("Location: index.php?status=truncated");
        exit;
    } catch (PDOException $e) {
        die("ERROR AL VACIAR: " . $e->getMessage());
    }
}

// 3. GUARDAR O EDITAR (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = $_POST['id'] ?? null;

    // Recolectar datos y convertirlos a mayúsculas (excepto fechas)
    $apellido_paterno      = mb_strtoupper(trim($_POST['apellido_paterno'] ?? ''));
    $apellido_materno      = mb_strtoupper(trim($_POST['apellido_materno'] ?? ''));
    $nombre                = mb_strtoupper(trim($_POST['nombre'] ?? ''));
    $rfc                   = mb_strtoupper(trim($_POST['rfc'] ?? ''));
    $area_adscripcion      = mb_strtoupper(trim($_POST['area_adscripcion'] ?? ''));
    $puesto                = mb_strtoupper(trim($_POST['puesto'] ?? ''));
    $descripcion_via_publica = mb_strtoupper(trim($_POST['descripcion_via_publica'] ?? ''));
    $tipo_contratacion     = $_POST['tipo_contratacion'] ?? '';
    $fecha_alta            = !empty($_POST['fecha_alta']) ? $_POST['fecha_alta'] : null;
    $quincena              = mb_strtoupper(trim($_POST['quincena'] ?? ''));

    try {
        if ($accion === 'guardar') {
            $sql = "INSERT INTO personal (
                        apellido_paterno, apellido_materno, nombre, rfc, 
                        area_adscripcion, puesto, descripcion_via_publica, 
                        tipo_contratacion, fecha_alta, quincena
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $apellido_paterno, $apellido_materno, $nombre, $rfc,
                $area_adscripcion, $puesto, $descripcion_via_publica,
                $tipo_contratacion, $fecha_alta, $quincena
            ]);
            header("Location: index.php?status=created");
            exit;

        } elseif ($accion === 'editar' && $id) {
            $sql = "UPDATE personal SET 
                        apellido_paterno = ?, 
                        apellido_materno = ?, 
                        nombre = ?, 
                        rfc = ?, 
                        area_adscripcion = ?, 
                        puesto = ?, 
                        descripcion_via_publica = ?, 
                        tipo_contratacion = ?, 
                        fecha_alta = ?, 
                        quincena = ? 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $apellido_paterno, $apellido_materno, $nombre, $rfc,
                $area_adscripcion, $puesto, $descripcion_via_publica,
                $tipo_contratacion, $fecha_alta, $quincena,
                $id
            ]);
            header("Location: index.php?status=updated");
            exit;
        }
    } catch (PDOException $e) {
        die("ERROR EN LA BASE DE DATOS: " . $e->getMessage());
    }
}

// Si llega aquí sin entrar en ninguna condición, regresar al index
header("Location: index.php");
exit;
