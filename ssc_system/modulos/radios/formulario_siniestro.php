<?php
session_start();
require_once "../../config/conexion.php";

$id_radio = $_GET['id'] ?? null;

// Consultamos los datos técnicos del inventario de radios
$stmt = $pdo->prepare("SELECT * FROM radios WHERE id = ?");
$stmt->execute([$id_radio]);
$radio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$radio) { die("Radio no encontrado."); }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Siniestro de Radio - SSC SYSTEM</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
</head>
<body class="container mt-4">
    <div class="card border-info">
        <div class="card-header bg-info text-white">
            <h4>Registro de Siniestro: Radio <?php echo $radio['marca'] . " - " . $radio['serie']; ?></h4>
        </div>
        <div class="card-body">
            <form action="../armas/guardar_siniestro_bien.php" method="POST">
                <input type="hidden" name="id_inventario" value="<?php echo $id_radio; ?>">
                <input type="hidden" name="tipo_modulo" value="RADIO">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Expediente Interno:</label>
                        <input type="text" name="folio_siniestro_interno" class="form-control" placeholder="D/C.P/002/2026" required>
                    </div>
                    <div class="col-md-4">
                        <label>Fecha Siniestro:</label>
                        <input type="date" name="fecha_siniestro" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>Hora Ocurrencia:</label>
                        <input type="time" name="hora_siniestro" class="form-control" required>
                    </div>
                </div>

                <hr>
                <h5>Datos del Resguardante</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Nombre del Policía:</label>
                        <input type="text" name="nombre_resguardante" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label>Grado:</label>
                        <input type="text" name="grado_resguardante" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>No. Empleado:</label>
                        <input type="text" name="num_empleado_resguardante" class="form-control" required>
                    </div>
                </div>

                <hr>
                <h5>Detalles del Evento</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Tipo de Siniestro:</label>
                        <select name="tipo_siniestro" class="form-control">
                            <option value="ROBO">ROBO</option>
                            <option value="EXTRAVÍO">EXTRAVÍO</option>
                            <option value="DAÑO">DAÑO / CAÍDA</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label>Lugar de los Hechos:</label>
                        <input type="text" name="lugar_siniestro" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label>Narrativa Detallada:</label>
                    <textarea name="narrativa_siniestro" class="form-control" rows="4"></textarea>
                </div>

                <div class="text-right">
                    <a href="index.php" class="btn btn-secondary">Regresar</a>
                    <button type="submit" class="btn btn-info">Guardar y Generar PDF</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
