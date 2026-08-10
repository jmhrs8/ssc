<?php
require('../../libs/fpdf/fpdf.php'); // Asegúrate de tener la librería en esta ruta
include("../../config/db.php");

$id = $_GET['id'];
$query = "SELECT * FROM reporte_siniestro_detallado WHERE id_reporte = $id";
$res = mysqli_query($conexion, $query);
$reg = mysqli_fetch_assoc($res);

class PDF extends FPDF {
    function Header() {
        // Logo y Encabezado según WhatsApp Image 2026-05-04 at 3.30.51 PM_2.jpeg
        $this->Image('../../img/logo_ssc.png', 10, 8, 33);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, utf8_decode('CIUDAD DE MÉXICO - SECRETARÍA DE SEGURIDAD CIUDADANA'), 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 5, utf8_decode('REPORTE TELEFÓNICO DE SINIESTRO'), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        // Línea de firma según el pie de página de la imagen
        $this->SetY(-30);
        $this->Line(60, $this->GetY(), 150, $this->GetY());
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 10, utf8_decode('NOMBRE Y FIRMA DE LA/EL OPERADOR(A)'), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

// --- BLOQUE FECHA (Arriba Derecha) ---
$pdf->SetXY(140, 20);
$pdf->Cell(15, 5, 'DIA', 1, 0, 'C');
$pdf->Cell(15, 5, 'MES', 1, 0, 'C');
$pdf->Cell(15, 5, 'ANIO', 1, 1, 'C');
$pdf->SetX(140);
$pdf->Cell(15, 7, $reg['dia'], 1, 0, 'C');
$pdf->Cell(15, 7, $reg['mes'], 1, 0, 'C');
$pdf->Cell(15, 7, $reg['anio'], 1, 1, 'C');

// --- DATOS DEL VEHÍCULO / EQUIPO ---
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(0, 6, utf8_decode('DATOS DEL VEHÍCULO ASEGURADO / EQUIPO'), 1, 1, 'L', true);
$pdf->Cell(60, 5, 'MARCA', 1); $pdf->Cell(70, 5, 'MODELO', 1); $pdf->Cell(60, 5, 'TIPO', 1, 1);
$pdf->Cell(60, 7, utf8_decode($reg['marca_espejo']), 1); 
$pdf->Cell(70, 7, utf8_decode($reg['modelo_espejo']), 1); 
$pdf->Cell(60, 7, utf8_decode($reg['tipo_espejo']), 1, 1);

// --- BLOQUE DE RIESGOS (Segunda hoja - WhatsApp Image 2026-05-04 at 3.31.00 PM_2.jpeg) ---
$pdf->Ln(10);
$pdf->Cell(0, 6, utf8_decode('RIESGO AFECTADO'), 1, 1, 'L', true);
$pdf->Cell(100, 7, utf8_decode('DAÑOS MATERIALES'), 0);
$pdf->Cell(20, 7, ($reg['riesgo_danos_materiales'] ? '( X )' : '(   )'), 0);
$pdf->Cell(70, 7, '$ ' . number_format($reg['est_danos_materiales'], 2), 'B', 1);

$pdf->Cell(100, 7, utf8_decode('ROBO TOTAL'), 0);
$pdf->Cell(20, 7, ($reg['riesgo_robo_total'] ? '( X )' : '(   )'), 0);
$pdf->Cell(70, 7, '$ ' . number_format($reg['est_robo_total'], 2), 'B', 1);

// --- OBSERVACIONES Y CONCLUSIÓN ---
$pdf->Ln(5);
$pdf->MultiCell(0, 5, utf8_decode("OBSERVACIONES: \n" . $reg['observaciones_generales']), 1);

$pdf->Output('I', 'Reporte_Siniestro_' . $reg['folio_siniestro'] . '.pdf');
?>
