<?php
require_once 'includes/header.php';

$mensajeExito = '';
$mensajeError = '';

// REGISTRAR PROVEEDOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_proveedor'])) {
    $nombre    = trim($_POST['nombre'] ?? '');
    $rfc       = trim($_POST['rfc'] ?? '');
    $contacto  = trim($_POST['contacto'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (!empty($nombre)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO proveedores (nombre, rfc, contacto, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $rfc, $contacto, $telefono, $email, $direccion]);
            $mensajeExito = "Proveedor registrado correctamente.";
        } catch (\PDOException $e) {
            $mensajeError = "Error al guardar el proveedor: " . $e->getMessage();
        }
    } else {
        $mensajeError = "El nombre del proveedor es obligatorio.";
    }
}

// OBTENER LISTA DE PROVEEDORES
try {
    $proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $proveedores = [];
    $mensajeError = "Error al consultar la tabla proveedores: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-truck text-primary me-2"></i> Gestión de Proveedores</h2>
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

<!-- FORMULARIO REGISTRO -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white fw-bold">
        <i class="bi bi-plus-circle me-1"></i> Registrar Nuevo Proveedor
    </div>
    <div class="card-body">
        <form method="POST" action="proveedores.php">
            <input type="hidden" name="guardar_proveedor" value="1">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Nombre / Razón Social (*):</label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Ej. Distribuidora MX">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">RFC:</label>
                    <input type="text" name="rfc" class="form-control" placeholder="XAXX010101000">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Persona de Contacto:</label>
                    <input type="text" name="contacto" class="form-control" placeholder="Ej. Lic. Juan Pérez">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Teléfono:</label>
                    <input type="text" name="telefono" class="form-control" placeholder="55 1234 5678">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Correo Electrónico:</label>
                    <input type="email" name="email" class="form-control" placeholder="contacto@empresa.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Dirección:</label>
                    <input type="text" name="direccion" class="form-control" placeholder="Calle, Número, Colonia, Ciudad">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="bi bi-save me-1"></i> Guardar Proveedor
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- LISTADO -->
<div class="card shadow-sm">
    <div class="card-header bg-dark text-white fw-bold">
        <i class="bi bi-list-ul me-1"></i> Lista de Proveedores Registrados
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre / Razón Social</th>
                        <th>RFC</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Dirección</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proveedores)): ?>
                        <tr><td colspan="7" class="text-center py-3 text-muted">No hay proveedores registrados aún.</td></tr>
                    <?php else: ?>
                        <?php foreach ($proveedores as $prov): ?>
                            <tr>
                                <td><?= $prov['id'] ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($prov['nombre']) ?></td>
                                <td><?= htmlspecialchars($prov['rfc'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($prov['contacto'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($prov['telefono'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($prov['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($prov['direccion'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
