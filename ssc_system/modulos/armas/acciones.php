<?php
/**
 * ARCHIVO: acciones.php (Módulo Armas)
 * FUNCIÓN: Eliminación individual y vaciado total de inventario.
 * CORRECCIÓN: Prevención de error "No active transaction" en el rollback y unificación de roles (Admin, Capturista, Lectura).
 */
session_start();
require_once "../../config/conexion.php";

// Seguridad: Verificar nivel de usuario y permisos específicos
$nivel_actual = strtolower(trim($_SESSION['nivel'] ?? ''));
$permiso_armas = $_SESSION['permiso_armas'] ?? 0;

// Validar que el usuario tenga sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Validar permisos de acceso general al módulo de armas
if ($nivel_actual !== 'admin_general' && $nivel_actual !== 'lectura' && $permiso_armas != 1) {
    header("Location: ../../index.php?error=sin_permiso");
    exit();
}

// 1. ACCIÓN: VACIAR TODO EL INVENTARIO (Solo ADMIN_GENERAL)
if (isset($_GET['vaciar']) && $_GET['vaciar'] == '1') {
    if ($nivel_actual === 'admin_general') {
        try {
            // NOTA: No usamos beginTransaction aquí porque SET y ALTER TABLE
            // provocan un commit implícito en MySQL, cerrando cualquier transacción.

            // 1. Desactivar revisión de llaves foráneas
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            // 2. Limpiar tablas
            $pdo->exec("DELETE FROM espejo_siniestros_armas");
            $pdo->exec("DELETE FROM inventario_armas");

            // 3. Reiniciar contadores de ID a 1
            $pdo->exec("ALTER TABLE espejo_siniestros_armas AUTO_INCREMENT = 1");
            $pdo->exec("ALTER TABLE inventario_armas AUTO_INCREMENT = 1");

            // 4. Reactivar revisión de llaves foráneas
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            header("Location: index.php?status=vaciado_exitoso");
            exit;
        } catch (PDOException $e) {
            // CORRECCIÓN: Verificamos si hay transacción antes de un rollback
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error en acciones.php (vaciar): " . $e->getMessage());
            die("ERROR CRÍTICO AL VACIAR: " . $e->getMessage());
        }
    } else {
        header("Location: index.php?error=sin_permiso");
        exit;
    }
}

// 2. ACCIÓN: ELIMINAR REGISTRO INDIVIDUAL (Solo ADMIN_GENERAL)
if (isset($_GET['eliminar'])) {
    if ($nivel_actual === 'admin_general') {
        $id = intval($_GET['eliminar']);
        try {
            $pdo->beginTransaction();

            // Primero eliminamos de la espejo por la relación de id_arma
            $stmt_espejo = $pdo->prepare("DELETE FROM espejo_siniestros_armas WHERE id_arma = ?");
            $stmt_espejo->execute([$id]);

            // Luego eliminamos de la principal
            $stmt_gral = $pdo->prepare("DELETE FROM inventario_armas WHERE id = ?");
            $stmt_gral->execute([$id]);

            $pdo->commit();

            header("Location: index.php?status=eliminado_exitoso");
            exit;
        } catch (Exception $e) {
            // CORRECCIÓN: Validación de transacción activa
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error en acciones.php (eliminar): " . $e->getMessage());
            die("ERROR AL ELIMINAR REGISTRO: " . $e->getMessage());
        }
    } else {
        header("Location: index.php?error=sin_permiso");
        exit;
    }
}

// Redirección por defecto si no hay parámetros válidos
header("Location: index.php");
exit;
