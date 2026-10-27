<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal - POS Híbrido</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { box-sizing: border-box; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }

        body {
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header {
            width: 100%;
            max-width: 1100px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            padding: 16px 24px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 40px;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .user-details h3 { margin: 0; font-size: 16px; font-weight: 600; }
        .user-details p { margin: 0; font-size: 12px; color: var(--text-muted); }

        .nav-actions { display: flex; gap: 12px; }

        .btn-header {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-header:hover {
            background: rgba(255, 255, 255, 0.18);
            transform: translateY(-2px);
        }

        .btn-logout { background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); color: #fca5a5; }
        .btn-logout:hover { background: rgba(239, 68, 68, 0.4); }

        .title-container {
            text-align: center;
            margin-bottom: 40px;
        }

        .title-container h1 {
            font-size: 32px;
            margin: 0 0 10px 0;
            background: linear-gradient(to right, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .title-container p { color: var(--text-muted); font-size: 16px; margin: 0; }

        .deck-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(320px, 420px));
            gap: 40px;
            width: 100%;
            max-width: 900px;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .deck-grid { grid-template-columns: 1fr; }
        }

        .card-deck {
            position: relative;
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border-radius: 24px;
            padding: 40px 30px;
            text-decoration: none;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .card-deck::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 6px;
            background: linear-gradient(to right, #3b82f6, #06b6d4);
            transition: height 0.3s;
        }

        .card-deck.cafeteria::before {
            background: linear-gradient(to right, #f59e0b, #ef4444);
        }

        .card-deck:hover {
            transform: translateY(-12px) scale(1.02);
            border-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
        }

        .card-deck:hover::before { height: 10px; }

        .card-icon {
            font-size: 72px;
            margin-bottom: 20px;
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.3));
            transition: transform 0.3s;
        }

        .card-deck:hover .card-icon { transform: scale(1.15) rotate(5deg); }

        .card-title { font-size: 26px; font-weight: 700; margin: 0 0 12px 0; letter-spacing: 0.5px; }
        .card-desc { font-size: 14px; color: var(--text-muted); line-height: 1.5; margin: 0 0 24px 0; }

        .card-btn {
            margin-top: auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.2s;
        }

        .card-deck:hover .card-btn {
            background: #fff;
            color: #0f172a;
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="user-badge">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['usuario_nombre'] ?? 'U', 0, 1)) ?></div>
            <div class="user-details">
                <h3><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?></h3>
                <p>Rol: <?= htmlspecialchars($_SESSION['rol_nombre'] ?? 'Personal') ?></p>
            </div>
        </div>
        <div class="nav-actions">
            <a href="index.php?route=caja" class="btn-header">
                💰 Control de Caja
            </a>
            <a href="index.php?route=logout" class="btn-header btn-logout">
                🚪 Salir
            </a>
        </div>
    </header>

    <div class="title-container">
        <h1>Sistema Punto de Venta Híbrido</h1>
        <p>Seleccione el entorno operativo de trabajo</p>
    </div>

    <div class="deck-grid">
        <!-- Tarjeta 1: Tienda Retail -->
        <a href="index.php?route=ventas&tipo=retail" class="card-deck retail">
            <div class="card-icon">🛒</div>
            <div class="card-title">Tienda / Abarrotes</div>
            <div class="card-desc">Venta rápida al mostrador por código de barras, búsqueda de productos y cobro directo en caja.</div>
            <div class="card-btn">ABRIR TIENDA →</div>
        </a>

        <!-- Tarjeta 2: Cafetería / Restaurante -->
        <a href="index.php?route=ventas&tipo=cafeteria" class="card-deck cafeteria">
            <div class="card-icon">☕</div>
            <div class="card-title">Cafetería & Restaurante</div>
            <div class="card-desc">Control de mesas, comandas por área, bebidas preparadas, repostería y pantalla de cocina (KDS).</div>
            <div class="card-btn">ABRIR CAFETERÍA →</div>
        </a>
    </div>

</body>
</html>
