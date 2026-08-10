<?php
// Conexión a la base de datos
include('config/db.php'); // Usa tu archivo de conexión actual

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $folio = $_POST['folio_siniestro'];

    // Listado de columnas según tu DESCRIBE (Mapeo de los 60 campos)
    // Agregamos solo los que el usuario puede editar/capturar
    $campos = [
        'marca_espejo', 'modelo_espejo', 'cond_ap_paterno', 'cond_ap_materno',
        'cond_no_empleado', 'cond_licencia', 'ubi_referencias', 'no_acta',
        'concluyo_atencion', 'est_gastos_medicos', 'est_equipo_especial',
        'responsable_policia', 'responsable_tercero', 'riesgo_gastos_medicos'
        // ... agrega aquí el resto de los 60 nombres de tu DESCRIBE
    ];

    $valores = [];
    $updates = [];
    $tipos = "s"; // El primer tipo es para el folio_siniestro (string)
    $valores_bind = [$folio];

    foreach ($campos as $columna) {
        // Manejo de Checkboxes (si no viene en POST es 0, si viene es 1)
        if (strpos($columna, 'responsable_') !== false || strpos($columna, 'riesgo_') !== false) {
            $valor = isset($_POST[$columna]) ? 1 : 0;
            $tipos .= "i";
        } else {
            $valor = $_POST[$columna] ?? '';
            $tipos .= "s";
        }
        
        $valores[] = "?";
        $updates[] = "$columna = VALUES($columna)";
        $valores_bind[] = $valor;
    }

    // Construcción de la súper consulta ON DUPLICATE KEY
    $sql = "INSERT INTO reporte_siniestro_detallado (folio_siniestro, " . implode(", ", $campos) . ") 
            VALUES (?, " . implode(", ", $valores) . ") 
            ON DUPLICATE KEY UPDATE " . implode(", ", $updates);

    $stmt = $conn->prepare($sql);
    
    // Vinculación dinámica de los 60 parámetros
    $stmt->bind_param($tipos, ...$valores_bind);

    if ($stmt->execute()) {
        // Al terminar, enviamos al usuario a la vista previa del PDF
        header("Location: generar_pdf.php?folio=" . $folio);
    } else {
        echo "Error al guardar en base espejo: " . $stmt->error;
    }
}
?>
