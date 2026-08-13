<?php
/**
 * acciones.php
 * Maneja las operaciones de base de datos para el módulo de personal.
 */

session_start();
require_once "../../config/conexion.php";

// --- SEGURIDAD: VALIDACIÓN DE SESIÓN ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$nivel_actual = strtoupper(trim($_SESSION['nivel'] ?? ''));
$permiso_personal = $_SESSION['permiso_personal'] ?? 0;
$es_admin_general = ($nivel_actual === 'ADMIN_GENERAL');
$es_solo_lectura = ($nivel_actual === 'LECTURA');
$tiene_permiso_masivo = ($es_admin_general || $permiso_personal == 1);
$usuario_que_elimina = $_SESSION['nombre'] ?? ($_SESSION['usuario'] ?? 'ADMINISTRADOR');


// ==========================================
// 1. PETICIONES POST (GUARDAR, EDITAR, BORRADO MASIVO)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A. ELIMINAR SELECCIONADOS (Borrado Masivo con Respaldo en Bitácora)
    if (isset($_POST['cmd']) && $_POST['cmd'] === 'eliminar_seleccionados') {
        if (!$tiene_permiso_masivo) {
            header("Location: index.php?error=sin_permiso_accion");
            exit();
        }

        $ids_a_eliminar = $_POST['ids'] ?? [];
        if (empty($ids_a_eliminar)) {
            header("Location: index.php?status=error_no_seleccionados");
            exit();
        }

        try {
            $ids_limpios = array_map('intval', $ids_a_eliminar);
            $placeholders = implode(',', array_fill(0, count($ids_limpios), '?'));

            // 1. RESPALDO EN BITÁCORA ANTES DE BORRAR (QUIRÚRGICO)
            $sql_backup = "INSERT INTO personal_eliminados 
                (id_original, rfc, apellido_paterno, apellido_materno, nombre, area_adscripcion, puesto, tipo_contratacion, fecha_eliminacion, eliminado_por)
                SELECT id, rfc, apellido_paterno, apellido_materno, nombre, area_adscripcion, puesto, tipo_contratacion, NOW(), ?
                FROM personal WHERE id IN ($placeholders)";
            
            $stmt_backup = $pdo->prepare($sql_backup);
            // El primer parámetro es el usuario activo, seguido de los IDs a respaldar
            $params_backup = array_merge([$usuario_que_elimina], $ids_limpios);
            $stmt_backup->execute($params_backup);

            // 2. Eliminar de la tabla principal
            $sql_delete = "DELETE FROM personal WHERE id IN ($placeholders)";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->execute($ids_limpios);
            $count = $stmt_delete->rowCount();

            // Limpiar sesiones auxiliares
            unset($_SESSION['rfc_resaltar'], $_SESSION['alerta_duplicados'], $_SESSION['alerta_no_encontrados']);

            header("Location: index.php?status=deleted_masive&count=$count");
            exit();

        } catch (PDOException $e) {
            die("Error al eliminar seleccionados: " . $e->getMessage());
        }
    }

    // B. GUARDAR O EDITAR REGISTRO
    if (isset($_POST['accion']) && !$es_solo_lectura) {
        $accion = $_POST['accion'];
        $id = $_POST['id'] ?? null;

        $apellido_paterno = mb_strtoupper(trim($_POST['apellido_paterno'] ?? ''));
        $apellido_materno = mb_strtoupper(trim($_POST['apellido_materno'] ?? ''));
        $nombre = mb_strtoupper(trim($_POST['nombre'] ?? ''));
        $rfc = mb_strtoupper(trim($_POST['rfc'] ?? ''));
        $area_adscripcion = mb_strtoupper(trim($_POST['area_adscripcion'] ?? ''));
        $puesto = mb_strtoupper(trim($_POST['puesto'] ?? ''));
        $descripcion_via_publica = mb_strtoupper(trim($_POST['descripcion_via_publica'] ?? ''));
        $tipo_contratacion = $_POST['tipo_contratacion'] ?? '';
        $fecha_alta = !empty($_POST['fecha_alta']) ? $_POST['fecha_alta'] : null;
        $quincena = mb_strtoupper(trim($_POST['quincena'] ?? ''));

        try {
            if ($accion === 'guardar') {
                $sql = "INSERT INTO personal (apellido_paterno, apellido_materno, nombre, rfc, area_adscripcion, puesto, descripcion_via_publica, tipo_contratacion, fecha_alta, quincena) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$apellido_paterno, $apellido_materno, $nombre, $rfc, $area_adscripcion, $puesto, $descripcion_via_publica, $tipo_contratacion, $fecha_alta, $quincena]);
                header("Location: index.php?status=created");
                exit();
            } elseif ($accion === 'editar' && $id) {
                $sql = "UPDATE personal SET apellido_paterno = ?, apellido_materno = ?, nombre = ?, rfc = ?, area_adscripcion = ?, puesto = ?, descripcion_via_publica = ?, tipo_contratacion = ?, fecha_alta = ?, quincena = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$apellido_paterno, $apellido_materno, $nombre, $rfc, $area_adscripcion, $puesto, $descripcion_via_publica, $tipo_contratacion, $fecha_alta, $quincena, $id]);
                header("Location: index.php?status=updated");
                exit();
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000' || strpos($e->getMessage(), '1062') !== false) {
                $url = "formulario.php?error=rfc_duplicado";
                if ($id) $url .= "&id=" . $id;
                header("Location: $url");
                exit();
            } else {
                die("Error en base de datos: " . $e->getMessage());
            }
        }
    }
}


// ==========================================
// 2. PETICIONES VÍA GET (ELIMINAR INDIVIDUAL Y TRUNCATE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // A. ELIMINAR UN SOLO REGISTRO (Exclusivo Admin General con Respaldo en Bitácora)
    if (isset($_GET['eliminar'])) {
        if (!$es_admin_general) {
            header("Location: index.php?error=sin_permiso_accion");
            exit();
        }

        $id = (int)$_GET['eliminar'];
        try {
            // 1. RESPALDO EN BITÁCORA ANTES DE BORRAR (QUIRÚRGICO)
            $sql_backup = "INSERT INTO personal_eliminados 
                (id_original, rfc, apellido_paterno, apellido_materno, nombre, area_adscripcion, puesto, tipo_contratacion, fecha_eliminacion, eliminado_por)
                SELECT id, rfc, apellido_paterno, apellido_materno, nombre, area_adscripcion, puesto, tipo_contratacion, NOW(), ?
                FROM personal WHERE id = ?";
            
            $stmt_backup = $pdo->prepare($sql_backup);
            $stmt_backup->execute([$usuario_que_elimina, $id]);

            // 2. Eliminar registro original
            $stmt = $pdo->prepare("DELETE FROM personal WHERE id = ?");
            $stmt->execute([$id]);

            header("Location: index.php?status=deleted");
            exit();
        } catch (PDOException $e) {
            die("Error al eliminar registro: " . $e->getMessage());
        }
    }

    // B. VACIAR TODA LA TABLA / TRUNCATE
    if (isset($_GET['cmd']) && $_GET['cmd'] === 'truncate') {
        if (!$es_admin_general) {
            header("Location: index.php?error=sin_permiso_accion");
            exit();
        }

        try {
            $pdo->exec("TRUNCATE TABLE personal");
            header("Location: index.php?status=truncated");
            exit();
        } catch (PDOException $e) {
            die("Error al vaciar la tabla: " . $e->getMessage());
        }
    }
}

// Redirección por defecto
header("Location: index.php");
exit();
?>
