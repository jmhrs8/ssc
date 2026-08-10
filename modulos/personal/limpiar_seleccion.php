<?php
session_start();

// Limpiamos las variables de sesión relacionadas con la selección del Excel y alertas
unset($_SESSION['rfc_resaltar']);
unset($_SESSION['alerta_duplicados']);

// Redirigimos al index principal mostrando todos los registros sin filtros
header("Location: index.php");
exit();
?>
