<?php 
require_once 'includes/header.php'; 

// Registrar Cuenta por Pagar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cuenta'])) {
    $prov = $_POST['proveedor_cliente'];
    $desc = $_POST['descripcion'];
    $monto = floatval($_POST['monto_total']);
    $fIngreso = $_POST['fecha_ingreso'];
    $fPromesa = $_POST['fecha_promesa_pago'];

    $stmt = $pdo->prepare("INSERT INTO cuentas_por_pagar (proveedor_cliente, descripcion, monto_total, fecha_ingreso, fecha_promesa_pago) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$prov, $desc, $monto, $fIngreso, $fPromesa]);
}

// Procesar Pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pagar_cuenta'])) {
    $id = $_POST['id_cuenta'];
    $forma = $_POST['forma_pago'];
    $pagador = $_POST['pagado_por'];
    $montoPago = floatval($_POST['monto_pago']);

    $cuenta = $pdo->query("SELECT * FROM cuentas_por_pagar WHERE id = $id")->fetch();
    $nuevoPagado = $cuenta['monto_pagado'] + $montoPago;
    $nuevoEstado = ($nuevoPagado >= $cuenta['monto_total']) ? 'liquidado' : 'parcial';

    $stmt = $pdo->prepare("UPDATE cuentas_por_pagar SET monto_pagado = ?, estado = ?, forma_pago = ?, pagado_por = ?, fecha_hora_pago = NOW() WHERE id = ?");
    $stmt->execute([$nuevoPagado, $nuevoEstado, $forma, $pagador, $id]);
}

$cuentas = $pdo->query("SELECT *, DATEDIFF(fecha_promesa_pago, CURDATE()) as dias_restantes FROM cuentas_por_pagar ORDER BY fecha_promesa_pago ASC")->fetchAll();
?>

<h2>Cuentas por Pagar (Proveedores / Deudas)</h2>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <h5>Registrar Nueva Obligación / Deuda</h5>
        <form method="POST" class="row g-2 mt-1">
            <input type="hidden" name="crear_cuenta" value="1">
            <div class="col-md-3"><input type="text" name="proveedor_cliente" placeholder="Proveedor / Acreedor" class="form-control" required></div>
            <div class="col-md-3"><input type="text" name="descripcion" placeholder="Descripción del producto/servicio" class="form-control" required></div>
            <div class="col-md-2"><input type="number" step="0.01" name="monto_total" placeholder="Monto Total" class="form-control" required></div>
            <div class="col-md-2"><label class="small">Fecha Ingreso</label><input type="date" name="fecha_ingreso" class="form-control" required></div>
            <div class="col-md-2"><label class="small">Fecha Promesa</label><input type="date" name="fecha_promesa_pago" class="form-control" required></div>
            <div class="col-md-12 mt-2"><button type="submit" class="btn btn-warning w-100"><i class="bi bi-clock-history"></i> Registrar Deuda</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Proveedor</th>
                    <th>Monto Total</th>
                    <th>Pagado</th>
                    <th>Pendiente</th>
                    <th>Promesa de Pago</th>
                    <th>Alarma / Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cuentas as $c): 
                    $pendiente = $c['monto_total'] - $c['monto_pagado'];
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['proveedor_cliente']) ?></strong><br><small><?= htmlspecialchars($c['descripcion']) ?></small></td>
                    <td>$<?= number_format($c['monto_total'], 2) ?></td>
                    <td>$<?= number_format($c['monto_pagado'], 2) ?></td>
                    <td class="text-danger fw-bold">$<?= number_format($pendiente, 2) ?></td>
                    <td><?= $c['fecha_promesa_pago'] ?></td>
                    <td>
                        <?php if ($c['estado'] == 'liquidado'): ?>
                            <span class="badge bg-success">LIQUIDADO</span>
                        <?php elseif ($c['dias_restantes'] < 0): ?>
                            <span class="badge bg-danger">VENCIDO (hace <?= abs($c['dias_restantes']) ?> días)</span>
                        <?php elseif ($c['dias_restantes'] <= 3): ?>
                            <span class="badge bg-warning text-dark">ALERTA (Vence en <?= $c['dias_restantes'] ?> días)</span>
                        <?php else: ?>
                            <span class="badge bg-info text-dark">A tiempo (<?= $c['dias_restantes'] ?> días)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($c['estado'] != 'liquidado'): ?>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalPago<?= $c['id'] ?>">Pagar</button>
                        <?php else: ?>
                            <small class="text-muted">Pagado por <?= htmlspecialchars($c['pagado_por']) ?> (<?= $c['forma_pago'] ?>)</small>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Modal Abonar/Pagar -->
                <div class="modal fade" id="modalPago<?= $c['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" class="modal-content">
                            <input type="hidden" name="pagar_cuenta" value="1">
                            <input type="hidden" name="id_cuenta" value="<?= $c['id'] ?>">
                            <div class="modal-header">
                                <h5 class="modal-title">Registrar Pago a <?= htmlspecialchars($c['proveedor_cliente']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Monto Pendiente: <strong>$<?= number_format($pendiente, 2) ?></strong></p>
                                <div class="mb-2"><label>Monto a Abonar/Pagar</label><input type="number" step="0.01" name="monto_pago" max="<?= $pendiente ?>" value="<?= $pendiente ?>" class="form-control" required></div>
                                <div class="mb-2">
                                    <label>Forma de Pago</label>
                                    <select name="forma_pago" class="form-select">
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta / Transferencia</option>
                                    </select>
                                </div>
                                <div class="mb-2"><label>Nombre de quien realizó el pago</label><input type="text" name="pagado_por" class="form-control" required></div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success">Confirmar Pago</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
