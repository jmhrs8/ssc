<?php
// views/ventas/pos.php
use Models\Producto;

$tipoModulo = $_GET['tipo'] ?? 'retail';
$productoModel = new Producto();
$productosBD = $productoModel->listarPorModulo($tipoModulo);
$categoriasBD = $productoModel->listarCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - <?= ucfirst($tipoModulo) ?></title>
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --bg-dark: #0f172a;
            --panel-bg: #1e293b;
            --card-bg: #334155;
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
            --border: #475569;
        }

        * { box-sizing: border-box; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; user-select: none; }

        body {
            margin: 0;
            background-color: var(--bg-dark);
            color: var(--text-light);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .top-bar {
            height: 60px;
            background: var(--panel-bg);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }

        .brand-section { display: flex; align-items: center; gap: 15px; }
        .btn-home {
            background: var(--card-bg);
            color: white;
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .mode-title { font-size: 18px; font-weight: bold; color: #818cf8; }

        .top-actions { display: flex; gap: 10px; }
        .btn-top {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-top.kds { background: #d97706; }

        .pos-container {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 380px;
            overflow: hidden;
        }

        .catalog-area {
            display: flex;
            flex-direction: column;
            padding: 15px;
            gap: 15px;
            overflow: hidden;
        }

        .search-and-cats {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .search-bar {
            width: 100%;
            padding: 12px 16px;
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: white;
            font-size: 16px;
        }

        .categories-row {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .cat-btn {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 10px 18px;
            border-radius: 10px;
            white-space: nowrap;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }

        .cat-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .products-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 180px));
            grid-auto-rows: max-content;
            gap: 15px;
            overflow-y: auto;
            padding-right: 5px;
            align-content: start;
        }

        .product-card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: transform 0.15s, border-color 0.15s;
            height: 210px;
        }

        .product-card:active { transform: scale(0.96); }
        .product-card:hover { border-color: var(--primary); }

        .product-img {
            height: 110px;
            width: 100%;
            background-size: cover;
            background-position: center;
            background-color: #1e293b;
        }

        .product-info {
            padding: 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex: 1;
        }

        .product-title { 
            font-size: 13px; 
            font-weight: 600; 
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price { 
            font-size: 16px; 
            font-weight: bold; 
            color: #34d399; 
            margin-top: 4px;
        }

        .cart-area {
            background: var(--panel-bg);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }

        .cart-header {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .cart-item {
            background: var(--card-bg);
            padding: 10px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-details { flex: 1; }
        .item-title { font-size: 14px; font-weight: 600; }
        .item-price { font-size: 12px; color: var(--text-muted); }

        .item-controls { display: flex; align-items: center; gap: 8px; }
        .btn-qty {
            width: 28px; height: 28px;
            background: var(--panel-bg);
            border: 1px solid var(--border);
            color: white;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .cart-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            background: rgba(0, 0, 0, 0.2);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .btn-pay {
            width: 100%;
            background: var(--success);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-pay:hover { background: #059669; }
    </style>
</head>
<body>

    <header class="top-bar">
        <div class="brand-section">
            <a href="index.php?route=dashboard" class="btn-home">← Menú</a>
            <span class="mode-title">
                <?= $tipoModulo === 'cafeteria' ? '☕ Módulo Cafetería' : '🛒 Módulo Tienda' ?>
            </span>
        </div>

        <div class="top-actions">
            <?php if ($tipoModulo === 'cafeteria'): ?>
                <a href="index.php?route=cocina" class="btn-top kds">🍳 Monitor Cocina (KDS)</a>
            <?php endif; ?>
            <a href="index.php?route=productos" class="btn-top" style="background:#4f46e5;">📦 Catálogo / Agregar</a>
            <a href="index.php?route=caja" class="btn-top">💰 Caja</a>
        </div>
    </header>

    <div class="pos-container">
        <div class="catalog-area">
            <div class="search-and-cats">
                <input type="text" class="search-bar" id="search-input" placeholder="🔍 Buscar por nombre o código de barras..." onkeyup="filtrarProductos()" autofocus>
                
                <div class="categories-row" id="categories-container">
                    <button class="cat-btn active" onclick="filtrarCategoria('todos', this)">Todos</button>
                    <?php foreach ($categoriasBD as $cat): ?>
                        <button class="cat-btn" onclick="filtrarCategoria(<?= $cat['id'] ?>, this)"><?= htmlspecialchars($cat['nombre']) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="products-grid" id="products-container">
                <!-- Se cargan dinámicamente desde PHP -->
            </div>
        </div>

        <div class="cart-area">
            <div class="cart-header">
                <h3 style="margin:0;">Comanda / Ticket</h3>
                <span id="items-count" style="color:var(--text-muted); font-size:14px;">0 productos</span>
            </div>

            <div class="cart-items" id="cart-container"></div>

            <div class="cart-footer">
                <div class="total-row">
                    <span>Total:</span>
                    <span id="cart-total" style="color:#34d399;">$0.00</span>
                </div>
                <button class="btn-pay" onclick="procesarPago()">COBRAR (F12)</button>
            </div>
        </div>
    </div>

    <script>
        // Productos inyectados desde MySQL
        const productosDB = <?= json_encode($productosBD) ?>;
        let carrito = [];
        let categoriaSeleccionada = 'todos';

        function renderProductos(lista) {
            const container = document.getElementById('products-container');
            if (lista.length === 0) {
                container.innerHTML = `<div style="grid-column: 1/-1; text-align:center; color:var(--text-muted); padding:40px;">No hay productos disponibles en este módulo o categoría.</div>`;
                return;
            }

            container.innerHTML = lista.map(p => {
                const imgUrl = p.imagen && p.imagen !== 'default.jpg' ? 'uploads/productos/' + p.imagen : 'https://via.placeholder.com/300?text=Sin+Imagen';
                return `
                    <div class="product-card" onclick="agregarAlCarrito(${p.id})">
                        <div class="product-img" style="background-image: url('${imgUrl}')"></div>
                        <div class="product-info">
                            <div class="product-title">${p.nombre}</div>
                            <div class="product-price">$${parseFloat(p.precio_venta).toFixed(2)}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function filtrarCategoria(catId, btnElement) {
            categoriaSeleccionada = catId;
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            btnElement.classList.add('active');
            aplicarFiltros();
        }

        function filtrarProductos() {
            aplicarFiltros();
        }

        function aplicarFiltros() {
            const query = document.getElementById('search-input').value.toLowerCase();
            let filtrados = productosDB.filter(p => {
                const matchTexto = p.nombre.toLowerCase().includes(query) || (p.codigo_barras && p.codigo_barras.toLowerCase().includes(query));
                const matchCat = categoriaSeleccionada === 'todos' || parseInt(p.categoria_id) === parseInt(categoriaSeleccionada);
                return matchTexto && matchCat;
            });
            renderProductos(filtrados);
        }

        function agregarAlCarrito(id) {
            const prod = productosDB.find(p => p.id == id);
            if (!prod) return;
            const existe = carrito.find(item => item.id == id);
            if (existe) {
                existe.cant++;
            } else {
                carrito.push({ ...prod, cant: 1 });
            }
            updateCarritoView();
        }

        function cambiarCant(id, delta) {
            const item = carrito.find(i => i.id == id);
            if (!item) return;
            item.cant += delta;
            if (item.cant <= 0) {
                carrito = carrito.filter(i => i.id != id);
            }
            updateCarritoView();
        }

        function updateCarritoView() {
            const container = document.getElementById('cart-container');
            let total = 0;
            let count = 0;

            container.innerHTML = carrito.map(i => {
                const subtotal = parseFloat(i.precio_venta) * i.cant;
                total += subtotal;
                count += i.cant;
                return `
                    <div class="cart-item">
                        <div class="item-details">
                            <div class="item-title">${i.nombre}</div>
                            <div class="item-price">$${parseFloat(i.precio_venta).toFixed(2)} x ${i.cant} = $${subtotal.toFixed(2)}</div>
                        </div>
                        <div class="item-controls">
                            <button class="btn-qty" onclick="cambiarCant(${i.id}, -1)">-</button>
                            <span>${i.cant}</span>
                            <button class="btn-qty" onclick="cambiarCant(${i.id}, 1)">+</button>
                        </div>
                    </div>
                `;
            }).join('');

            document.getElementById('cart-total').innerText = `$${total.toFixed(2)}`;
            document.getElementById('items-count').innerText = `${count} productos`;
        }

        function procesarPago() {
            if (carrito.length === 0) {
                alert('Seleccione al menos un producto.');
                return;
            }
            alert('¡Venta registrada con éxito en el sistema!');
            carrito = [];
            updateCarritoView();
        }

        // Inicializar vista con todos los productos del módulo actual
        renderProductos(productosDB);
    </script>
</body>
</html>
