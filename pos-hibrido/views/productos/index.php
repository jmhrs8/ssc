<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Productos - POS Híbrido</title>
    <style>
        :root {
            --bg: #0f172a;
            --panel: #1e293b;
            --card: #334155;
            --text: #f8fafc;
            --primary: #4f46e5;
            --success: #10b981;
            --border: #475569;
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-back { background: var(--card); color: white; padding: 10px 16px; border-radius: 8px; text-decoration: none; border: 1px solid var(--border); }
        
        .grid-layout { display: grid; grid-template-columns: 360px 1fr; gap: 20px; }
        @media (max-width: 800px) { .grid-layout { grid-template-columns: 1fr; } }

        .card-panel { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
        .card-panel h3 { margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 10px; }

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .form-control { width: 100%; padding: 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; color: white; box-sizing: border-box; }
        
        .btn-save { background: var(--success); color: white; border: none; width: 100%; padding: 12px; font-weight: bold; border-radius: 8px; cursor: pointer; }
        
        .table-prods { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-prods th, .table-prods td { padding: 10px; border-bottom: 1px solid var(--border); text-align: left; font-size: 14px; }
        .img-thumb { width: 45px; height: 45px; object-fit: cover; border-radius: 6px; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-retail { background: #1e3a8a; color: #93c5fd; }
        .badge-cafeteria { background: #78350f; color: #fde68a; }
        .badge-ambos { background: #065f46; color: #a7f3d0; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📦 Catálogo de Productos e Inventario</h2>
        <a href="index.php?route=dashboard" class="btn-back">← Menú Principal</a>
    </div>

    <div class="grid-layout">
        <!-- Formulario -->
        <div class="card-panel">
            <h3>Agregar Nuevo Producto</h3>
            <form action="index.php?route=productos/guardar" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nombre del Producto *</label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Ej. Baguette de Pavo">
                </div>
                <div class="form-group">
                    <label>Código de Barras</label>
                    <input type="text" name="codigo_barras" class="form-control" placeholder="Ej. 7501000123456">
                </div>
                <div class="form-group">
                    <label>Categoría *</label>
                    <select name="categoria_id" class="form-control" required>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Módulo / Asignación *</label>
                    <select name="tipo_modulo" class="form-control">
                        <option value="retail">Tienda (Retail)</option>
                        <option value="cafeteria">Cafetería</option>
                        <option value="ambos" selected>Ambos Módulos</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Precio de Venta ($) *</label>
                    <input type="number" step="0.01" name="precio_venta" class="form-control" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Costo ($)</label>
                    <input type="number" step="0.01" name="costo" class="form-control" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Stock Inicial</label>
                    <input type="number" name="stock_actual" class="form-control" value="10">
                </div>
                <div class="form-group">
                    <label>Fotografía del Producto</label>
                    <input type="file" name="imagen" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn-save">Guardar Producto</button>
            </form>
        </div>

        <!-- Tabla -->
        <div class="card-panel">
            <h3>Productos Registrados</h3>
            <table class="table-prods">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Módulo</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr><td colspan="5" style="text-align:center; color:#94a3b8;">No hay productos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?= $p['imagen'] !== 'default.jpg' ? 'uploads/productos/' . $p['imagen'] : 'https://via.placeholder.com/45' ?>" class="img-thumb" alt="">
                                </td>
                                <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                                <td><?= htmlspecialchars($p['categoria_nombre'] ?? 'Sin categoría') ?></td>
                                <td>
                                    <span class="badge badge-<?= $p['tipo_modulo'] ?>">
                                        <?= strtoupper($p['tipo_modulo']) ?>
                                    </span>
                                </td>
                                <td style="color:#34d399; font-weight:bold;">$<?= number_format($p['precio_venta'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
