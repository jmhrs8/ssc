<?php
session_start();
require_once('../../config/conexion.php');
require_once('../../libs/fpdf/fpdf.php');

if (!isset($_SESSION['user_id'])) { die("Acceso denegado."); }

$id = $_GET['id'] ?? null;
if (!$id) die("ID no proporcionado.");

// 1. CONSULTA DETALLADA (Asegúrate de que los nombres de columnas coincidan con tu DB)
try {
    $stmt = $pdo->prepare("SELECT * FROM inventario_armas WHERE id = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}

if (!$r) die("Error: El registro con ID $id no existe en la tabla inventario_armas.");

// 2. CLASE PARA DISEÑO TIPO EXCEL
class PDF extends FPDF {
    function Header() {
        // Cuadro exterior del encabezado
        $this->Rect(10, 10, 190, 30); 
        
        // Espacio para Logo (Ajusta la ruta si tienes el logo de la SSC)
        if (file_exists('../../img/logo_reporte.png')) {
            $this->Image('../../img/logo_reporte.png', 12, 12, 25);
        }
        
        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(40, 15);
        $this->MultiCell(110, 5, utf8_decode("SECRETARÍA DE SEGURIDAD CIUDADANA\nDIRECCIÓN GENERAL DE ADMINISTRACIÓN\nDIRECCIÓN DE RECURSOS MATERIALES"), 0, 'C');
        
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY(155, 15);
        $this->Cell(40, 5, utf8_decode("REPORTE DE"), 0, 1, 'C');
        $this->SetX(155);
        $this->Cell(40, 5, utf8_decode("RECLAMACIÓN"), 0, 1, 'C');
        
        $this->Ln(15);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 7);
        $this->Cell(0, 10, utf8_decode('SSC SYSTEM - Módulo Armamento - Hoja ').$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 20);

// --- ESTILO TIPO EXCEL ---
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(220, 220, 220); // Gris para encabezados

// SECCIÓN 1: DATOS GENERALES DEL SINIESTRO
$pdf->Cell(190, 7, utf8_decode("I. INFORMACIÓN GENERAL DEL EXPEDIENTE"), 1, 1, 'L', true);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "EXPEDIENTE:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 7, utf8_decode($r['no_expediente']), 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "POLIZA:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 7, utf8_decode($r['poliza']), 1, 1, 'L');

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "SINIESTRO No:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 7, utf8_decode($r['no_siniestro']), 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "FECHA SIN.:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 7, utf8_decode($r['fecha_siniestro_1']), 1, 1, 'L');

// SECCIÓN 2: DATOS DEL BIEN
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(190, 7, utf8_decode("II. ESPECIFICACIONES DEL ARMAMENTO / EQUIPO"), 1, 1, 'L', true);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "TIPO DE BIEN:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 7, utf8_decode($r['tipo_bien']), 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "MARCA:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 7, utf8_decode($r['marca']), 1, 1, 'L');

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "MODELO:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 7, utf8_decode($r['modelo']), 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "MATRICULA/S:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 7, utf8_decode($r['serie_matricula_1']), 1, 1, 'L');

// SECCIÓN 3: STATUS Y RECLAMACIÓN
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(190, 7, utf8_decode("III. SEGUIMIENTO DE RECLAMACIÓN"), 1, 1, 'L', true);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "ASEGURADORA:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(160, 7, utf8_decode($r['aseguradora']), 1, 1, 'L');

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "FECHA RECLAM.:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 7, utf8_decode($r['fecha_reclamacion']), 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 7, "DIAS TRANS.:", 1, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(65, 7, $r['dias_transcurridos'] . " DIAS", 1, 1, 'L');

// SECCIÓN 4: DESCRIPCIÓN (MultiCell)
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(190, 7, utf8_decode("IV. DETALLE DEL SINIESTRO Y OBSERVACIONES"), 1, 1, 'L', true);
$pdf->SetFont('Arial', '', 8);
$pdf->MultiCell(190, 5, utf8_decode($r['siniestro_detalle'] ?: 'SIN OBSERVACIONES REGISTRADAS.'), 1, 'J');

// SECCIÓN 5: AREA DE FIRMAS (Al final de la página)
$pdf->SetY(-60);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(95, 5, "ELABORO / ATENDIO", 0, 0, 'C');
$pdf->Cell(95, 5, "RECIBE (NOMBRE Y FIRMA)", 0, 1, 'C');
$pdf->Ln(15);
$pdf->Cell(95, 5, "________________________________", 0, 0, 'C');
$pdf->Cell(95, 5, "________________________________", 0, 1, 'C');
$pdf->Cell(95, 5, utf8_decode($r['atendio']), 0, 0, 'C');
$pdf->Cell(95, 5, "PERSONAL RESPONSABLE", 0, 1, 'C');

$pdf->Output('I', 'Reporte_Reclamacion_'.$r['no_expediente'].'.pdf');
