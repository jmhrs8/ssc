<?php
require_once "../../config/conexion.php";

try {
    // Buscamos el folio exacto que me comentas
    $stmt = $pdo->prepare("SELECT * FROM siniestros WHERE FOLIO = ? OR economico_placas = ? OR id = (SELECT MAX(id) FROM siniestros) LIMIT 1");
    $stmt->execute(['0414-2026', 'MX-195D-49']);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h2>Estructura Real de la Base de Datos</h2>";
    if ($resultado) {
        echo "<pre>";
        print_r($resultado);
        echo "</pre>";
    } else {
        echo "No se encontró el folio 0414-2026. Vamos a traer el último registro guardado para revisar:<br>";
        $stmt2 = $pdo->query("SELECT * FROM siniestros ORDER BY id DESC LIMIT 1");
        $ultimo = $stmt2->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($ultimo);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "Error al conectar o consultar: " . $e->getMessage();
}
