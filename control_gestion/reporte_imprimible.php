<?php
$host = '127.0.0.1'; $db = 'ssc_control_gestion'; $user = 'root'; $pass = 'jmhl2474';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
$registros = $pdo->query("SELECT * FROM control_gestion ORDER BY id_registro DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Gestión</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .firmas { margin-top: 50px; display: flex; justify-content: space-around; }
        .firma-box { width: 40%; text-align: center; border-top: 1px solid #000; padding-top: 10px; }
        @media print {
            .btn-imprimir { display: none; }
        }
    </style>
</head>
<body>
    <button class="btn-imprimir" onclick="window.print()">Imprimir / Guardar PDF</button>
    <h2 style="text-align:center;">REPORTE DE CONTROL DE GESTIÓN</h2>
    <p>Fecha de emisión: <?php echo date('d/m/Y H:i'); ?></p>

    <table>
        <thead>
            <tr>
                <th>Folio</th>
                <th>Oficio</th>
                <th>Asunto</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($registros as $r): ?>
            <tr>
                <td><?php echo $r['id_registro']; ?></td>
                <td><?php echo $r['numero_oficio']; ?></td>
                <td><?php echo $r['asunto']; ?></td>
                <td><?php echo (!empty($r['pdf_conclusion'])) ? 'CONCLUIDO' : 'PENDIENTE'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="firmas">
        <div class="firma-box">Instrucciones</div>
        <div class="firma-box">Visto Bueno</div>
    </div>
</body>
</html>
