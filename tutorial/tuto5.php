<?php

use FPDF\FPDF;

include '../vendor/autoload.php';

class PDF extends FPDF
{
// Load data
    function loadData($file): array
    {
        // Read file lines
        $lines = file($file);
        $data = array();
        foreach ($lines as $line) {
            $data[] = explode(';', trim($line));
        }

        return $data;
    }

// Simple table
    function basicTable($header, $data)
    {
        // Header
        foreach ($header as $col) {
            $this->cell(40, 7, $col, 1);
        }
        $this->Ln();
        // Data
        foreach ($data as $row) {
            foreach ($row as $col) {
                $this->cell(40, 6, $col, 1);
            }
            $this->Ln();
        }
    }

// Better table
    function improvedTable($header, $data)
    {
        // Column widths
        $w = array(40, 35, 40, 45);
        // Header
        for ($i = 0; $i < count($header); $i++) {
            $this->cell($w[$i], 7, $header[$i], 1, 0, 'C');
        }
        $this->Ln();
        // Data
        foreach ($data as $row) {
            $this->cell($w[0], 6, $row[0], 'LR');
            $this->cell($w[1], 6, $row[1], 'LR');
            $this->cell($w[2], 6, number_format($row[2]), 'LR', 0, 'R');
            $this->cell($w[3], 6, number_format($row[3]), 'LR', 0, 'R');
            $this->ln();
        }
        // Closing line
        $this->cell(array_sum($w), 0, '', 'T');
    }

// Colored table
    function fancyTable($header, $data)
    {
        // Colors, line width and bold font
        $this->setFillColor(255, 0, 0);
        $this->setTextColor(255);
        $this->setDrawColor(128, 0, 0);
        $this->setLineWidth(.3);
        $this->setFont('', 'B');
        // Header
        $w = array(40, 35, 40, 45);
        for ($i = 0; $i < count($header); $i++) {
            $this->cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        // Color and font restoration
        $this->setFillColor(224, 235, 255);
        $this->setTextColor(0);
        $this->setFont('');
        // Data
        $fill = false;
        foreach ($data as $row) {
            $this->cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
            $this->cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
            $this->cell($w[2], 6, number_format($row[2]), 'LR', 0, 'R', $fill);
            $this->cell($w[3], 6, number_format($row[3]), 'LR', 0, 'R', $fill);
            $this->ln();
            $fill = !$fill;
        }
        // Closing line
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

$pdf = new PDF();
// Column headings
$header = array('Country', 'Capital', 'Area (sq km)', 'Pop. (thousands)');
// Data loading
$data = $pdf->loadData('countries.txt');
$pdf->setFont('Arial', '', 14);
$pdf->addPage();
$pdf->basicTable($header, $data);
$pdf->addPage();
$pdf->improvedTable($header, $data);
$pdf->addPage();
$pdf->fancyTable($header, $data);
$pdf->output();
