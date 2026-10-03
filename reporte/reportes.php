<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=acceso_denegado");
    exit();
}

include('config.php');

// Consultas de productividad detallada
$hoy = date('Y-m-d');
$mes_actual = date('m');
$año_actual = date('Y');

// 1. Conteo por Usuario (Quién trabaja más)
$prod_usuarios = $conn->query("SELECT registrado_por, COUNT(*) as total FROM reportes GROUP BY registrado_por ORDER BY total DESC");

// 2. Conteo por Tipo de Unidad
$prod_tipos = $conn->query("SELECT n_tipo, COUNT(*) as total FROM reportes GROUP BY n_tipo ORDER BY total DESC");

// 3. Últimos 20 movimientos
$ultimos = $conn->query("SELECT n_inventario, n_placa, registrado_por, fecha FROM reportes ORDER BY id DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Productividad - SSC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --guinda: #6b1e34; --oro: #b38e5d; }
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--oro); padding-bottom: 15px; margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; }
        .card h3 { margin-top: 0; color: var(--guinda); border-bottom: 1px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 8px; background: #f8f9fa; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .btn-back { text-decoration: none; color: var(--guinda); font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2 style="color:var(--guinda); margin:0;"><i class="fas fa-chart-line"></i> PRODUCTIVIDAD DEL SISTEMA</h2>
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> VOLVER</a>
    </div>

    <div class="grid">
        <div class="card">
            <h3><i class="fas fa-user-edit"></i> Por Operador (Total)</h3>
            <table>
                <thead><tr><th>Usuario</th><th>Total Reportes</th></tr></thead>
                <tbody>
                    <?php while($u = $prod_usuarios->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['registrado_por']) ?></strong></td>
                        <td><?= $u['total'] ?> unidades</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3><i class="fas fa-car"></i> Por Tipo de Unidad</h3>
            <table>
                <thead><tr><th>Tipo</th><th>Cantidad</th></tr></thead>
                <tbody>
                    <?php while($t = $prod_tipos->fetch_assoc()): ?>
                    <tr>
                        <td><?= strtoupper(htmlspecialchars($t['n_tipo'])) ?></td>
                        <td><?= $t['total'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3><i class="fas fa-history"></i> Últimos 20 Registros</h3>
        <table>
            <thead>
                <tr><th>Inventario</th><th>Placa</th><th>Registró</th><th>Fecha/Hora</th></tr>
            </thead>
            <tbody>
                <?php while($row = $ultimos->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['n_inventario'] ?></td>
                    <td><?= $row['n_placa'] ?></td>
                    <td><small><?= $row['registrado_por'] ?></small></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
