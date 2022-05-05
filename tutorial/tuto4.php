<?php

use FPDF\FPDF;

include '../vendor/autoload.php';

class PDF extends FPDF
{
    protected $col = 0; // Current column
    protected $y0;      // Ordinate of column start

    function header(): void
    {
        // Page header
        global $title;

        $this->setFont('Arial', 'B', 15);
        $w = $this->getStringWidth($title) + 6;
        $this->setX((210 - $w) / 2);
        $this->setDrawColor(0, 80, 180);
        $this->setFillColor(230, 230, 0);
        $this->setTextColor(220, 50, 50);
        $this->setLineWidth(1);
        $this->cell($w, 9, $title, 1, 1, 'C', true);
        $this->ln(10);
        // Save ordinate
        $this->y0 = $this->getY();
    }

    function footer(): void
    {
        // Page footer
        $this->setY(-15);
        $this->setFont('Arial', 'I', 8);
        $this->setTextColor(128);
        $this->cell(0, 10, 'Page ' . $this->pageNo(), 0, 0, 'C');
    }

    function setCol($col)
    {
        // Set position at a given column
        $this->col = $col;
        $x = 10 + $col * 65;
        $this->setLeftMargin($x);
        $this->setX($x);
    }

    function acceptPageBreak(): bool
    {
        // Method accepting or not automatic page break
        if ($this->col < 2) {
            // Go to next column
            $this->setCol($this->col + 1);
            // Set ordinate to top
            $this->setY($this->y0);

            // Keep on page
            return false;
        } else {
            // Go back to first column
            $this->setCol(0);

            // Page break
            return true;
        }
    }

    function chapterTitle($num, $label)
    {
        // Title
        $this->setFont('Arial', '', 12);
        $this->setFillColor(200, 220, 255);
        $this->cell(0, 6, "Chapter $num : $label", 0, 1, 'L', true);
        $this->ln(4);
        // Save ordinate
        $this->y0 = $this->getY();
    }

    function chapterBody($file)
    {
        // Read text file
        $txt = file_get_contents($file);
        // Font
        $this->setFont('Times', '', 12);
        // Output text in a 6 cm width column
        $this->multiCell(60, 5, $txt);
        $this->ln();
        // Mention
        $this->setFont('', 'I');
        $this->cell(0, 5, '(end of excerpt)');
        // Go back to first column
        $this->setCol(0);
    }

    function printChapter($num, $title, $file)
    {
        // Add chapter
        $this->addPage();
        $this->chapterTitle($num, $title);
        $this->chapterBody($file);
    }
}

$pdf = new PDF();
$title = '20000 Leagues Under the Seas';
$pdf->setTitle($title);
$pdf->setAuthor('Jules Verne');
$pdf->printChapter(1, 'A RUNAWAY REEF', '20k_c1.txt');
$pdf->printChapter(2, 'THE PROS AND CONS', '20k_c2.txt');
$pdf->output();
