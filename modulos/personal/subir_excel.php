<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Personal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        #loadingArea { display: none; }
        .progress { height: 30px; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white text-center">
                    <h5 class="mb-0">SUBIR PERSONAL (FORMATO EXCEL)</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form id="uploadForm" action="procesar_excel.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Seleccione el archivo:</label>
                            <input type="file" name="archivo_excel" class="form-control" accept=".xlsx, .xls" required>
                        </div>
                        
                        <div id="loadingArea">
                            <p class="text-center mb-1"><i class="fas fa-spinner fa-spin"></i> Procesando datos, por favor espere...</p>
                            <div class="progress mb-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                     role="progressbar" style="width: 100%">CARGANDO...</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2" id="actionButtons">
                            <button type="submit" class="btn btn-dark btn-lg">INICIAR SUBIDA</button>
                            <a href="index.php" class="btn btn-outline-secondary">REGRESAR</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('uploadForm').onsubmit = function() {
    // Ocultar botones y mostrar barra de progreso
    document.getElementById('actionButtons').style.display = 'none';
    document.getElementById('loadingArea').style.display = 'block';
};
</script>
</body>
</html>
