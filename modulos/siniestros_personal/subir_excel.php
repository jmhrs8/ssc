<?php
require_once "../../config/conexion.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>IMPORTAR EXCEL - PERSONAL SINIESTRADO | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow col-md-8 mx-auto">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-file-excel text-success me-2"></i> IMPORTAR REGISTROS DESDE EXCEL</h4>
            <a href="index.php" class="btn btn-outline-light btn-sm">Regresar</a>
        </div>
        <div class="card-body">
            <form action="procesar_excel.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-bold">Selecciona tu archivo Excel (.xlsx o .csv)</label>
                    <input type="file" name="archivo_excel" class="form-control" accept=".xlsx, .xls, .csv" required>
                    <small class="text-muted">Asegúrate de que las columnas coincidan con el formato establecido.</small>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success fw-bold"><i class="fas fa-upload me-1"></i> PROCESAR E IMPORTAR DATOS</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
