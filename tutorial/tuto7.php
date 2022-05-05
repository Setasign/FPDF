<?php
define('FPDF_FONTPATH', '.');

include '../vendor/autoload.php';

$pdf = new FPDF\FPDF();
$pdf->addFont('CevicheOne', '', 'CevicheOne-Regular.php');
$pdf->addPage();
$pdf->setFont('CevicheOne', '', 45);
$pdf->cell(0, 10, 'Enjoy new fonts with FPDF!');
$pdf->output();
