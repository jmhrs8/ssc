<?php
session_start();
require_once "../../config/conexion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$nivel_actual = strtoupper(trim($_SESSION['nivel'] ?? ''));
$es_admin_general = ($nivel_actual === 'ADMIN_GENERAL');
$es_lectura = ($nivel_actual === 'LECTURA');

$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'consultar_personal_rfc':
        header('Content-Type: application/json');
        $rfc = trim($_GET['rfc'] ?? '');
        
        if (empty($rfc)) {
            echo json_encode(['encontrado' => false]);
            exit();
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM personal WHERE rfc = ? LIMIT 1");
            $stmt->execute([$rfc]);
            $persona = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($persona) {
                // Concatenación completa de nombre y apellidos tal como estaba funcionando
                $nombre_completo = trim(
                    ($persona['nombre'] ?? '') . ' ' . 
                    ($persona['apellido_paterno'] ?? '') . ' ' . 
                    ($persona['apellido_materno'] ?? '')
                );
                
                // Por si en tu tabla la columna se llamaba distinto o venía en un solo campo
                if (empty(trim($nombre_completo))) {
                    $nombre_completo = $persona['nombre'] ?? ($persona['nombre_completo'] ?? '');
                }

                echo json_encode([
                    'encontrado' => true,
                    'nombre' => $nombre_completo,
                    'no_empleado' => $persona['no_empleado'] ?? '',
                    'area_adscripcion' => $persona['area_adscripcion'] ?? '',
                    'edad' => $persona['edad'] ?? null
                ]);
            } else {
                echo json_encode(['encontrado' => false]);
            }
        } catch (Exception $e) {
            echo json_encode(['encontrado' => false]);
        }
        exit();
        break;

    case 'exportar_excel':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_siniestros_personal_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        $stmt = $pdo->query("SELECT * FROM siniestros_personal");
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($registros) > 0) {
            fputcsv($output, array_keys($registros[0]));
            foreach ($registros as $row) {
                fputcsv($output, $row);
            }
        }
        fclose($output);
        exit();

    case 'limpiar_base':
        if (!$es_admin_general) {
            header("Location: index.php?error_msg=Acceso denegado.");
            exit();
        }
        try {
            $pdo->exec("TRUNCATE TABLE siniestros_personal");
            header("Location: index.php?msg=Base de datos vaciada correctamente.");
            exit();
        } catch (PDOException $e) {
            header("Location: index.php?error_msg=Error al vaciar la base.");
            exit();
        }
        break;

    case 'eliminar':
        if ($es_lectura) {
            header("Location: index.php?error_msg=Acceso denegado.");
            exit();
        }
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM siniestros_personal WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: index.php?msg=Registro eliminado.");
            exit();
        }
        break;

    default:
        header("Location: index.php");
        exit();
}
