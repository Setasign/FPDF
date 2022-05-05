<?php

use FPDF\FPDF;

include '../vendor/autoload.php';

class PDF extends FPDF
{
    function header(): void
    {
        // Logo
        $this->image('logo.png', 10, 6, 30);
        // Arial bold 15
        $this->setFont('Arial', 'B', 15);
        // Move to the right
        $this->cell(80);
        // Title
        $this->cell(30, 10, 'Title', 1, 0, 'C');
        // Line break
        $this->ln(20);
    }

    function footer(): void
    {
        // Position at 1.5 cm from bottom
        $this->setY(-15);
        // Arial italic 8
        $this->setFont('Arial', 'I', 8);
        // Page number
        $this->cell(0, 10, 'Page ' . $this->pageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Instanciation of inherited class
$pdf = new PDF();
$pdf->aliasNbPages();
$pdf->addPage();
$pdf->setFont('Times', '', 12);
for ($i = 1; $i <= 40; $i++) {
    $pdf->Cell(0, 10, 'Printing line number ' . $i, 0, 1);
}
$pdf->output();
