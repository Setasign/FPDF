<?php
define('FPDF_FONTPATH', '.');
$vendorAutoload = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
} else {
    require('../fpdf.php');
}

$pdf = new FPDF();
$pdf->AddFont('Calligrapher', '', 'calligra.php');
$pdf->AddPage();
$pdf->SetFont('Calligrapher', '', 35);
$pdf->Cell(0, 10, 'Enjoy new fonts with FPDF!');
$pdf->Output();
