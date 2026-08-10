<?php
require_once "../../config/conexion.php";

try {
    $total = $pdo->query("SELECT COUNT(*) FROM personal")->fetchColumn();
    $base = $pdo->query("SELECT COUNT(*) FROM personal WHERE tipo_contratacion = 'BASE'")->fetchColumn();
    $eventual = $pdo->query("SELECT COUNT(*) FROM personal WHERE tipo_contratacion = 'EVENTUAL'")->fetchColumn();
} catch (PDOException $e) {
    die("Error en reporte: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTES PERSONAL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; text-transform: uppercase; font-size: 12px; }
        .card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-chart-bar"></i> ESTADÍSTICAS DE PERSONAL</h4>
        <a href="index.php" class="btn btn-dark btn-sm">VOLVER</a>
    </div>
    <div class="row text-center">
        <div class="col-md-4">
            <div class="card p-4">
                <h6 class="text-muted">TOTAL</h6>
                <h2 class="display-5 fw-bold"><?= $total ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 text-success">
                <h6>BASE</h6>
                <h2 class="display-5 fw-bold"><?= $base ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 text-info">
                <h6>EVENTUAL</h6>
                <h2 class="display-5 fw-bold"><?= $eventual ?></h2>
            </div>
        </div>
    </div>
</div>
</body>
</html>
