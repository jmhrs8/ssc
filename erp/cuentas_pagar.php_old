<?php
require_once 'includes/header.php';

$mensajeExito = '';
$mensajeError = '';

// 1. CREAR NUEVA CUENTA POR PAGAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cuenta'])) {
    $proveedorId = !empty($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;
    $concepto    = trim($_POST['concepto'] ?? '');
    $monto       = floatval($_POST['monto'] ?? 0);
    $comprobanteUrl = null;

    if ($monto > 0 && !empty($concepto)) {
        try {
            if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
                $permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'xml'];
                
                if (in_array($ext, $permitidas)) {
                    $dirSubida = 'uploads/comprobantes/';
                    if (!is_dir($dirSubida)) {
                        mkdir($dirSubida, 0777, true);
                    }
                    $nombreArchivo = 'cxp_' . time() . '_' . uniqid() . '.' . $ext;
                    $comprobanteUrl = $dirSubida . $nombreArchivo;
                    move_uploaded_file($_FILES['comprobante']['tmp_name'], $comprobanteUrl);
                }
            }

            $stmtIns = $pdo->prepare("INSERT INTO cuentas_pagar 
                (proveedor_id, concepto, monto, estatus, comprobante_url) 
                VALUES (?, ?, ?, 'pendiente', ?)");
            $stmtIns->execute([$proveedorId, $concepto, $monto, $comprobanteUrl]);

            $mensajeExito = "Cuenta por pagar registrada exitosamente.";
        } catch (\PDOException $e) {
            $mensajeError = "Error al registrar la cuenta por pagar: " . $e->getMessage();
        }
    } else {
        $mensajeError = "Por favor, ingresa un concepto válido y un monto mayor a cero.";
    }
}

// 2. MODIFICAR/EDITAR CUENTA POR PAGAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_cuenta'])) {
    $cuentaId    = intval($_POST['cuenta_id'] ?? 0);
    $proveedorId = !empty($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;
    $concepto    = trim($_POST['concepto'] ?? '');
    $monto       = floatval($_POST['monto'] ?? 0);

    if ($cuentaId > 0 && $monto > 0 && !empty($concepto)) {
        try {
            // Verificar que siga pendiente antes de editar
            $stmtVerif = $pdo->prepare("SELECT estatus FROM cuentas_pagar WHERE id = ?");
            $stmtVerif->execute([$cuentaId]);
            $estatusActual = $stmtVerif->fetchColumn();

            if ($estatusActual === 'pagado') {
                throw new Exception("No se puede editar una cuenta que ya ha sido pagada.");
            }

            $stmtUpd = $pdo->prepare("UPDATE cuentas_pagar 
                                      SET proveedor_id = ?, concepto = ?, monto = ? 
                                      WHERE id = ?");
            $stmtUpd->execute([$proveedorId, $concepto, $monto, $cuentaId]);

            $mensajeExito = "La cuenta por pagar se actualizó correctamente.";
        } catch (Exception $e) {
            $mensajeError = "Error al actualizar la cuenta: " . $e->getMessage();
        }
    } else {
        $mensajeError = "Datos inválidos para actualizar la cuenta.";
    }
}

// 3. PROCESAR PAGO DE CUENTA POR PAGAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pagar_cuenta'])) {
    $cuentaId   = intval($_POST['cuenta_id'] ?? 0);
    $metodoPago = $_POST['metodo_pago'] ?? 'efectivo';

    if ($cuentaId > 0) {
        try {
            $pdo->beginTransaction();

            $stmtC = $pdo->prepare("SELECT * FROM cuentas_pagar WHERE id = ? FOR UPDATE");
            $stmtC->execute([$cuentaId]);
            $cuenta = $stmtC->fetch(PDO::FETCH_ASSOC);

            if (!$cuenta) {
                throw new Exception("La cuenta por pagar no existe.");
            }

            if ($cuenta['estatus'] === 'pagado') {
                throw new Exception("Esta cuenta ya ha sido pagada previamente.");
            }

            $stmtUpdC = $pdo->prepare("UPDATE cuentas_pagar SET estatus = 'pagado', fecha_pago = NOW() WHERE id = ?");
            $stmtUpdC->execute([$cuentaId]);

            if (!empty($cuenta['entrada_id'])) {
                $stmtUpdE = $pdo->prepare("UPDATE entradas_inventario SET estatus_pago = 'pagado', fecha_pago = NOW() WHERE id = ?");
                $stmtUpdE->execute([$cuenta['entrada_id']]);
            }

            $stmtEgr = $pdo->prepare("INSERT INTO egresos
                (entrada_id, proveedor_id, concepto, monto, metodo_pago, tipo_comprobante, comprobante_url, fecha_pago)
                VALUES (?, ?, ?, ?, ?, 'sin_comprobante', ?, NOW())");
            $stmtEgr->execute([
                $cuenta['entrada_id'] ?? null,
                $cuenta['proveedor_id'] ?? null,
                "Pago CxP: " . $cuenta['concepto'],
                $cuenta['monto'],
                $metodoPago,
                $cuenta['comprobante_url'] ?? null
            ]);

            $pdo->commit();
            $mensajeExito = "Cuenta liquidada exitosamente y registrada en Egresos.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $mensajeError = "Error al procesar el pago: " . $e->getMessage();
        }
    } else {
        $mensajeError = "ID de cuenta inválido.";
    }
}

// Cargar catálogo de proveedores
try {
    $proveedores = $pdo->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $proveedores = [];
}

// Cargar Cuentas por Pagar
try {
    $sqlCxP = "SELECT cp.*, pr.nombre AS proveedor_nombre
               FROM cuentas_pagar cp
               LEFT JOIN proveedores pr ON cp.proveedor_id = pr.id
               ORDER BY cp.estatus DESC, cp.id DESC";
    $cuentas = $pdo->query($sqlCxP)->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $cuentas = [];
    $mensajeError = "Error al consultar cuentas por pagar: " . $e->getMessage();
}

// Resumen financiero
$totalPendiente = 0;
$totalPagado = 0;
foreach ($cuentas as $c) {
    if (($c['estatus'] ?? '') === 'pendiente') {
        $totalPendiente += floatval($c['monto']);
    } else {
        $totalPagado += floatval($c['monto']);
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-folder-fill text-warning me-2"></i> Cuentas por Pagar</h2>
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

<!-- FORMULARIO DE NUEVA CUENTA POR PAGAR -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white fw-bold">
        <i class="bi bi-plus-circle me-1"></i> Registrar Nueva Cuenta por Pagar
    </div>
    <div class="card-body">
        <form method="POST" action="cuentas_pagar.php" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="crear_cuenta" value="1">

            <div class="col-md-3">
                <label class="form-label fw-bold">Proveedor (Opcional):</label>
                <select name="proveedor_id" class="form-select">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Concepto / Descripción:</label>
                <input type="text" name="concepto" class="form-control" placeholder="Ej. Factura A-123 / Compra de insumos" required>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold">Monto Total ($):</label>
                <input type="number" step="0.01" min="0.01" name="monto" class="form-control" placeholder="0.00" required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Comprobante (Opcional):</label>
                <input type="file" name="comprobante" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.xml">
            </div>

            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar Cuenta por Pagar</button>
            </div>
        </form>
    </div>
</div>

<!-- TARJETAS DE RESUMEN FINANCIERO -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-danger text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Total Pendiente por Pagar</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalPendiente, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Total Liquidado</h6>
                <h3 class="fw-bold mb-0">$<?= number_format($totalPagado, 2) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- TABLA DE HISTORIAL DE CUENTAS POR PAGAR -->
<div class="card shadow-sm">
    <div class="card-header bg-dark text-white fw-bold">
        Listado de Cuentas por Pagar
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha Registro</th>
                        <th>Proveedor</th>
                        <th>Concepto</th>
                        <th class="text-end">Monto Total</th>
                        <th class="text-center">Estatus</th>
                        <th class="text-center">Comprobante</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cuentas)): ?>
                        <tr><td colspan="7" class="text-center py-3 text-muted">No hay cuentas por pagar registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cuentas as $c): ?>
                            <?php 
                                $fechaMostrar = $c['fecha_registro'] ?? $c['fecha_emision'] ?? $c['fecha'] ?? null;
                            ?>
                            <tr>
                                <td><?= $fechaMostrar ? date('d/m/Y H:i', strtotime($fechaMostrar)) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($c['proveedor_nombre'] ?? 'Sin Proveedor') ?></td>
                                <td><?= htmlspecialchars($c['concepto'] ?? 'Compra a crédito') ?></td>
                                <td class="text-end fw-bold">$<?= number_format($c['monto'], 2) ?></td>
                                <td class="text-center">
                                    <?php if (($c['estatus'] ?? '') === 'pagado'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Pagado</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($c['comprobante_url']) && file_exists(__DIR__ . '/' . $c['comprobante_url'])): ?>
                                        <a href="<?= htmlspecialchars($c['comprobante_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-earmark-arrow-down"></i> Ver
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin archivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (($c['estatus'] ?? '') === 'pendiente'): ?>
                                        <!-- Botón Editar -->
                                        <button class="btn btn-sm btn-outline-warning me-1" title="Editar Cuenta" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $c['id'] ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Botón Liquidar -->
                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPagar<?= $c['id'] ?>">
                                            <i class="bi bi-cash-stack me-1"></i> Liquidar
                                        </button>

                                        <!-- MODAL EDITAR -->
                                        <div class="modal fade" id="modalEditar<?= $c['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-start">
                                                    <form method="POST" action="cuentas_pagar.php">
                                                        <input type="hidden" name="editar_cuenta" value="1">
                                                        <input type="hidden" name="cuenta_id" value="<?= $c['id'] ?>">
                                                        
                                                        <div class="modal-header bg-warning text-dark">
                                                            <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i> Editar Cuenta por Pagar</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Proveedor:</label>
                                                                <select name="proveedor_id" class="form-select">
                                                                    <option value="">-- Seleccionar --</option>
                                                                    <?php foreach ($proveedores as $prov): ?>
                                                                        <option value="<?= $prov['id'] ?>" <?= ($prov['id'] == $c['proveedor_id']) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($prov['nombre']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Concepto:</label>
                                                                <input type="text" name="concepto" class="form-control" value="<?= htmlspecialchars($c['concepto'] ?? '') ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Monto Total ($):</label>
                                                                <input type="number" step="0.01" min="0.01" name="monto" class="form-control" value="<?= floatval($c['monto']) ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-warning"><i class="bi bi-check-circle me-1"></i> Guardar Cambios</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- MODAL PAGO -->
                                        <div class="modal fade" id="modalPagar<?= $c['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-start">
                                                    <form method="POST" action="cuentas_pagar.php">
                                                        <input type="hidden" name="pagar_cuenta" value="1">
                                                        <input type="hidden" name="cuenta_id" value="<?= $c['id'] ?>">
                                                        <div class="modal-header bg-success text-white">
                                                            <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i> Registrar Pago de Cuenta</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Concepto:</strong> <?= htmlspecialchars($c['concepto'] ?? 'Pago de factura') ?></p>
                                                            <p><strong>Monto a pagar:</strong> <span class="fs-5 text-success">$<?= number_format($c['monto'], 2) ?></span></p>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Método de Pago:</label>
                                                                <select name="metodo_pago" class="form-select" required>
                                                                    <option value="efectivo">Efectivo</option>
                                                                    <option value="transferencia">Transferencia Bancaria</option>
                                                                    <option value="tarjeta">Tarjeta Débito/Crédito</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Confirmar Pago</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Liquidado</span>
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
