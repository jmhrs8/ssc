<?php
session_start();
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $folio = $_POST['folio'];
    
    // Subida de foto
    $foto_nombre = null;
    if (isset($_FILES['foto_unidad']) && $_FILES['foto_unidad']['error'] == 0) {
        $ext = pathinfo($_FILES['foto_unidad']['name'], PATHINFO_EXTENSION);
        $foto_nombre = "siniestro_" . time() . "." . $ext;
        move_uploaded_file($_FILES['foto_unidad']['tmp_name'], "../../uploads/fotos_siniestros/" . $foto_nombre);
    }

    // Lógica de detección de duplicados (Punto 7)
    $stmt = $pdo->prepare("SELECT id FROM siniestros WHERE folio = ?");
    $stmt->execute([$folio]);
    if ($stmt->fetch()) {
        // Si existe, actualizamos (reemplazar)
        $sql = "UPDATE siniestros SET fecha=?, hora=?, marca=?, modelo=?, tipo=?, economico_placas=?, foto_unidad=? WHERE folio=?";
        $params = [$_POST['fecha'], $_POST['hora'], $_POST['marca'], $_POST['modelo'], $_POST['tipo'], $_POST['economico_placas'], $foto_nombre, $folio];
    } else {
        // Si no existe, insertamos
        $sql = "INSERT INTO siniestros (folio, fecha, hora, marca, modelo, tipo, economico_placas, foto_unidad) VALUES (?,?,?,?,?,?,?,?)";
        $params = [$folio, $_POST['fecha'], $_POST['hora'], $_POST['marca'], $_POST['modelo'], $_POST['tipo'], $_POST['economico_placas'], $foto_nombre];
    }

    $pdo->prepare($sql)->execute($params);
    header("Location: ../../index.php?msg=success");
}
