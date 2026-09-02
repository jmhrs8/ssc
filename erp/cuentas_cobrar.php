<?php
require_once 'includes/header.php';

$mensajeExito = '';
$mensajeError = '';

// PROCESAR LIQUIDACIÓN DE CRÉDITO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['liquidar_cxc'])) {
    $cxcId      = intval($_POST['cxc_id'] ?? 0);
    $metodoPago = $_POST['metodo_pago'] ?? 'efectivo';

    if ($cxcId > 0) {
        try {
            $pdo->beginTransaction();

            $stmtCxC = $pdo->prepare("SELECT * FROM cuentas_cobrar WHERE id = ? AND estatus = 'pendiente'");
            $stmtCxC->execute([$cxcId]);
            $cuenta = $stmtCxC->fetch(PDO::FETCH_ASSOC);

            if ($cuenta) {
                // a) Marcar como Cobrado en CxC
                $stmtUp = $pdo->prepare("UPDATE cuentas_cobrar SET estatus = 'cobrado', fecha_cobro = NOW() WHERE id = ?");
                $stmtUp->execute([$cxcId]);

                // b) Enviar a Ingresos de Caja/Banco
                $concepto = "Cobro de crédito a {$cuenta['cliente']} (Venta #{$cuenta['salida_id']})";
                $stmtIngreso = $pdo->prepare("INSERT INTO ingresos 
                    (salida_id, concepto, monto_subtotal, monto_iva, monto_total, metodo_pago, fecha_ingreso) 
                    VALUES (?, ?, ?, 0.00, ?, ?, NOW())");
                $stmtIngreso->execute([$cuenta['salida_id'], $concepto, $cuenta['monto'], $cuenta['monto'], $metodoPago]);

                $pdo->commit();
                $mensajeExito = "Crédito liquidado exitosamente y registrado en Ingresos.";
            } else {
                $mensajeError = "La cuenta a cobrar ya fue procesada o no existe.";
            }
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $mensajeError = "Error al abonar la cuenta: " . $e->getMessage();
        }
    }
}

// Cargar Cuentas por Cobrar
$cuentas = $pdo->query("SELECT * FROM cuentas_cobrar ORDER BY estatus DESC, fecha_emision DESC")->fetchAll(PDO::FETCH_ASSOC);
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
                        <th class="text-center">Acción</th>
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
                                <td>#<?= $c['salida_id'] ?></td>
                                <td class="text-end fw-bold text-danger">$<?= number_format($c['monto'], 2) ?></td>
                                <td class="text-center">
                                    <?php if ($c['estatus'] === 'pendiente'): ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Cobrado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($c['estatus'] === 'pendiente'): ?>
                                        <form method="POST" action="cuentas_cobrar.php" class="d-inline-flex gap-2">
                                            <input type="hidden" name="liquidar_cxc" value="1">
                                            <input type="hidden" name="cxc_id" value="<?= $c['id'] ?>">
                                            <select name="metodo_pago" class="form-select form-select-sm" style="width: 140px;">
                                                <option value="efectivo">Efectivo</option>
                                                <option value="transferencia">Transferencia</option>
                                                <option value="tarjeta">Tarjeta</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-cash me-1"></i> Cobrar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small class="text-muted">Cobrado el <?= date('d/m/Y', strtotime($c['fecha_cobro'])) ?></small>
                                    <?php endif; ?>
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
