<?php
require_once "../../config/conexion.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carga Masiva Radios | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-size: 13px; }
        .card { border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-upload { background-color: #17a2b8; color: white; border: none; }
        .btn-upload:hover { background-color: #138496; color: white; }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-file-excel"></i> Cargar Inventario de Radios</h6>
                    <a href="index.php" class="btn btn-sm btn-outline-light">Volver</a>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info border-0 shadow-sm">
                        <i class="fas fa-info-circle"></i> <strong>Instrucciones:</strong>
                        <ul class="mb-0 mt-2">
                            <li>El archivo debe ser formato <strong>.xlsx</strong> o <strong>.csv</strong>.</li>
                            <li>Asegúrese de que el orden de las columnas sea el mismo del listado.</li>
                        </ul>
                    </div>

                    <form action="procesar_excel.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Seleccione el archivo:</label>
                            <input type="file" name="archivo_excel" class="form-control" accept=".xlsx, .xls, .csv" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-upload shadow-sm">
                                <i class="fas fa-cloud-upload-alt"></i> Iniciar Carga Masiva
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted text-center small">
                    SSC System v3.0 - VMware Platform
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
