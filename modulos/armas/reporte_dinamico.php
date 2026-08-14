<?php
session_start();
require_once "../../config/conexion.php";

// 1. Obtener todas los nombres de las columnas de la tabla de forma segura
$stmt_cols = $pdo->query("DESCRIBE inventario_armas");
$columnas = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);

// 2. Identificar qué columnas seleccionó el usuario
$columnas_seleccionadas = [];
for ($i = 1; $i <= 6; $i++) {
    $c = $_GET["campo_$i"] ?? '';
    if (!empty($c) && in_array($c, $columnas) && !in_array($c, $columnas_seleccionadas)) {
        $columnas_seleccionadas[] = $c;
    }
}
// Si no seleccionó nada, por defecto mostramos el ID
if (empty($columnas_seleccionadas)) $columnas_seleccionadas = ['id'];

// 3. Procesar filtros (solo si se seleccionó columna y tiene valor)
$condiciones = [];
$params = [];
for ($i = 1; $i <= 6; $i++) {
    $col = $_GET["campo_$i"] ?? '';
    $val = trim($_GET["valor_$i"] ?? '');
    if (!empty($col) && $val !== '' && in_array($col, $columnas)) {
        $condiciones[] = "UPPER($col) LIKE ?";
        $params[] = "%" . strtoupper($val) . "%";
    }
}

$where = count($condiciones) > 0 ? " WHERE " . implode(" AND ", $condiciones) : "";
$cols_sql = implode(", ", $columnas_seleccionadas);
$sql = "SELECT $cols_sql FROM inventario_armas $where ORDER BY id DESC";

// 4. Lógica de Exportación a Excel (CSV) ESTRICTA
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_seleccionado_'.date('Ymd_His').'.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8
    
    // Encabezados
    fputcsv($output, array_map('strtoupper', $columnas_seleccionadas));

    // Datos
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fila = [];
        foreach ($columnas_seleccionadas as $col) {
            $fila[] = strtoupper((string)$row[$col]);
        }
        fputcsv($output, $fila);
    }
    fclose($output);
    exit();
}

// Ejecutar consulta para pantalla
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporteador | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-size: 12px; text-transform: uppercase; }
        .table th { background: #212529 !important; color: white !important; }
    </style>
</head>
<body class="p-3">

<div class="card p-3 mb-3">
    <h6 class="fw-bold mb-3">GENERADOR DE REPORTES (SOLO MUESTRA COLUMNAS SELECCIONADAS)</h6>
    <form method="GET" class="row g-2">
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <div class="col-md-2">
            <select name="campo_<?= $i ?>" class="form-select form-select-sm">
                <option value="">-- Columna <?= $i ?> --</option>
                <?php foreach ($columnas as $c): ?>
                    <option value="<?= $c ?>" <?= ($_GET["campo_$i"] ?? '') == $c ? 'selected' : '' ?>><?= strtoupper($c) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="valor_<?= $i ?>" class="form-control form-control-sm mt-1" placeholder="Filtro..." value="<?= htmlspecialchars($_GET["valor_$i"] ?? '') ?>">
        </div>
        <?php endfor; ?>
        <div class="col-12 mt-2">
            <button type="submit" class="btn btn-primary btn-sm">Aplicar y Ver</button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="btn btn-success btn-sm">Exportar Excel</a>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-sm table-striped">
        <thead>
            <tr><?php foreach ($columnas_seleccionadas as $c): ?><th><?= strtoupper($c) ?></th><?php endforeach; ?></tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $r): ?>
            <tr><?php foreach ($columnas_seleccionadas as $c): ?><td><?= htmlspecialchars($r[$c] ?? '') ?></td><?php endforeach; ?></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
