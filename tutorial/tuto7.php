<?php
require('../fpdf.php');

$pdf = new FPDF();
$pdf->AddFont('CevicheOne','','CevicheOne-Regular.php','.');
$pdf->AddPage();
$pdf->SetFont('CevicheOne','',45);
$pdf->Write(10,'Enjoy new fonts with FPDF!');
$pdf->Output();
?>
