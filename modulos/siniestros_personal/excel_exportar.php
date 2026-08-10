<?php
session_start();
require_once "../../config/conexion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=reporte_siniestros_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para acentos en Excel

// Cabeceras exactas
fputcsv($output, [
    'ID', 'No. Folio', 'Tipo', 'Mes de Reporte', 'No. Empleado', 'Edad', 'RFC', 
    'Nombre del Elemento', 'Fecha de Siniestro', 'Reporte', 'Póliza / Sección', 
    'Aseguradora', 'Causa Resumido', 'Unidad Vehicular', 'Lesiones', 
    'Área de Adscripción', 'Hospital', 'Requirió Hospitalización', 'Observaciones', 'Montos Erogados'
]);

try {
    $query = "SELECT * FROM siniestros_personal ORDER BY id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'], $row['no_folio'], $row['tipo'], $row['mes_de_reporte'], $row['no_empleado'],
            $row['edad'], $row['rfc'], $row['nombre'], $row['fecha_de_siniestro'], $row['reporte'],
            $row['poliza_seccion'], $row['aseguradora'], $causa_resumido = $row['causa_resumido'],
            $row['unidad_vehicular'], $row['lesiones'], $row['area_adscripcion'], $row['hospital'],
            $row['requirio_hospitalizacion'], $row['observaciones'], $row['montos_erogados']
        ]);
    }
} catch (PDOException $e) {
    // Manejo de excepción silenciosa en la descarga
}

fclose($output);
exit();
?>
