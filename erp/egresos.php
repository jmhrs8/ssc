<?php
require_once 'includes/header.php';

$mensajeExito = '';
$mensajeError = '';

// --- PROCESAR ELIMINACIÓN DE REGISTRO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_eliminar'])) {
    $idEliminar = intval($_POST['registro_id'] ?? 0);
    $origen     = $_POST['origen'] ?? '';

    if ($idEliminar > 0) {
        try {
            if ($origen === 'egreso') {
                $stmtDel = $pdo->prepare("DELETE FROM egresos WHERE id = ?");
                $stmtDel->execute([$idEliminar]);
            } else if ($origen === 'entrada') {
                $stmtDel = $pdo->prepare("DELETE FROM entradas_inventario WHERE id = ?");
                $stmtDel->execute([$idEliminar]);
            }
            $mensajeExito = "El registro #{$idEliminar} ({$origen}) fue eliminado correctamente.";
        } catch (\PDOException $e) {
            $mensajeError = "Error al eliminar el registro: " . $e->getMessage();
        }
    }
}

// --- PROCESAR EDICIÓN DE REGISTRO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_editar'])) {
    $idEditar   = intval($_POST['registro_id'] ?? 0);
    $origen     = $_POST['origen'] ?? '';
    $concepto   = trim($_POST['concepto'] ?? '');
    $metodoPago = $_POST['metodo_pago'] ?? 'efectivo';
    $montoTotal = floatval($_POST['monto_total'] ?? 0);

    if ($idEditar > 0 && $montoTotal >= 0) {
        try {
            if ($origen === 'egreso') {
                $stmtUpd = $pdo->prepare("UPDATE egresos SET
                            concepto = ?,
                            metodo_pago = ?,
                            monto = ?
                          WHERE id = ?");
                $stmtUpd->execute([$concepto, $metodoPago, $montoTotal, $idEditar]);
            } else if ($origen === 'entrada') {
                $stmtUpd = $pdo->prepare("UPDATE entradas_inventario SET
                            monto_total = ?
                          WHERE id = ?");
                $stmtUpd->execute([$montoTotal, $idEditar]);
            }

            $mensajeExito = "El registro #{$idEditar} ({$origen}) fue actualizado con éxito.";
        } catch (\PDOException $e) {
            $mensajeError = "Error al actualizar el registro: " . $e->getMessage();
        }
    } else {
        $mensajeError = "Por favor completa los campos requeridos con valores válidos.";
    }
}

// --- CONSULTA UNIFICADA CON ESQUEMA REAL ---
$sqlUnificado = "
    SELECT
        'egreso' AS origen,
        e.id,
        COALESCE(e.fecha_pago, NOW()) AS fecha,
        e.concepto,
        COALESCE(e.metodo_pago, 'efectivo') AS metodo_pago,
        (e.monto / 1.16) AS subtotal,
        (e.monto - (e.monto / 1.16)) AS iva,
        e.monto AS total,
        e.comprobante_url
    FROM egresos e

    UNION ALL

    SELECT
        'entrada' AS origen,
        ei.id,
        COALESCE(ei.fecha_pago, NOW()) AS fecha,
        CONCAT('Compra Inventario: ', COALESCE(p.nombre, 'Producto'), ' (Prov: ', COALESCE(pr.nombre, 'General'), ')') AS concepto,
        'efectivo' AS metodo_pago,
        (ei.monto_total / 1.16) AS subtotal,
        (ei.monto_total - (ei.monto_total / 1.16)) AS iva,
        COALESCE(ei.monto_total, 0.00) AS total,
        ei.comprobante_url
    FROM entradas_inventario ei
    LEFT JOIN productos p ON ei.producto_id = p.id
    LEFT JOIN proveedores pr ON ei.proveedor_id = pr.id
    WHERE ei.estatus_pago = 'pagado' OR ei.estatus_pago IS NULL
";

// Cargar Historial Unificado
try {
    $sqlFinal = "SELECT * FROM ({$sqlUnificado}) AS reporte_egresos ORDER BY fecha DESC LIMIT 100";
    $egresos = $pdo->query($sqlFinal)->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $egresos = [];
    $mensajeError = "Error al consultar los egresos: " . $e->getMessage();
}

// Calcular Totales por Método de Pago
try {
    $sqlTotales = "
        SELECT metodo_pago, SUM(total) AS total_metodo
        FROM ({$sqlUnificado}) AS reporte_totales
        GROUP BY metodo_pago
    ";
    $totalesMetodo = $pdo->query($sqlTotales)->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (\PDOException $e) {
    $totalesMetodo = [];
}

$totalEfectivo = $totalesMetodo['efectivo'] ?? 0.00;
$totalTransfer = $totalesMetodo['transferencia'] ?? 0.00;
$totalTarjeta  = $totalesMetodo['tarjeta'] ?? 0.00;
$totalEgresos  = array_sum($totalesMetodo);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-wallet2 text-danger me-2"></i> Reporte de Egresos y Compras</h2>
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

<!-- TARJETAS DE RESUMEN -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-danger text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Total Egresos</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalEgresos, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Efectivo</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalEfectivo, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Transferencias</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalTransfer, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-dark shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Tarjetas</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalTarjeta, 2) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- TABLA DE DETALLE -->
<div class="card shadow-sm">
    <div class="card-header bg-dark text-white fw-bold">
        <i class="bi bi-journal-text me-1"></i> Salidas de Dinero Registradas
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha Pago</th>
                        <th>Concepto / Detalle</th>
                        <th class="text-center">Método Pago</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">IVA (16%)</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Comprobante</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($egresos)): ?>
                        <tr><td colspan="8" class="text-center py-3 text-muted">No se han registrado egresos aún.</td></tr>
                    <?php else: ?>
                        <?php foreach ($egresos as $eg): ?>
                            <tr>
                                <td><?= !empty($eg['fecha']) ? date('d/m/Y H:i', strtotime($eg['fecha'])) : 'N/A' ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($eg['concepto'] ?? 'Egreso General') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= ucfirst($eg['metodo_pago'] ?? 'Efectivo') ?></span>
                                </td>
                                <td class="text-end">$<?= number_format(floatval($eg['subtotal'] ?? 0), 2) ?></td>
                                <td class="text-end">$<?= number_format(floatval($eg['iva'] ?? 0), 2) ?></td>
                                <td class="text-end fw-bold text-danger">$<?= number_format(floatval($eg['total'] ?? 0), 2) ?></td>
                                <td class="text-center">
                                    <?php if (!empty($eg['comprobante_url']) && file_exists(__DIR__ . '/' . $eg['comprobante_url'])): ?>
                                        <a href="<?= htmlspecialchars($eg['comprobante_url']) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-file-earmark-pdf"></i> Ver
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin archivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditarRegistro"
                                                data-id="<?= $eg['id'] ?>"
                                                data-origen="<?= $eg['origen'] ?>"
                                                data-concepto="<?= htmlspecialchars($eg['concepto']) ?>"
                                                data-metodo="<?= $eg['metodo_pago'] ?>"
                                                data-total="<?= $eg['total'] ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <form method="POST" action="egresos.php" class="d-inline" onsubmit="return confirm('¿Confirmas eliminar este registro de egreso?');">
                                            <input type="hidden" name="accion_eliminar" value="1">
                                            <input type="hidden" name="registro_id" value="<?= $eg['id'] ?>">
                                            <input type="hidden" name="origen" value="<?= $eg['origen'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditarRegistro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="egresos.php">
                <input type="hidden" name="accion_editar" value="1">
                <input type="hidden" name="registro_id" id="edit_registro_id">
                <input type="hidden" name="origen" id="edit_origen">

                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Editar Registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Concepto (*):</label>
                        <input type="text" name="concepto" id="edit_concepto" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Método de Pago (*):</label>
                        <select name="metodo_pago" id="edit_metodo_pago" class="form-select" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Monto Total ($) (*):</label>
                        <input type="number" step="0.01" min="0" name="monto_total" id="edit_monto_total" class="form-control" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-check-lg me-1"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEditar = document.getElementById('modalEditarRegistro');
    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('edit_registro_id').value = button.getAttribute('data-id');
            document.getElementById('edit_origen').value = button.getAttribute('data-origen');
            document.getElementById('edit_concepto').value = button.getAttribute('data-concepto');
            document.getElementById('edit_metodo_pago').value = button.getAttribute('data-metodo');
            document.getElementById('edit_monto_total').value = button.getAttribute('data-total');
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
