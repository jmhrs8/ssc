// 1. CONSULTA INTELIGENTE: Detecta si es ARMA o RADIO
try {
    $stmt_check = $pdo->prepare("SELECT tipo_modulo, id_inventario FROM siniestros_bienes WHERE id = ?");
    $stmt_check->execute([$id_siniestro]);
    $check = $stmt_check->fetch();

    if ($check['tipo_modulo'] == 'ARMA') {
        $sql = "SELECT s.*, a.marca, a.modelo, a.serie_matricula_1 as serie 
                FROM siniestros_bienes s
                JOIN inventario_armas a ON s.id_inventario = a.id
                WHERE s.id = ?";
    } else {
        $sql = "SELECT s.*, r.marca, r.modelo, r.serie 
                FROM siniestros_bienes s
                JOIN radios r ON s.id_inventario = r.id
                WHERE s.id = ?";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_siniestro]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
