<?php
require_once 'includes/header.php';

$mensajeExito = '';
$mensajeError = '';

$dirFotos = __DIR__ . '/uploads/productos/';
$dirFacturas = __DIR__ . '/uploads/facturas_compras/';

// =========================================================================
// 1. ELIMINAR PRODUCTO
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_producto'])) {
    $idEliminar = intval($_POST['producto_id'] ?? 0);

    if ($idEliminar > 0) {
        try {
            // Consultar si tiene imagen para borrarla si no es la default
            $stmtImg = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
            $stmtImg->execute([$idEliminar]);
            $prod = $stmtImg->fetch(PDO::FETCH_ASSOC);

            if ($prod && !empty($prod['imagen']) && $prod['imagen'] !== 'uploads/productos/default.png') {
                $fileImg = __DIR__ . '/' . $prod['imagen'];
                if (file_exists($fileImg)) {
                    @unlink($fileImg);
                }
            }

            // Eliminar producto de la BDD
            $stmtDel = $pdo->prepare("DELETE FROM productos WHERE id = ?");
            $stmtDel->execute([$idEliminar]);

            $mensajeExito = "Producto eliminado correctamente del inventario.";
        } catch (\PDOException $e) {
            $mensajeError = "Error al eliminar el producto (puede tener registros asociados en entradas/salidas): " . $e->getMessage();
        }
    }
}

// =========================================================================
// 2. EDITAR / MODIFICAR PRODUCTO
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_producto'])) {
    $idEditar      = intval($_POST['producto_id'] ?? 0);
    $codigo        = trim($_POST['codigo'] ?? '');
    $nombre        = trim($_POST['nombre'] ?? '');
    $tipoUnidad    = trim($_POST['tipo_unidad'] ?? 'pieza');
    $precioVenta   = floatval($_POST['precio_venta'] ?? 0);
    $costoUnitario = floatval($_POST['costo_unitario'] ?? 0);
    $stockActual   = floatval($_POST['stock_actual'] ?? 0);
    $stockMinimo   = floatval($_POST['stock_minimo'] ?? 5);

    if ($idEditar > 0 && !empty($codigo) && !empty($nombre)) {
        try {
            // Obtener datos actuales
            $stmtActual = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
            $stmtActual->execute([$idEditar]);
            $prodActual = $stmtActual->fetch(PDO::FETCH_ASSOC);
            $fotoUrl = $prodActual['imagen'] ?? 'uploads/productos/default.png';

            // Actualizar fotografía si se subió una nueva
            if (!empty($_FILES['fotografia']['name'])) {
                if (!is_dir($dirFotos)) mkdir($dirFotos, 0755, true);
                $ext = strtolower(pathinfo($_FILES['fotografia']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $nomFoto = 'prod_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['fotografia']['tmp_name'], $dirFotos . $nomFoto)) {
                        // Eliminar imagen anterior si no es la por defecto
                        if (!empty($fotoUrl) && $fotoUrl !== 'uploads/productos/default.png') {
                            @unlink(__DIR__ . '/' . $fotoUrl);
                        }
                        $fotoUrl = 'uploads/productos/' . $nomFoto;
                    }
                }
            }

            $stmtUp = $pdo->prepare("UPDATE productos SET codigo = ?, nombre = ?, tipo_unidad = ?, costo_unitario = ?, precio_venta = ?, stock_actual = ?, stock_minimo = ?, imagen = ? WHERE id = ?");
            $stmtUp->execute([$codigo, $nombre, $tipoUnidad, $costoUnitario, $precioVenta, $stockActual, $stockMinimo, $fotoUrl, $idEditar]);

            $mensajeExito = "Producto '{$nombre}' actualizado correctamente.";
        } catch (\PDOException $e) {
            $mensajeError = "Error al actualizar el producto: " . $e->getMessage();
        }
    } else {
        $mensajeError = "El Código y el Nombre son obligatorios para modificar el producto.";
    }
}

// =========================================================================
// 3. REGISTRAR NUEVO PRODUCTO + PRIMERA ENTRADA (COMPRA)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_producto_completo'])) {
    $codigo        = trim($_POST['codigo'] ?? '');
    $nombre        = trim($_POST['nombre'] ?? '');
    $tipoUnidad    = trim($_POST['tipo_unidad'] ?? 'pieza');
    $precioVenta   = floatval($_POST['precio_venta'] ?? 0);
    $stockMinimo   = floatval($_POST['stock_minimo'] ?? 5);
    $costoUnitario = floatval($_POST['costo_unitario'] ?? 0);
    $stockInicial  = floatval($_POST['stock_inicial'] ?? 0);

    $proveedorId     = !empty($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;
    $estatusPago     = $_POST['estatus_pago'] ?? 'pagado';
    $fechaVencimiento= !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
    $tipoComprobante = $_POST['tipo_comprobante'] ?? 'sin_comprobante';
    $montoTotal      = $stockInicial * $costoUnitario;

    $fotoUrl = 'uploads/productos/default.png';
    $comprobanteUrl = null;

    if (!empty($codigo) && !empty($nombre)) {
        try {
            $pdo->beginTransaction();

            if (!empty($_FILES['fotografia']['name'])) {
                if (!is_dir($dirFotos)) mkdir($dirFotos, 0755, true);
                $ext = strtolower(pathinfo($_FILES['fotografia']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $nomFoto = 'prod_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['fotografia']['tmp_name'], $dirFotos . $nomFoto)) {
                        $fotoUrl = 'uploads/productos/' . $nomFoto;
                    }
                }
            }

            if (!empty($_FILES['comprobante']['name'])) {
                if (!is_dir($dirFacturas)) mkdir($dirFacturas, 0755, true);
                $ext = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp'])) {
                    $nomComp = 'compra_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $dirFacturas . $nomComp)) {
                        $comprobanteUrl = 'uploads/facturas_compras/' . $nomComp;
                    }
                }
            }

            $stmtProd = $pdo->prepare("INSERT INTO productos (codigo, nombre, tipo_unidad, costo_unitario, precio_venta, stock_actual, stock_minimo, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtProd->execute([$codigo, $nombre, $tipoUnidad, $costoUnitario, $precioVenta, $stockInicial, $stockMinimo, $fotoUrl]);
            $productoId = $pdo->lastInsertId();

            if ($stockInicial > 0) {
                $fechaPago = ($estatusPago === 'pagado') ? date('Y-m-d H:i:s') : null;

                $stmtEnt = $pdo->prepare("INSERT INTO entradas_inventario (producto_id, proveedor_id, cantidad, costo_unitario, monto_total, estatus_pago, tipo_comprobante, comprobante_url, fecha_pago) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtEnt->execute([$productoId, $proveedorId, $stockInicial, $costoUnitario, $montoTotal, $estatusPago, $tipoComprobante, $comprobanteUrl, $fechaPago]);
                $entradaId = $pdo->lastInsertId();

                if ($estatusPago === 'pagado') {
                    $concepto = "Compra inicial: {$stockInicial} unid. de {$nombre}";
                    $stmtEgr = $pdo->prepare("INSERT INTO egresos (entrada_id, proveedor_id, concepto, monto, metodo_pago, tipo_comprobante, comprobante_url, fecha_pago) VALUES (?, ?, ?, ?, 'efectivo', ?, ?, NOW())");
                    $stmtEgr->execute([$entradaId, $proveedorId, $concepto, $montoTotal, $tipoComprobante, $comprobanteUrl]);
                } else {
                    $concepto = "Compra a crédito (inicial): {$stockInicial} unid. de {$nombre}";
                    $stmtCxP = $pdo->prepare("INSERT INTO cuentas_pagar (entrada_id, proveedor_id, concepto, monto, estatus, comprobante_url, fecha_vencimiento) VALUES (?, ?, ?, ?, 'pendiente', ?, ?)");
                    $stmtCxP->execute([$entradaId, $proveedorId, $concepto, $montoTotal, $comprobanteUrl, $fechaVencimiento]);
                }
            }

            $pdo->commit();
            $mensajeExito = "Producto '{$nombre}' registrado exitosamente.";
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $mensajeError = "Error al guardar el producto: " . $e->getMessage();
        }
    } else {
        $mensajeError = "El Código y el Nombre del producto son obligatorios.";
    }
}

// Cargar Proveedores para el desplegable
try {
    $proveedores = $pdo->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $proveedores = [];
}

// Cargar Productos para el listado
try {
    $productos = $pdo->query("SELECT * FROM productos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $productos = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam text-primary me-2"></i> Inventario e Insumos</h2>
    <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
        <i class="bi bi-plus-lg me-1"></i> + Nuevo Producto
    </button>
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

<!-- LISTA DE PRODUCTOS -->
<div class="card shadow-sm">
    <div class="card-header bg-dark text-white fw-bold">
        <i class="bi bi-list-stars me-1"></i> Existencias Actuales
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Foto</th>
                        <th>Código</th>
                        <th>Nombre / Descripción</th>
                        <th>Unidad</th>
                        <th class="text-end">Costo U.</th>
                        <th class="text-end">P. Venta</th>
                        <th class="text-center">Stock Actual</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr><td colspan="9" class="text-center py-3 text-muted">No hay productos registrados en el inventario.</td></tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="Foto" style="width: 40px; height: 40px; object-fit: cover;" class="rounded border">
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($p['codigo']) ?></td>
                                <td><?= htmlspecialchars($p['nombre']) ?></td>
                                <td><?= htmlspecialchars($p['tipo_unidad']) ?></td>
                                <td class="text-end">$<?= number_format($p['costo_unitario'], 2) ?></td>
                                <td class="text-end">$<?= number_format($p['precio_venta'], 2) ?></td>
                                <td class="text-center fw-bold fs-6">
                                    <?= number_format($p['stock_actual'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($p['stock_actual'] <= $p['stock_minimo']): ?>
                                        <span class="badge bg-danger">Bajo Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Óptimo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning me-1" 
                                            onclick='abrirModalEditar(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' 
                                            title="Editar producto">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" action="inventario.php" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este producto del inventario? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="eliminar_producto" value="1">
                                        <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar producto">
                                            <i class="bi bi-trash"></i>
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

<!-- MODAL: EDITAR PRODUCTO -->
<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="inventario.php" enctype="multipart/form-data">
                <input type="hidden" name="modificar_producto" value="1">
                <input type="hidden" name="producto_id" id="edit_id">

                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Modificar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Código (*):</label>
                            <input type="text" name="codigo" id="edit_codigo" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Nombre / Descripción (*):</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tipo Unidad:</label>
                            <select name="tipo_unidad" id="edit_tipo_unidad" class="form-select">
                                <option value="Pieza">Pieza / Unidad</option>
                                <option value="Caja">Caja</option>
                                <option value="Paquete">Paquete</option>
                                <option value="Kilo">Kilo</option>
                                <option value="Litro">Litro</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Costo Unitario ($):</label>
                            <input type="number" step="0.01" name="costo_unitario" id="edit_costo_unitario" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Precio Venta ($):</label>
                            <input type="number" step="0.01" name="precio_venta" id="edit_precio_venta" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Stock Actual:</label>
                            <input type="number" step="0.01" name="stock_actual" id="edit_stock_actual" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Stock Mínimo:</label>
                            <input type="number" step="0.01" name="stock_minimo" id="edit_stock_minimo" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Cambiar Fotografía (Opcional):</label>
                            <input type="file" name="fotografia" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="bi bi-check-circle me-1"></i> Actualizar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: NUEVO PRODUCTO CON DATOS DE COMPRA -->
<div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="inventario.php" enctype="multipart/form-data">
                <input type="hidden" name="guardar_producto_completo" value="1">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i> Nuevo Producto o Insumo (Alta + Compra)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <!-- SECCIÓN 1: DATOS DEL PRODUCTO -->
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-1"></i> 1. Información General del Producto</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Código de Identificación (*):</label>
                            <input type="text" name="codigo" class="form-control" placeholder="Ej. PROD-001" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Nombre o Descripción (*):</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej. Papel Bond Tamaño Carta" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tipo Unidad:</label>
                            <select name="tipo_unidad" class="form-select">
                                <option value="Pieza">Pieza / Unidad</option>
                                <option value="Caja">Caja</option>
                                <option value="Paquete">Paquete</option>
                                <option value="Kilo">Kilo</option>
                                <option value="Litro">Litro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Precio Venta ($):</label>
                            <input type="number" step="0.01" name="precio_venta" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Stock Mínimo (Alerta):</label>
                            <input type="number" step="0.01" name="stock_minimo" class="form-control" value="5">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fotografía del Producto:</label>
                            <input type="file" name="fotografia" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <!-- SECCIÓN 2: DATOS DE LA PRIMERA COMPRA / ENTRADA -->
                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i class="bi bi-cart-plus me-1"></i> 2. Datos de Compra / Entrada Inicial</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Proveedor / Razón Social:</label>
                            <select name="proveedor_id" class="form-select">
                                <option value="">-- Sin Proveedor / Mostrador --</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Stock Inicial (Cantidad):</label>
                            <input type="number" step="0.01" min="0" name="stock_inicial" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Costo Unitario ($):</label>
                            <input type="number" step="0.01" min="0" name="costo_unitario" class="form-control" value="0.00" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Estatus del Pago:</label>
                            <select name="estatus_pago" id="estatusPagoSelect" class="form-select" onchange="toggleCreditoCampos(this.value)">
                                <option value="pagado">Pagado (Contado -> Egresos)</option>
                                <option value="pendiente">Pendiente (A Crédito -> CxP)</option>
                            </select>
                        </div>

                        <div class="col-md-4" id="divFechaVencimiento" style="display: none;">
                            <label class="form-label fw-bold text-danger">Promesa de Pago (Vencimiento):</label>
                            <input type="date" name="fecha_vencimiento" class="form-control border-danger">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipo Comprobante:</label>
                            <select name="tipo_comprobante" class="form-select">
                                <option value="sin_comprobante">Sin Comprobante / Nota</option>
                                <option value="factura">Factura Fiscal (CFDI)</option>
                                <option value="remision">Nota de Remisión / Ticket</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Comprobante de Compra (PDF, XML, Foto/Ticket):</label>
                            <input type="file" name="comprobante" class="form-control" accept=".pdf,.xml,.jpg,.jpeg,.png,.webp">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="bi bi-check-circle me-1"></i> Registrar Producto y Compra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleCreditoCampos(val) {
    const divVenc = document.getElementById('divFechaVencimiento');
    if (val === 'pendiente') {
        divVenc.style.display = 'block';
    } else {
        divVenc.style.display = 'none';
    }
}

function abrirModalEditar(prod) {
    document.getElementById('edit_id').value = prod.id;
    document.getElementById('edit_codigo').value = prod.codigo;
    document.getElementById('edit_nombre').value = prod.nombre;
    document.getElementById('edit_tipo_unidad').value = prod.tipo_unidad;
    document.getElementById('edit_costo_unitario').value = prod.costo_unitario;
    document.getElementById('edit_precio_venta').value = prod.precio_venta;
    document.getElementById('edit_stock_actual').value = prod.stock_actual;
    document.getElementById('edit_stock_minimo').value = prod.stock_minimo;

    const modal = new bootstrap.Modal(document.getElementById('modalEditarProducto'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
