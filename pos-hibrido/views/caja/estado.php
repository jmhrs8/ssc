<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado y Cierre de Caja - POS Híbrido</title>
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --bg-main: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .dashboard-container {
            width: 100%;
            max-width: 900px;
        }

        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--card-bg);
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .header-box h2 {
            margin: 0;
            font-size: 22px;
            color: #111827;
        }

        .badge-open {
            background-color: #d1fae5;
            color: #065f46;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .grid-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .grid-layout { grid-template-columns: 1fr; }
        }

        .card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .card h3 {
            margin-top: 0;
            font-size: 18px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 10px;
            color: #374151;
        }

        .table-resumen {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table-resumen th, .table-resumen td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .table-resumen th {
            color: var(--text-muted);
            font-weight: 600;
        }

        .table-resumen tr.total-row {
            font-weight: bold;
            background-color: #f9fafb;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 6px;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }

        textarea.form-control {
            resize: vertical;
            height: 80px;
        }

        .btn-close-box {
            background-color: #dc2626;
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 10px;
        }

        .btn-close-box:hover {
            background-color: #b91c1c;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <!-- Encabezado -->
    <div class="header-box">
        <h2>Control de Caja y Turno</h2>
        <span class="badge-open">● Caja Abierta</span>
    </div>

    <div class="grid-layout">
        
        <!-- Columna Izquierda: Resumen de Flujo de Efectivo -->
        <div class="card">
            <h3>Flujo de Efectivo Actual</h3>
            <table class="table-resumen">
                <tr>
                    <th>Concepto</th>
                    <th style="text-align: right;">Monto</th>
                </tr>
                <tr>
                    <td>Fondo de Apertura</td>
                    <td style="text-align: right;">$<?= number_format($flujo['monto_apertura'], 2) ?></td>
                </tr>
                <tr>
                    <td>Ventas en Efectivo</td>
                    <td style="text-align: right;">$<?= number_format($flujo['efectivo'], 2) ?></td>
                </tr>
                <tr>
                    <td>Ventas con Tarjeta</td>
                    <td style="text-align: right;">$<?= number_format($flujo['tarjeta'], 2) ?></td>
                </tr>
                <tr>
                    <td>Ventas por Transferencia</td>
                    <td style="text-align: right;">$<?= number_format($flujo['transferencia'], 2) ?></td>
                </tr>
                <tr class="total-row">
                    <td>Efectivo Teórico Esperado</td>
                    <td style="text-align: right; color: var(--success);">$<?= number_format($flujo['efectivo_teorico_esperado'], 2) ?></td>
                </tr>
            </table>
        </div>

        <!-- Columna Derecha: Formulario de Arqueo y Cierre -->
        <div class="card">
            <h3>Arqueo y Cierre de Caja</h3>
            <form action="index.php?route=caja/cerrar" method="POST">
                <input type="hidden" name="caja_id" value="<?= htmlspecialchars($caja['id']) ?>">
                
                <div class="form-group">
                    <label for="monto_cierre_real">Efectivo Contado Físicamente ($):</label>
                    <input type="number" step="0.01" id="monto_cierre_real" name="monto_cierre_real" class="form-control" required placeholder="0.00" autofocus>
                </div>

                <div class="form-group">
                    <label for="notas">Observaciones / Notas del Turno:</label>
                    <textarea id="notas" name="notas" class="form-control" placeholder="Ej. Retiro parcial de efectivo, faltante justificado..."></textarea>
                </div>

                <button type="submit" class="btn-close-box" onclick="return confirm('¿Está seguro de realizar el arqueo y cerrar el turno de caja?');">
                    Cerrar Turno de Caja
                </button>
            </form>
        </div>

    </div>

</div>

</body>
</html>
