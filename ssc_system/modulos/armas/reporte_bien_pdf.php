<?php
session_start();
require_once('../../config/conexion.php');
require_once('../../libs/fpdf/fpdf.php');

if (!isset($_SESSION['user_id'])) { die("Acceso denegado."); }

$id_siniestro = $_GET['id_siniestro'] ?? null;
if (!$id_siniestro) die("ID de reporte no proporcionado.");

// 1. CONSULTA CRUZADA: Siniestro + Inventario de Armas
try {
    $sql = "SELECT s.*, a.marca, a.modelo, a.serie_matricula_1 
            FROM siniestros_bienes s
            JOIN inventario_armas a ON s.id_inventario = a.id
            WHERE s.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_siniestro]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}

if (!$r) die("Error: Registro no encontrado.");

class PDF extends FPDF {
    function Header() {
        // Logo y Encabezado Institucional
        if (file_exists('../../img/logo_reporte.png')) {
            $this->Image('../../img/logo_reporte.png', 10, 10, 25);
        }
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(40, 10);
        $this->Cell(120, 5, utf8_decode("CIUDAD DE MÉXICO"), 0, 1, 'L');
        $this->SetX(40);
        $this->SetFont('Arial', '', 8);
        $this->Cell(120, 5, utf8_decode("CAPITAL DE LA TRANSFORMACIÓN"), 0, 1, 'L');
        
        $this->SetY(25);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(190, 7, utf8_decode("REPORTE DE SINIESTRO 2026"), 0, 1, 'C');
        $this->Ln(5);
    }

    // Función para dibujar los cuadros de verificación (CheckBoxes)
    function CheckBox($label, $x, $y, $checked = false) {
        $this->SetXY($x, $y);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(35, 5, utf8_decode($label), 0, 0, 'L');
        $this->Rect($x + 35, $y, 5, 5);
        if ($checked) {
            $this->SetFont('Arial', 'B', 10);
            $this->Text($x + 35.5, $y + 4.5, 'X');
        }
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);

// --- SECCIÓN SUPERIOR: EXPEDIENTE Y TIPO DE BIEN ---
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(100, 7, "EXPEDIENTE:", 0, 0);
$pdf->CheckBox("ARMAMENTO", 110, $pdf->GetY(), ($r['tipo_modulo'] == 'ARMA'));
$pdf->Ln(7);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(100, 7, utf8_decode($r['folio_siniestro_interno']), 1, 1);

// --- FECHAS ---
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(63, 5, utf8_decode("FECHA DE ELABORACIÓN:"), 1, 0, 'C', true);
$pdf->Cell(63, 5, utf8_decode("FECHA DE SINIESTRO:"), 1, 0, 'C', true);
$pdf->Cell(64, 5, utf8_decode("HORA DE OCURRENCIA:"), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(63, 7, $r['fecha_elaboracion'], 1, 0, 'C');
$pdf->Cell(63, 7, $r['fecha_siniestro'], 1, 0, 'C');
$pdf->Cell(64, 7, $r['hora_siniestro'], 1, 1, 'C');

// --- DATOS DEL RESGUARDANTE ---
$pdf->Ln(2);
$pdf->SetFillColor(230, 230, 230);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(190, 6, "DATOS DEL RESGUARDANTE DEL BIEN", 1, 1, 'L', true);

$pdf->SetFont('Arial', 'B', 8);
$fields = [
    'NOMBRE:' => $r['nombre_resguardante'],
    'ADSCRIPCIÓN:' => $r['adscripcion_resguardante'],
    'GRADO:' => $r['grado_resguardante'],
    'NO. EMPLEADO:' => $r['num_empleado_resguardante'],
    'TELÉFONO:' => $r['tel_resguardante'],
    'E-MAIL:' => $r['email_resguardante']
];

foreach($fields as $label => $val) {
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(40, 6, utf8_decode($label), 1, 0);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(150, 6, utf8_decode($val), 1, 1);
}

// --- DATOS DEL SINIESTRO ---
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(190, 6, "DATOS DEL SINIESTRO", 1, 1, 'L', true);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(40, 6, "TIPO DE SINIESTRO:", 1, 0);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(150, 6, utf8_decode($r['tipo_siniestro']), 1, 1, 'C');

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(190, 6, utf8_decode("NARRACIÓN SINIESTRO:"), 1, 1, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->MultiCell(190, 4, utf8_decode($r['narrativa_siniestro']), 1, 'J');

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(40, 6, "LUGAR DEL SINIESTRO:", 1, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(150, 6, utf8_decode($r['lugar_siniestro']), 1, 1);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(40, 6, utf8_decode("DESCRIPCIÓN DEL BIEN:"), 1, 0);
$pdf->SetFont('Arial', '', 8);
$desc = "MARCA: {$r['marca']}, MODELO: {$r['modelo']}, SERIE: {$r['serie_matricula_1']}";
$pdf->Cell(150, 6, utf8_decode($desc), 1, 1);

// --- PIE DE PÁGINA / FIRMAS ---
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(95, 20, "", 1, 0); // Espacio firma 1
$pdf->Cell(95, 20, "", 1, 1); // Espacio firma 2
$pdf->Cell(95, 5, utf8_decode("NOMBRE Y FIRMA DE QUIEN REPORTA"), 1, 0, 'C');
$pdf->Cell(95, 5, utf8_decode("VoBo J.U.D DE ASEGURAMIENTO DE BIENES"), 1, 1, 'C');

$pdf->Output('I', 'Reporte_Siniestro_'.$r['folio_siniestro_interno'].'.pdf');
