<?php
require_once "../../config/conexion.php";

// Forzar la descarga del archivo como Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_solo_pendientes_todas_aseguradoras_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

try {
    // 1. Obtener todas las aseguradoras registradas
    $aseguradoras = $pdo->query("SELECT DISTINCT aseguradora FROM inventario_armas WHERE aseguradora != '' AND aseguradora IS NOT NULL ORDER BY aseguradora ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; text-transform: uppercase; }
        h2 { color: #1a1a1a; border-bottom: 3px solid #0dcaf0; padding-bottom: 5px; font-size: 16px; }
        h3 { color: #1a1a1a; margin-top: 25px; border-bottom: 2px solid #0dcaf0; padding-bottom: 4px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #1a1a1a; color: #ffffff; padding: 6px; border: 1px solid #ddd; text-align: center; font-weight: bold; }
        td { padding: 5px; border: 1px solid #ddd; text-align: center; }
        td.left { text-align: left; }
    </style>
</head>
<body>
    <h2>REPORTE EXCLUSIVO DE PENDIENTES - TODAS LAS ASEGURADORAS</h2>
    
    <?php 
    $total_encontrados = 0;
    foreach ($aseguradoras as $aseg): 
        // 2. Filtrar estrictamente solo los registros pendientes, vacíos o nulos de cada aseguradora
        $stmt = $pdo->prepare("SELECT * FROM inventario_armas WHERE aseguradora = ? AND (status_seguro LIKE '%PENDIENTE%' OR status_seguro = '' OR status_seguro IS NULL)");
        $stmt->execute([$aseg]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si esta aseguradora no tiene registros pendientes, se omite para mostrar únicamente los pendientes reales
        if (empty($registros)) continue;
        
        $total_encontrados += count($registros);
    ?>
        <h3>ASEGURADORA: <?= htmlspecialchars($aseg) ?> (PENDIENTES: <?= count($registros) ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>ASEGURADORA</th>
                    <th>FECHA_SINIESTRO_1</th>
                    <th>FECHA_RECLAMACION</th>
                    <th>STATUS_SEGURO</th>
                    <th>NO_SINIESTRO</th>
                    <th>TIPO_BIEN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $row): 
                    // Mapeo flexible de nombres de columnas
                    $f_siniestro = $row['fecha_siniestro_1'] ?? $row['fecha_siniestro'] ?? $row['fecha'] ?? '';
                    $f_reclamacion = $row['fecha_reclamacion'] ?? $row['fecha_reclama'] ?? '';
                    if (empty($f_reclamacion)) $f_reclamacion = 'S/F';
                    
                    $status = !empty(trim($row['status_seguro'])) ? $row['status_seguro'] : 'PENDIENTE';
                    $no_siniestro = $row['no_siniestro'] ?? $row['no_siniestro_aseg'] ?? '';
                    $tipo_bien = $row['tipo_bien'] ?? $row['tipo'] ?? '';
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['aseguradora'] ?? $aseg) ?></td>
                    <td><?= htmlspecialchars($f_siniestro) ?></td>
                    <td><?= htmlspecialchars($f_reclamacion) ?></td>
                    <td><?= htmlspecialchars($status) ?></td>
                    <td><?= htmlspecialchars($no_siniestro) ?></td>
                    <td class="left"><?= htmlspecialchars($tipo_bien) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; 

    if ($total_encontrados === 0) {
        echo "<p style='color: red; font-weight: bold;'>No se encontraron registros pendientes en ninguna aseguradora.</p>";
    }
    ?>

</body>
</html>
<?php
} catch (PDOException $e) {
    die("Error al generar el reporte en Excel: " . $e->getMessage());
}
?>
