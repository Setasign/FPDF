<?php

include '../vendor/autoload.php';

$pdf = new FPDF\FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(40,10,'Hello World!');
$pdf->Output();
