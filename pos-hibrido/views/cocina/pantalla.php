<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Cocina (KDS) - POS Híbrido</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: white; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .kds-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card-comanda { background: #1e293b; border-radius: 12px; padding: 15px; border-top: 6px solid #f59e0b; }
        .card-comanda.lista { border-top-color: #10b981; }
        .comanda-header { display: flex; justify-content: space-between; font-weight: bold; border-bottom: 1px solid #475569; padding-bottom: 8px; margin-bottom: 10px; }
        .item-list { list-style: none; padding: 0; margin: 0 0 15px 0; }
        .item-list li { padding: 6px 0; border-bottom: 1px dashed #334155; }
        .btn-listo { background: #10b981; color: white; border: none; width: 100%; padding: 10px; border-radius: 8px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="header">
        <h2>🍳 Monitor de Preparación (Cocina / Barra)</h2>
        <a href="index.php?route=ventas&tipo=cafeteria" style="color:white; text-decoration:none; background:#334155; padding:8px 16px; border-radius:8px;">← Volver</a>
    </div>

    <div class="kds-grid">
        <div class="card-comanda">
            <div class="comanda-header">
                <span>Mesa 3</span>
                <span>Hace 4 min</span>
            </div>
            <ul class="item-list">
                <li>1x Baguette Clásico Jamón (Sin cebolla)</li>
                <li>1x Capuchino Italiano (Leche deslactosada)</li>
            </ul>
            <button class="btn-listo">Marcar Como Listo</button>
        </div>
    </div>
</body>
</html>
