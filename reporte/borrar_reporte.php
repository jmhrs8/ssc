<?php
include('config.php');

// Verificar que el usuario tenga sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // 1. Obtener los nombres de las fotos para borrarlas del disco
    $res = $conn->query("SELECT fotos FROM reportes WHERE id = $id");
    if ($res && $res->num_rows > 0) {
        $data = $res->fetch_assoc();
        $fotos = json_decode($data['fotos'], true);

        // 2. Borrar archivos físicos de la carpeta uploads
        if (is_array($fotos)) {
            foreach ($fotos as $foto) {
                $ruta_foto = "uploads/" . $foto;
                if (file_exists($ruta_foto)) {
                    unlink($ruta_foto);
                }
            }
        }

        // 3. Borrar el registro de la base de datos
        $sql = "DELETE FROM reportes WHERE id = $id";
        if ($conn->query($sql)) {
            // REDIRECCIÓN CORREGIDA AL INDEX
            header("Location: index.php?msg=eliminado");
            exit();
        } else {
            echo "Error al eliminar el registro: " . $conn->error;
        }
    } else {
        echo "Reporte no encontrado.";
    }
}
?>
