<?php
require_once 'includes/header.php';

$mensajeExito = '';
$mensajeError = '';

// --- PROCESAR LIQUIDACIÓN / COBRO DE CRÉDITO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['liquidar_cxc'])) {
    $cxcId      = intval($_POST['cxc_id'] ?? 0);
    $montoAbono = floatval($_POST['monto_abono'] ?? 0);
    $metodoPago = $_POST['metodo_pago'] ?? 'efectivo';

    if ($cxcId > 0 && $montoAbono > 0) {
        try {
            $pdo->beginTransaction();

            $stmtCxC = $pdo->prepare("SELECT * FROM cuentas_cobrar WHERE id = ?");
            $stmtCxC->execute([$cxcId]);
            $cuenta = $stmtCxC->fetch(PDO::FETCH_ASSOC);

            if ($cuenta && ($cuenta['estatus'] === 'pendiente' || floatval($cuenta['monto']) > 0)) {
                $saldoActual = floatval($cuenta['monto']);

                if ($montoAbono >= $saldoActual) {
                    $montoCobrado = $saldoActual;
                    $nuevoSaldo   = 0.00;
                    $nuevoEstatus = 'cobrado';
                } else {
                    $montoCobrado = $montoAbono;
                    $nuevoSaldo   = $saldoActual - $montoAbono;
                    $nuevoEstatus = 'pendiente';
                }

                // 1. Actualizar el saldo y estatus en cuentas_cobrar
                $stmtUp = $pdo->prepare("UPDATE cuentas_cobrar SET monto = ?, estatus = ?, fecha_cobro = NOW() WHERE id = ?");
                $stmtUp->execute([$nuevoSaldo, $nuevoEstatus, $cxcId]);

                // 2. Obtener un producto_id válido si existe en detalle_salidas (para evitar restricciones NOT NULL)
                $productoId = 0;
                if (!empty($cuenta['salida_id'])) {
                    $stmtProd = $pdo->prepare("SELECT producto_id FROM detalle_salidas WHERE salida_id = ? LIMIT 1");
                    $stmtProd->execute([$cuenta['salida_id']]);
                    $prodFetch = $stmtProd->fetchColumn();
                    if ($prodFetch) {
                        $productoId = intval($prodFetch);
                    }
                }

                // 3. Registrar el flujo financiero en ingresos pasando producto_id, cantidad y costo_unitario
                $concepto = "Cobro de crédito a {$cuenta['cliente']} (Venta #{$cuenta['salida_id']})";
                $stmtIngreso = $pdo->prepare("INSERT INTO ingresos 
                    (salida_id, producto_id, cantidad, costo_unitario, concepto, monto_subtotal, monto_iva, monto_total, metodo_pago, fecha_ingreso) 
                    VALUES (?, ?, 1, ?, ?, ?, 0.00, ?, ?, NOW())");
                $stmtIngreso->execute([$cuenta['salida_id'], $productoId, $montoCobrado, $concepto, $montoCobrado, $montoCobrado, $metodoPago]);

                $pdo->commit();
                $mensajeExito = "Cobro registrado correctamente ($" . number_format($montoCobrado, 2) . "). Saldo pendiente: $" . number_format($nuevoSaldo, 2);
            } else {
                $pdo->rollBack();
                $mensajeError = "La cuenta a cobrar ya fue procesada o no existe.";
            }
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $mensajeError = "Error al abonar la cuenta: " . $e->getMessage();
        }
    } else {
        $mensajeError = "Ingresa un monto válido mayor a cero.";
    }
}

// --- CONSULTAS PARA TARJETAS DE TOTALES SUPERIORES ---
try {
    // Total Por Cobrar (Pendiente)
    $stmtTotPend = $pdo->query("SELECT SUM(monto) FROM cuentas_cobrar WHERE estatus = 'pendiente'");
    $totalPendiente = floatval($stmtTotPend->fetchColumn() ?? 0);

    // Total Recuperado (Cobrado)
    $stmtTotCob = $pdo->query("SELECT SUM(monto_total) FROM ingresos WHERE salida_id IS NOT NULL AND concepto LIKE '%Cobro de crédito%'");
    $totalCobrado = floatval($stmtTotCob->fetchColumn() ?? 0);

    // Total Créditos Emitidos
    $totalGeneralCredito = $totalPendiente + $totalCobrado;

    // Clientes únicos con saldo pendiente
    $stmtClientes = $pdo->query("SELECT COUNT(DISTINCT cliente) FROM cuentas_cobrar WHERE estatus = 'pendiente' AND monto > 0");
    $clientesDeudores = intval($stmtClientes->fetchColumn() ?? 0);
} catch (\PDOException $e) {
    $totalPendiente = 0;
    $totalCobrado = 0;
    $totalGeneralCredito = 0;
    $clientesDeudores = 0;
}

// --- CONSULTA DE SALDOS PENDIENTES ---
try {
    $sqlCxC = "SELECT * FROM cuentas_cobrar WHERE estatus = 'pendiente' AND monto > 0 ORDER BY fecha_emision DESC";
    $cuentas = $pdo->query($sqlCxC)->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $cuentas = [];
    $mensajeError = "Error al consultar Cuentas por Cobrar: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-receipt text-warning me-2"></i> Cuentas por Cobrar (Créditos a Clientes)</h2>
</div>

<?php if ($mensajeExito): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($mensajeExito) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($mensajeError): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($mensajeError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- TARJETAS DE MÉTRICAS SUPERIORES -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-dark shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1 fw-bold">Por Cobrar (Pendiente)</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalPendiente, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1 fw-bold">Total Recuperado</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalCobrado, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1 fw-bold">Total Créditos Emitidos</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalGeneralCredito, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1 fw-bold">Clientes con Saldo</h6>
                <h3 class="fw-bold mb-0"><?= $clientesDeudores ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- TABLA DE CONTROL DE SALDOS -->
<div class="card shadow-sm">
    <div class="card-header bg-dark text-white fw-bold">
        <i class="bi bi-list-check me-1"></i> Control de Saldos Pendientes
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha Venta</th>
                        <th>Cliente</th>
                        <th>Venta #</th>
                        <th class="text-end">Monto Pendiente</th>
                        <th class="text-center">Estatus</th>
                        <th class="text-center" style="width: 300px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cuentas)): ?>
                        <tr><td colspan="6" class="text-center py-3 text-muted">Sin cuentas pendientes por cobrar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cuentas as $c): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($c['fecha_emision'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($c['cliente']) ?></td>
                                <td><code>#<?= $c['salida_id'] ?></code></td>
                                <td class="text-end fw-bold text-danger fs-6">$<?= number_format($c['monto'], 2) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                </td>
                                <td>
                                    <form method="POST" action="cuentas_cobrar.php" class="d-flex gap-1 align-items-center">
                                        <input type="hidden" name="liquidar_cxc" value="1">
                                        <input type="hidden" name="cxc_id" value="<?= $c['id'] ?>">

                                        <input type="number" step="0.01" min="0.01" max="<?= $c['monto'] ?>" 
                                               name="monto_abono" class="form-control form-control-sm" 
                                               value="<?= number_format($c['monto'], 2, '.', '') ?>" required>

                                        <select name="metodo_pago" class="form-select form-select-sm" style="width: 110px;">
                                            <option value="efectivo">Efectivo</option>
                                            <option value="transferencia">SPEI</option>
                                            <option value="tarjeta">Tarjeta</option>
                                        </select>

                                        <button type="submit" class="btn btn-sm btn-success fw-bold d-flex align-items-center">
                                            <i class="bi bi-cash me-1"></i> Cobrar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
