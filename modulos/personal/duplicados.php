<?php
session_start();
require_once "../../config/conexion.php";

// --- VALIDACIÓN DE SEGURIDAD ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$nivel_actual = strtoupper(trim($_SESSION['nivel'] ?? ''));
$permiso_personal = $_SESSION['permiso_personal'] ?? 0;
$usuario_sesion = $_SESSION['nombre'] ?? ($_SESSION['usuario'] ?? 'INVITADO');

if (!$nivel_actual || ($nivel_actual !== 'ADMIN_GENERAL' && $permiso_personal != 1)) {
    header("Location: ../../index.php?error=sin_permiso");
    exit();
}

$es_admin_general = ($nivel_actual === 'ADMIN_GENERAL');

try {
    // Traemos registros para comparar
    $sql = "SELECT id, nombre, apellido_paterno, apellido_materno, rfc, area_adscripcion 
            FROM personal 
            WHERE rfc IS NOT NULL AND rfc != '' 
            ORDER BY apellido_paterno, nombre";
    
    $stmt = $pdo->query($sql);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $duplicados_filtrados = [];
    $total_regs = count($registros);

    // Algoritmo para detectar error de dedo en RFC (distancia 1 o 2)
    for ($i = 0; $i < $total_regs; $i++) {
        for ($j = $i + 1; $j < $total_regs; $j++) {
            $p1 = $registros[$i];
            $p2 = $registros[$j];

            if (trim($p1['apellido_paterno']) === trim($p2['apellido_paterno']) && trim($p1['nombre']) === trim($p2['nombre'])) {
                $rfc1 = strtoupper(trim($p1['rfc']));
                $rfc2 = strtoupper(trim($p2['rfc']));
                
                if (levenshtein($rfc1, $rfc2) > 0 && levenshtein($rfc1, $rfc2) <= 2) {
                    $duplicados_filtrados[] = [
                        'id1' => $p1['id'], 'nom1' => $p1['nombre'], 'pat1' => $p1['apellido_paterno'], 'mat1' => $p1['apellido_materno'], 'rfc1' => $p1['rfc'], 'area1' => $p1['area_adscripcion'],
                        'id2' => $p2['id'], 'nom2' => $p2['nombre'], 'pat2' => $p2['apellido_paterno'], 'mat2' => $p2['apellido_materno'], 'rfc2' => $p2['rfc'], 'area2' => $p2['area_adscripcion']
                    ];
                }
            }
        }
    }

    // --- ACCIÓN DE EXPORTAR A EXCEL ---
    if (isset($_GET['exportar']) && $_GET['exportar'] == 'excel') {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=reporte_posibles_duplicados_" . date('Y-m-d') . ".xls");
        
        echo "<table border='1'>";
        echo "<tr style='background-color: #212529; color: #ffffff; font-weight: bold;'>";
        echo "<th>#</th><th>ID A</th><th>Apellidos y Nombre (A)</th><th>RFC A</th><th>Área A</th>";
        echo "<th>ID B</th><th>Apellidos y Nombre (B)</th><th>RFC B</th><th>Área B</th>";
        echo "</tr>";
        
        foreach ($duplicados_filtrados as $idx => $row) {
            echo "<tr>";
            echo "<td>" . ($idx + 1) . "</td>";
            echo "<td>" . $row['id1'] . "</td>";
            echo "<td>" . htmlspecialchars($row['pat1'] . ' ' . $row['mat1'] . ' ' . $row['nom1']) . "</td>";
            echo "<td><b>" . htmlspecialchars($row['rfc1']) . "</b></td>";
            echo "<td>" . htmlspecialchars($row['area1']) . "</td>";
            echo "<td>" . $row['id2'] . "</td>";
            echo "<td>" . htmlspecialchars($row['pat2'] . ' ' . $row['mat2'] . ' ' . $row['nom2']) . "</td>";
            echo "<td><b>" . htmlspecialchars($row['rfc2']) . "</b></td>";
            echo "<td>" . htmlspecialchars($row['area2']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit();
    }

} catch (PDOException $e) {
    die("ERROR DE SISTEMA: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>DEPURACIÓN DE DUPLICADOS | SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-size: 11px; background-color: #f4f6f9; text-transform: uppercase; }
        .header-top { background: #1a1a1a; color: white; padding: 12px; }
        .table-container { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table th { background: #212529 !important; color: white !important; text-align: center; font-weight: 600; }
        .table td { vertical-align: middle; }
        .btn-xs { padding: 3px 8px; font-size: 11px; }
    </style>
</head>
<body>

<div class="header-top shadow-sm mb-3">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-copy text-warning"></i> DEPURACIÓN: DETECCIÓN DE ERRORES DE DEDO</h5>
        </div>
        <div class="d-flex gap-2">
            <!-- Botón para descargar a Excel -->
            <a href="duplicados.php?exportar=excel" class="btn btn-success btn-sm fw-bold"><i class="fas fa-file-excel"></i> EXPORTAR A EXCEL</a>
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left"></i> REGRESAR</a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="table-container shadow-sm">
        <table class="table table-hover table-bordered mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>REGISTRO A</th>
                    <th>RFC (A)</th>
                    <th>REGISTRO B</th>
                    <th>RFC (B)</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($duplicados_filtrados) > 0): ?>
                    <?php foreach ($duplicados_filtrados as $index => $d): ?>
                    <tr>
                        <td class="text-center fw-bold"><?= $index + 1 ?></td>
                        <td><strong><?= htmlspecialchars($d['pat1'].' '.$d['mat1'].' '.$d['nom1']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($d['area1']) ?></small></td>
                        <td class="text-center fw-bold text-primary"><?= htmlspecialchars($d['rfc1']) ?></td>
                        <td><strong><?= htmlspecialchars($d['pat2'].' '.$d['mat2'].' '.$d['nom2']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($d['area2']) ?></small></td>
                        <td class="text-center fw-bold text-danger"><?= htmlspecialchars($d['rfc2']) ?></td>
                        <td class="text-center">
                            <a href="formulario.php?id=<?= $d['id1'] ?>" target="_blank" class="btn btn-warning btn-xs" title="Editar Registro 1"><i class="fas fa-edit"></i> 1</a>
                            <a href="formulario.php?id=<?= $d['id2'] ?>" target="_blank" class="btn btn-warning btn-xs" title="Editar Registro 2"><i class="fas fa-edit"></i> 2</a>
                            <?php if ($es_admin_general): ?>
                                <button class="btn btn-danger btn-xs" onclick="borrarRegistro(<?= $d['id1'] ?>, this)"><i class="fas fa-trash"></i> 1</button>
                                <button class="btn btn-danger btn-xs" onclick="borrarRegistro(<?= $d['id2'] ?>, this)"><i class="fas fa-trash"></i> 2</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted fw-bold">NO SE ENCONTRARON POSIBLES DUPLICADOS POR ERROR DE DEDO EN RFC.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function borrarRegistro(id, btn) {
    if (!confirm('¿ELIMINAR ESTE REGISTRO PERMANENTEMENTE?')) return;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('acciones.php?eliminar=' + id, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        btn.closest('tr').remove();
    })
    .catch(error => { alert('ERROR AL PROCESAR'); btn.disabled = false; });
}
</script>
</body>
</html>
