<?php
require_once "../../config/conexion.php";

$columna = $_POST['columna'] ?? '';
$id_actual = (int)($_POST['id'] ?? 0);
$response = ['duplicado' => false, 'total' => 0];

if ($columna === 'combinacion_siniestro') {
    // Validación cruzada para Siniestro + Aseguradora
    $no_siniestro = trim($_POST['no_siniestro'] ?? '');
    $aseguradora = trim($_POST['aseguradora'] ?? '');

    if (!empty($no_siniestro) && !empty($aseguradora)) {
        $sql = "SELECT COUNT(*) FROM siniestros WHERE no_siniestro = ? AND aseguradora = ? AND id != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$no_siniestro, $aseguradora, $id_actual]);
        $total = $stmt->fetchColumn();

        if ($total > 0) {
            $response['duplicado'] = true;
            $response['total'] = $total;
        }
    }
} else if (!empty($columna)) {
    // Validación original para Placas o Número de Serie
    $valor = trim($_POST['valor'] ?? '');
    
    if (!empty($valor)) {
        $sql = "SELECT COUNT(*) FROM siniestros WHERE $columna = ? AND id != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$valor, $id_actual]);
        $total = $stmt->fetchColumn();

        if ($total > 0) {
            $response['duplicado'] = true;
            $response['total'] = $total;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
