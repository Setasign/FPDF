<?php
/**
 * FPDF
 *
 * @version 1.8.4
 * @author Oliver PLATHEY
 * @author Janis VINCENT <j.vincent@santevet.com>
 */

namespace FPDF;

use Exception;

define('FPDF_VERSION', '1.8.41');

class FPDF
{
    protected $page;               // current page number
    protected $n;                  // current object number
    protected $offsets;            // array of object offsets
    protected $buffer;             // buffer holding in-memory PDF
    protected $pages;              // array containing pages
    protected $state;              // current document state
    protected $compress;           // compression flag
    protected $k;                  // scale factor (number of points in user unit)
    protected $defOrientation;     // default orientation
    protected $curOrientation;     // current orientation
    protected $stdPageSizes;       // standard page sizes
    protected $defPageSize;        // default page size
    protected $curPageSize;        // current page size
    protected $curRotation;        // current page rotation
    protected $pageInfo;           // page-related data
    protected $wPt, $hPt;          // dimensions of current page in points
    protected $w, $h;              // dimensions of current page in user unit
    protected $lMargin;            // left margin
    protected $tMargin;            // top margin
    protected $rMargin;            // right margin
    protected $bMargin;            // page break margin
    protected $cMargin;            // cell margin
    protected $x, $y;              // current position in user unit
    protected $lasth;              // height of last printed cell
    protected $lineWidth;          // line width in user unit
    protected $fontpath;           // path containing fonts
    protected $coreFonts;          // array of core font names
    protected $fonts;              // array of used fonts
    protected $fontFiles;          // array of font files
    protected $encodings;          // array of encodings
    protected $cmaps;              // array of ToUnicode CMaps
    protected $fontFamily;         // current font family
    protected $fontStyle;          // current font style
    protected $underline;          // underlining flag
    protected $currentFont;        // current font info
    protected $fontSizePt;         // current font size in points
    protected $fontSize;           // current font size in user unit
    protected $drawColor;          // commands for drawing color
    protected $fillColor;          // commands for filling color
    protected $textColor;          // commands for text color
    protected $colorFlag;          // indicates whether fill and text colors are different
    protected $withAlpha;          // indicates whether alpha channel is used
    protected $ws;                 // word spacing
    protected $images;             // array of used images
    protected $pageLinks;          // array of links in pages
    protected $links;              // array of internal links
    protected $autoPageBreak;      // automatic page breaking
    protected $pageBreakTrigger;   // threshold used to trigger page breaks
    protected $inHeader;           // flag set when processing header
    protected $inFooter;           // flag set when processing footer
    protected $aliasNbPages;       // alias for total number of pages
    protected $zoomMode;           // zoom display mode
    protected $layoutMode;         // layout display mode
    protected $metadata;           // document properties
    protected $PDFVersion;         // PDF version number

    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
    {
        // Some checks
        $this->_dochecks();
        // Initialization of properties
        $this->state = 0;
        $this->page = 0;
        $this->n = 2;
        $this->buffer = '';
        $this->pages = array();
        $this->pageInfo = array();
        $this->fonts = array();
        $this->fontFiles = array();
        $this->encodings = array();
        $this->cmaps = array();
        $this->images = array();
        $this->links = array();
        $this->inHeader = false;
        $this->inFooter = false;
        $this->lasth = 0;
        $this->fontFamily = '';
        $this->fontStyle = '';
        $this->fontSizePt = 12;
        $this->underline = false;
        $this->drawColor = '0 G';
        $this->fillColor = '0 g';
        $this->textColor = '0 g';
        $this->colorFlag = false;
        $this->withAlpha = false;
        $this->ws = 0;
        // Font path
        if (defined('FPDF_FONTPATH')) {
            $this->fontpath = FPDF_FONTPATH;
            if (substr($this->fontpath, -1) != '/' && substr($this->fontpath, -1) != '\\') {
                $this->fontpath .= '/';
            }
        } elseif (is_dir(dirname(__FILE__) . '/font')) {
            $this->fontpath = dirname(__FILE__) . '/font/';
        } else {
            $this->fontpath = '';
        }
        // Core fonts
        $this->coreFonts = array('courier', 'helvetica', 'times', 'symbol', 'zapfdingbats');
        // Scale factor
        if ($unit == 'pt') {
            $this->k = 1;
        } elseif ($unit == 'mm') {
            $this->k = 72 / 25.4;
        } elseif ($unit == 'cm') {
            $this->k = 72 / 2.54;
        } elseif ($unit == 'in') {
            $this->k = 72;
        } else {
            $this->error('Incorrect unit: ' . $unit);
        }
        // Page sizes
        $this->stdPageSizes = array(
            'a3' => array(841.89, 1190.55),
            'a4' => array(595.28, 841.89),
            'a5' => array(420.94, 595.28),
            'letter' => array(612, 792),
            'legal' => array(612, 1008)
        );
        $size = $this->_getpagesize($size);
        $this->defPageSize = $size;
        $this->curPageSize = $size;
        // Page orientation
        $orientation = strtolower($orientation);
        if ($orientation == 'p' || $orientation == 'portrait') {
            $this->defOrientation = 'P';
            $this->w = $size[0];
            $this->h = $size[1];
        } elseif ($orientation == 'l' || $orientation == 'landscape') {
            $this->defOrientation = 'L';
            $this->w = $size[1];
            $this->h = $size[0];
        } else {
            $this->error('Incorrect orientation: ' . $orientation);
        }
        $this->curOrientation = $this->defOrientation;
        $this->wPt = $this->w * $this->k;
        $this->hPt = $this->h * $this->k;
        // Page rotation
        $this->curRotation = 0;
        // Page margins (1 cm)
        $margin = 28.35 / $this->k;
        $this->setMargins($margin, $margin);
        // Interior cell margin (1 mm)
        $this->cMargin = $margin / 10;
        // Line width (0.2 mm)
        $this->lineWidth = .567 / $this->k;
        // Automatic page break
        $this->setAutoPageBreak(true, 2 * $margin);
        // Default display mode
        $this->setDisplayMode('default');
        // Enable compression
        $this->setCompression(true);
        // Set default PDF version number
        $this->pDFVersion = '1.3';
    }

    public function setMargins($left, $top, $right = null): void
    {
        // Set left, top and right margins
        $this->lMargin = $left;
        $this->tMargin = $top;
        if ($right === null) {
            $right = $left;
        }
        $this->rMargin = $right;
    }

    public function setLeftMargin($margin): void
    {
        // Set left margin
        $this->lMargin = $margin;
        if ($this->page > 0 && $this->x < $margin) {
            $this->x = $margin;
        }
    }

    public function setTopMargin($margin): void
    {
        // Set top margin
        $this->tMargin = $margin;
    }

    public function setRightMargin($margin): void
    {
        // Set right margin
        $this->rMargin = $margin;
    }

    public function setAutoPageBreak($auto, $margin = 0): void
    {
        // Set auto page break mode and triggering margin
        $this->autoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->pageBreakTrigger = $this->h - $margin;
    }

    public function setDisplayMode($zoom, $layout = 'default'): void
    {
        // Set display mode in viewer
        if ($zoom == 'fullpage' || $zoom == 'fullwidth' || $zoom == 'real' || $zoom == 'default' || !is_string($zoom)) {
            $this->zoomMode = $zoom;
        } else {
            $this->error('Incorrect zoom display mode: ' . $zoom);
        }
        if ($layout == 'single' || $layout == 'continuous' || $layout == 'two' || $layout == 'default') {
            $this->layoutMode = $layout;
        } else {
            $this->error('Incorrect layout display mode: ' . $layout);
        }
    }

    public function setCompression($compress): void
    {
        // Set page compression
        if (function_exists('gzcompress')) {
            $this->compress = $compress;
        } else {
            $this->compress = false;
        }
    }

    public function setTitle($title, $isUTF8 = false): void
    {
        // Title of document
        $this->metadata['Title'] = $isUTF8 ? $title : utf8_encode($title);
    }

    public function setAuthor($author, $isUTF8 = false): void
    {
        // Author of document
        $this->metadata['Author'] = $isUTF8 ? $author : utf8_encode($author);
    }

    public function setSubject($subject, $isUTF8 = false): void
    {
        // Subject of document
        $this->metadata['Subject'] = $isUTF8 ? $subject : utf8_encode($subject);
    }

    public function setKeywords($keywords, $isUTF8 = false): void
    {
        // Keywords of document
        $this->metadata['Keywords'] = $isUTF8 ? $keywords : utf8_encode($keywords);
    }

    public function setCreator($creator, $isUTF8 = false): void
    {
        // Creator of document
        $this->metadata['Creator'] = $isUTF8 ? $creator : utf8_encode($creator);
    }

    public function aliasNbPages($alias = '{nb}'): void
    {
        // Define an alias for total number of pages
        $this->aliasNbPages = $alias;
    }

    public function error($msg): void
    {
        // Fatal error
        throw new Exception('FPDF error: ' . $msg);
    }

    public function close(): void
    {
        // Terminate document
        if ($this->state == 3) {
            return;
        }
        if ($this->page == 0) {
            $this->addPage();
        }
        // Page footer
        $this->inFooter = true;
        $this->footer();
        $this->inFooter = false;
        // Close page
        $this->_endpage();
        // Close document
        $this->_enddoc();
    }

    public function addPage($orientation = '', $size = '', $rotation = 0): void
    {
        // Start a new page
        if ($this->state == 3) {
            $this->error('The document is closed');
        }
        $family = $this->fontFamily;
        $style = $this->fontStyle . ($this->underline ? 'U' : '');
        $fontsize = $this->fontSizePt;
        $lw = $this->lineWidth;
        $dc = $this->drawColor;
        $fc = $this->fillColor;
        $tc = $this->textColor;
        $cf = $this->colorFlag;
        if ($this->page > 0) {
            // Page footer
            $this->inFooter = true;
            $this->footer();
            $this->inFooter = false;
            // Close page
            $this->_endpage();
        }
        // Start new page
        $this->_beginpage($orientation, $size, $rotation);
        // Set line cap style to square
        $this->_out('2 J');
        // Set line width
        $this->lineWidth = $lw;
        $this->_out(sprintf('%.2F w', $lw * $this->k));
        // Set font
        if ($family) {
            $this->setFont($family, $style, $fontsize);
        }
        // Set colors
        $this->drawColor = $dc;
        if ($dc != '0 G') {
            $this->_out($dc);
        }
        $this->fillColor = $fc;
        if ($fc != '0 g') {
            $this->_out($fc);
        }
        $this->textColor = $tc;
        $this->colorFlag = $cf;
        // Page header
        $this->inHeader = true;
        $this->header();
        $this->inHeader = false;
        // Restore line width
        if ($this->lineWidth != $lw) {
            $this->lineWidth = $lw;
            $this->_out(sprintf('%.2F w', $lw * $this->k));
        }
        // Restore font
        if ($family) {
            $this->setFont($family, $style, $fontsize);
        }
        // Restore colors
        if ($this->drawColor != $dc) {
            $this->drawColor = $dc;
            $this->_out($dc);
        }
        if ($this->fillColor != $fc) {
            $this->fillColor = $fc;
            $this->_out($fc);
        }
        $this->textColor = $tc;
        $this->colorFlag = $cf;
    }

    public function header(): void
    {
        // To be implemented in your own inherited class
    }

    public function footer(): void
    {
        // To be implemented in your own inherited class
    }

    public function pageNo(): int
    {
        // Get current page number
        return $this->page;
    }

    public function setDrawColor($r, $g = null, $b = null): void
    {
        // Set color for all stroking operations
        if (($r == 0 && $g == 0 && $b == 0) || $g === null) {
            $this->drawColor = sprintf('%.3F G', $r / 255);
        } else {
            $this->drawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
        }
        if ($this->page > 0) {
            $this->_out($this->drawColor);
        }
    }

    public function setFillColor($r, $g = null, $b = null): void
    {
        // Set color for all filling operations
        if (($r == 0 && $g == 0 && $b == 0) || $g === null) {
            $this->fillColor = sprintf('%.3F g', $r / 255);
        } else {
            $this->fillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        }
        $this->colorFlag = ($this->fillColor != $this->textColor);
        if ($this->page > 0) {
            $this->_out($this->fillColor);
        }
    }

    public function setTextColor($r, $g = null, $b = null): void
    {
        // Set color for text
        if (($r == 0 && $g == 0 && $b == 0) || $g === null) {
            $this->textColor = sprintf('%.3F g', $r / 255);
        } else {
            $this->textColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        }
        $this->colorFlag = ($this->fillColor != $this->textColor);
    }

    public function getStringWidth($s): int
    {
        // Get width of a string in the current font
        $s = (string)$s;
        $cw = &$this->currentFont['cw'];
        $w = 0;
        $l = strlen($s);
        for ($i = 0; $i < $l; $i++) {
            $w += $cw[$s[$i]];
        }

        return $w * $this->fontSize / 1000;
    }

    public function setLineWidth($width): void
    {
        // Set line width
        $this->lineWidth = $width;
        if ($this->page > 0) {
            $this->_out(sprintf('%.2F w', $width * $this->k));
        }
    }

    public function line($x1, $y1, $x2, $y2): void
    {
        // Draw a line
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S',
                $x1 * $this->k,
                ($this->h - $y1) * $this->k,
                $x2 * $this->k,
                ($this->h - $y2) * $this->k
            )
        );
    }

    public function rect($x, $y, $w, $h, $style = ''): void
    {
        // Draw a rectangle
        if ($style == 'F') {
            $op = 'f';
        } elseif ($style == 'FD' || $style == 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s',
                $x * $this->k,
                ($this->h - $y) * $this->k,
                $w * $this->k,
                -$h * $this->k,
                $op
            )
        );
    }

    public function addFont($family, $style = '', $file = ''): void
    {
        // Add a TrueType, OpenType or Type1 font
        $family = strtolower($family);
        if ($file == '') {
            $file = str_replace(' ', '', $family) . strtolower($style) . '.php';
        }
        $style = strtoupper($style);
        if ($style == 'IB') {
            $style = 'BI';
        }
        $fontkey = $family . $style;
        if (isset($this->fonts[$fontkey])) {
            return;
        }
        $info = $this->_loadfont($file);
        $info['i'] = count($this->fonts) + 1;
        if (!empty($info['file'])) {
            // Embedded font
            if ($info['type'] == 'TrueType') {
                $this->fontFiles[$info['file']] = array('length1' => $info['originalsize']);
            } else {
                $this->fontFiles[$info['file']] = array('length1' => $info['size1'], 'length2' => $info['size2']);
            }
        }
        $this->fonts[$fontkey] = $info;
    }

    public function setFont($family, $style = '', $size = 0): void
    {
        // Select a font; size given in points
        if ($family == '') {
            $family = $this->fontFamily;
        } else {
            $family = strtolower($family);
        }
        $style = strtoupper($style);
        if (strpos($style, 'U') !== false) {
            $this->underline = true;
            $style = str_replace('U', '', $style);
        } else {
            $this->underline = false;
        }
        if ($style == 'IB') {
            $style = 'BI';
        }
        if ($size == 0) {
            $size = $this->fontSizePt;
        }
        // Test if font is already selected
        if ($this->fontFamily == $family && $this->fontStyle == $style && $this->fontSizePt == $size) {
            return;
        }
        // Test if font is already loaded
        $fontkey = $family . $style;
        if (!isset($this->fonts[$fontkey])) {
            // Test if one of the core fonts
            if ($family == 'arial') {
                $family = 'helvetica';
            }
            if (in_array($family, $this->coreFonts)) {
                if ($family == 'symbol' || $family == 'zapfdingbats') {
                    $style = '';
                }
                $fontkey = $family . $style;
                if (!isset($this->fonts[$fontkey])) {
                    $this->addFont($family, $style);
                }
            } else {
                $this->error('Undefined font: ' . $family . ' ' . $style);
            }
        }
        // Select it
        $this->fontFamily = $family;
        $this->fontStyle = $style;
        $this->fontSizePt = $size;
        $this->fontSize = $size / $this->k;
        $this->currentFont = &$this->fonts[$fontkey];
        if ($this->page > 0) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->currentFont['i'], $this->fontSizePt));
        }
    }

    public function setFontSize($size): void
    {
        // Set font size in points
        if ($this->fontSizePt == $size) {
            return;
        }
        $this->fontSizePt = $size;
        $this->fontSize = $size / $this->k;
        if ($this->page > 0) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->currentFont['i'], $this->fontSizePt));
        }
    }

    public function addLink(): int
    {
        // Create a new internal link
        $n = count($this->links) + 1;
        $this->links[$n] = array(0, 0);

        return $n;
    }

    public function setLink($link, $y = 0, $page = -1): void
    {
        // Set destination of internal link
        if ($y == -1) {
            $y = $this->y;
        }
        if ($page == -1) {
            $page = $this->page;
        }
        $this->links[$link] = array($page, $y);
    }

    public function link($x, $y, $w, $h, $link): void
    {
        // Put a link on the page
        $this->pageLinks[$this->page][] = array(
            $x * $this->k,
            $this->hPt - $y * $this->k,
            $w * $this->k,
            $h * $this->k,
            $link
        );
    }

    public function text($x, $y, $txt): void
    {
        // Output a string
        if (!isset($this->currentFont)) {
            $this->error('No font has been set');
        }
        $s = sprintf('BT %.2F %.2F Td (%s) Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        if ($this->underline && $txt != '') {
            $s .= ' ' . $this->_dounderline($x, $y, $txt);
        }
        if ($this->colorFlag) {
            $s = 'q ' . $this->textColor . ' ' . $s . ' Q';
        }
        $this->_out($s);
    }

    public function acceptPageBreak(): bool
    {
        // Accept automatic page break or not
        return $this->autoPageBreak;
    }

    public function cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = ''): void
    {
        // Output a cell
        $k = $this->k;
        if ($this->y + $h > $this->pageBreakTrigger && !$this->inHeader && !$this->inFooter && $this->acceptPageBreak(
            )) {
            // Automatic page break
            $x = $this->x;
            $ws = $this->ws;
            if ($ws > 0) {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->addPage($this->curOrientation, $this->curPageSize, $this->curRotation);
            $this->x = $x;
            if ($ws > 0) {
                $this->ws = $ws;
                $this->_out(sprintf('%.3F Tw', $ws * $k));
            }
        }
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $s = '';
        if ($fill || $border == 1) {
            if ($fill) {
                $op = ($border == 1) ? 'B' : 'f';
            } else {
                $op = 'S';
            }
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ',
                $this->x * $k,
                ($this->h - $this->y) * $k,
                $w * $k,
                -$h * $k,
                $op
            );
        }
        if (is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if (strpos($border, 'L') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',
                    $x * $k,
                    ($this->h - $y) * $k,
                    $x * $k,
                    ($this->h - ($y + $h)) * $k
                );
            }
            if (strpos($border, 'T') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',
                    $x * $k,
                    ($this->h - $y) * $k,
                    ($x + $w) * $k,
                    ($this->h - $y) * $k
                );
            }
            if (strpos($border, 'R') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',
                    ($x + $w) * $k,
                    ($this->h - $y) * $k,
                    ($x + $w) * $k,
                    ($this->h - ($y + $h)) * $k
                );
            }
            if (strpos($border, 'B') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',
                    $x * $k,
                    ($this->h - ($y + $h)) * $k,
                    ($x + $w) * $k,
                    ($this->h - ($y + $h)) * $k
                );
            }
        }
        if ($txt !== '') {
            if (!isset($this->currentFont)) {
                $this->error('No font has been set');
            }
            if ($align == 'R') {
                $dx = $w - $this->cMargin - $this->getStringWidth($txt);
            } elseif ($align == 'C') {
                $dx = ($w - $this->getStringWidth($txt)) / 2;
            } else {
                $dx = $this->cMargin;
            }
            if ($this->colorFlag) {
                $s .= 'q ' . $this->textColor . ' ';
            }
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET',
                ($this->x + $dx) * $k,
                ($this->h - ($this->y + .5 * $h + .3 * $this->fontSize)) * $k,
                $this->_escape($txt)
            );
            if ($this->underline) {
                $s .= ' ' . $this->_dounderline($this->x + $dx, $this->y + .5 * $h + .3 * $this->fontSize, $txt);
            }
            if ($this->colorFlag) {
                $s .= ' Q';
            }
            if ($link) {
                $this->link($this->x + $dx,
                    $this->y + .5 * $h - .5 * $this->fontSize,
                    $this->getStringWidth($txt),
                    $this->fontSize,
                    $link
                );
            }
        }
        if ($s) {
            $this->_out($s);
        }
        $this->lasth = $h;
        if ($ln > 0) {
            // Go to next line
            $this->y += $h;
            if ($ln == 1) {
                $this->x = $this->lMargin;
            }
        } else {
            $this->x += $w;
        }
    }

    public function multiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false): void
    {
        // Output text with automatic or explicit line breaks
        if (!isset($this->currentFont)) {
            $this->error('No font has been set');
        }
        $cw = &$this->currentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->fontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        $b = 0;
        if ($border) {
            if ($border == 1) {
                $border = 'LTRB';
                $b = 'LRT';
                $b2 = 'LR';
            } else {
                $b2 = '';
                if (strpos($border, 'L') !== false) {
                    $b2 .= 'L';
                }
                if (strpos($border, 'R') !== false) {
                    $b2 .= 'R';
                }
                $b = (strpos($border, 'T') !== false) ? $b2 . 'T' : $b2;
            }
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $ns = 0;
        $nl = 1;
        while ($i < $nb) {
            // Get next character
            $c = $s[$i];
            if ($c == "\n") {
                // Explicit line break
                if ($this->ws > 0) {
                    $this->ws = 0;
                    $this->_out('0 Tw');
                }
                $this->cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl == 2) {
                    $b = $b2;
                }
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
                $ls = $l;
                $ns++;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                // Automatic line break
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                    if ($this->ws > 0) {
                        $this->ws = 0;
                        $this->_out('0 Tw');
                    }
                    $this->cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                } else {
                    if ($align == 'J') {
                        $this->ws = ($ns > 1) ? ($wmax - $ls) / 1000 * $this->fontSize / ($ns - 1) : 0;
                        $this->_out(sprintf('%.3F Tw', $this->ws * $this->k));
                    }
                    $this->cell($w, $h, substr($s, $j, $sep - $j), $b, 2, $align, $fill);
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl == 2) {
                    $b = $b2;
                }
            } else {
                $i++;
            }
        }
        // Last chunk
        if ($this->ws > 0) {
            $this->ws = 0;
            $this->_out('0 Tw');
        }
        if ($border && strpos($border, 'B') !== false) {
            $b .= 'B';
        }
        $this->cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
        $this->x = $this->lMargin;
    }

    public function write($h, $txt, $link = ''): void
    {
        // Output text in flowing mode
        if (!isset($this->currentFont)) {
            $this->error('No font has been set');
        }
        $cw = &$this->currentFont['cw'];
        $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->fontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            // Get next character
            $c = $s[$i];
            if ($c == "\n") {
                // Explicit line break
                $this->cell($w, $h, substr($s, $j, $i - $j), 0, 2, '', false, $link);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $this->x = $this->lMargin;
                    $w = $this->w - $this->rMargin - $this->x;
                    $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->fontSize;
                }
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                // Automatic line break
                if ($sep == -1) {
                    if ($this->x > $this->lMargin) {
                        // Move to next line
                        $this->x = $this->lMargin;
                        $this->y += $h;
                        $w = $this->w - $this->rMargin - $this->x;
                        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->fontSize;
                        $i++;
                        $nl++;
                        continue;
                    }
                    if ($i == $j) {
                        $i++;
                    }
                    $this->cell($w, $h, substr($s, $j, $i - $j), 0, 2, '', false, $link);
                } else {
                    $this->cell($w, $h, substr($s, $j, $sep - $j), 0, 2, '', false, $link);
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $this->x = $this->lMargin;
                    $w = $this->w - $this->rMargin - $this->x;
                    $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->fontSize;
                }
                $nl++;
            } else {
                $i++;
            }
        }
        // Last chunk
        if ($i != $j) {
            $this->cell($l / 1000 * $this->fontSize, $h, substr($s, $j), 0, 0, '', false, $link);
        }
    }

    public function ln($h = null): void
    {
        // Line feed; default value is the last cell height
        $this->x = $this->lMargin;
        if ($h === null) {
            $this->y += $this->lasth;
        } else {
            $this->y += $h;
        }
    }

    public function image(
        $file,
        $x = null,
        $y = null,
        $w = 0,
        $h = 0,
        $type = '',
        $link = '',
        $isMask = false,
        $maskImage = null
    ) {
        // Put an image on the page
        if ($file == '') {
            $this->error('Image file name is empty');
        }

        $info = $this->getFileInfo($file, $type, $isMask, $maskImage);

        if (!empty($info['alpha'])) {
            return $this->imagePngWithAlpha($file, $x, $y, $w, $h, $link);
        }

        // Automatic width and height calculation if needed
        list($w, $h) = $this->calculateWidthAndHeight($w, $h, $info);
        // Flowing mode
        $y = $this->applyFlowingMode($y, $h);

        if ($x === null) {
            $x = $this->x;
        }

        // embed hidden, ouside the canvas
        if ($isMask) {
            $x = ($this->curOrientation == 'P' ? $this->curPageSize[0] : $this->curPageSize[1]) + 10;
        }

        $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',
                $w * $this->k,
                $h * $this->k,
                $x * $this->k,
                ($this->h - ($y + $h)) * $this->k,
                $info['i']
            )
        );

        if ($link) {
            $this->link($x, $y, $w, $h, $link);
        }

        return $info['i'];
    }

    public function getPageWidth()
    {
        // Get current page width
        return $this->w;
    }

    public function getPageHeight()
    {
        // Get current page height
        return $this->h;
    }

    public function getX()
    {
        // Get x position
        return $this->x;
    }

    public function setX($x)
    {
        // Set x position
        if ($x >= 0) {
            $this->x = $x;
        } else {
            $this->x = $this->w + $x;
        }
    }

    public function getY()
    {
        // Get y position
        return $this->y;
    }

    public function setY($y, $resetX = true)
    {
        // Set y position and optionally reset x
        if ($y >= 0) {
            $this->y = $y;
        } else {
            $this->y = $this->h + $y;
        }
        if ($resetX) {
            $this->x = $this->lMargin;
        }
    }

    public function setXY($x, $y)
    {
        // Set x and y positions
        $this->setX($x);
        $this->setY($y, false);
    }

    public function output($dest = '', $name = '', $isUTF8 = false): string
    {
        // Output PDF to some destination
        $this->close();
        if (strlen($name) == 1 && strlen($dest) != 1) {
            // Fix parameter order
            $tmp = $dest;
            $dest = $name;
            $name = $tmp;
        }
        if ($dest == '') {
            $dest = 'I';
        }
        if ($name == '') {
            $name = 'doc.pdf';
        }
        switch (strtoupper($dest)) {
            case 'I':
                // Send to standard output
                $this->_checkoutput();
                if (PHP_SAPI != 'cli') {
                    // We send to a browser
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; ' . $this->_httpencode('filename', $name, $isUTF8));
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                }
                echo $this->buffer;
                break;
            case 'D':
                // Download file
                $this->_checkoutput();
                header('Content-Type: application/x-download');
                header('Content-Disposition: attachment; ' . $this->_httpencode('filename', $name, $isUTF8));
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                echo $this->buffer;
                break;
            case 'F':
                // Save to local file
                if (!file_put_contents($name, $this->buffer)) {
                    $this->error('Unable to create output file: ' . $name);
                }
                break;
            case 'S':
                // Return as a string
                return $this->buffer;
            default:
                $this->error('Incorrect output destination: ' . $dest);
        }

        return '';
    }

    protected function _dochecks()
    {
        // Check mbstring overloading
        if (ini_get('mbstring.func_overload') & 2) {
            $this->error('mbstring overloading must be disabled');
        }
    }

    protected function _checkoutput()
    {
        if (PHP_SAPI != 'cli') {
            if (headers_sent($file, $line)) {
                $this->error("Some data has already been output, can't send PDF file (output started at $file:$line)");
            }
        }
        if (ob_get_length()) {
            // The output buffer is not empty
            if (preg_match('/^(\xEF\xBB\xBF)?\s*$/', ob_get_contents())) {
                // It contains only a UTF-8 BOM and/or whitespace, let's clean it
                ob_clean();
            } else {
                $this->error("Some data has already been output, can't send PDF file");
            }
        }
    }

    protected function _getpagesize($size)
    {
        if (is_string($size)) {
            $size = strtolower($size);
            if (!isset($this->stdPageSizes[$size])) {
                $this->error('Unknown page size: ' . $size);
            }
            $a = $this->stdPageSizes[$size];

            return array($a[0] / $this->k, $a[1] / $this->k);
        } else {
            if ($size[0] > $size[1]) {
                return array($size[1], $size[0]);
            } else {
                return $size;
            }
        }
    }

    protected function _beginpage($orientation, $size, $rotation)
    {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->pageLinks[$this->page] = array();
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->fontFamily = '';
        // Check page size and orientation
        if ($orientation == '') {
            $orientation = $this->defOrientation;
        } else {
            $orientation = strtoupper($orientation[0]);
        }
        if ($size == '') {
            $size = $this->defPageSize;
        } else {
            $size = $this->_getpagesize($size);
        }
        if ($orientation != $this->curOrientation || $size[0] != $this->curPageSize[0] || $size[1] != $this->curPageSize[1]) {
            // New size or orientation
            if ($orientation == 'P') {
                $this->w = $size[0];
                $this->h = $size[1];
            } else {
                $this->w = $size[1];
                $this->h = $size[0];
            }
            $this->wPt = $this->w * $this->k;
            $this->hPt = $this->h * $this->k;
            $this->pageBreakTrigger = $this->h - $this->bMargin;
            $this->curOrientation = $orientation;
            $this->curPageSize = $size;
        }
        if ($orientation != $this->defOrientation || $size[0] != $this->defPageSize[0] || $size[1] != $this->defPageSize[1]) {
            $this->pageInfo[$this->page]['size'] = array($this->wPt, $this->hPt);
        }
        if ($rotation != 0) {
            if ($rotation % 90 != 0) {
                $this->error('Incorrect rotation value: ' . $rotation);
            }
            $this->curRotation = $rotation;
            $this->pageInfo[$this->page]['rotation'] = $rotation;
        }
    }

    protected function _endpage()
    {
        $this->state = 1;
    }

    protected function _loadfont($font): array
    {
        // Load a font definition file from the font directory
        if (strpos($font, '/') !== false || strpos($font, "\\") !== false) {
            $this->error('Incorrect font definition file name: ' . $font);
        }
        include($this->fontpath . $font);
        if (!isset($name)) {
            $this->error('Could not include font definition file');
        }
        if (isset($enc)) {
            $enc = strtolower($enc);
        }
        if (!isset($subsetted)) {
            $subsetted = false;
        }

        return get_defined_vars();
    }

    protected function _isascii($s): bool
    {
        // Test if string is ASCII
        $nb = strlen($s);
        for ($i = 0; $i < $nb; $i++) {
            if (ord($s[$i]) > 127) {
                return false;
            }
        }

        return true;
    }

    protected function _httpencode($param, $value, $isUTF8): string
    {
        // Encode HTTP header field parameter
        if ($this->_isascii($value)) {
            return $param . '="' . $value . '"';
        }
        if (!$isUTF8) {
            $value = utf8_encode($value);
        }
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
            return $param . '="' . rawurlencode($value) . '"';
        } else {
            return $param . "*=UTF-8''" . rawurlencode($value);
        }
    }

    protected function _UTF8toUTF16($s): string
    {
        // Convert UTF-8 to UTF-16BE with BOM
        $res = "\xFE\xFF";
        $nb = strlen($s);
        $i = 0;
        while ($i < $nb) {
            $c1 = ord($s[$i++]);
            if ($c1 >= 224) {
                // 3-byte character
                $c2 = ord($s[$i++]);
                $c3 = ord($s[$i++]);
                $res .= chr((($c1 & 0x0F) << 4) + (($c2 & 0x3C) >> 2));
                $res .= chr((($c2 & 0x03) << 6) + ($c3 & 0x3F));
            } elseif ($c1 >= 192) {
                // 2-byte character
                $c2 = ord($s[$i++]);
                $res .= chr(($c1 & 0x1C) >> 2);
                $res .= chr((($c1 & 0x03) << 6) + ($c2 & 0x3F));
            } else {
                // Single-byte character
                $res .= "\0" . chr($c1);
            }
        }

        return $res;
    }

    protected function _escape($s): string
    {
        // Escape special characters
        if (strpos($s, '(') !== false || strpos($s, ')') !== false || strpos($s, '\\') !== false || strpos($s,
                "\r"
            ) !== false) {
            return str_replace(array('\\', '(', ')', "\r"), array('\\\\', '\\(', '\\)', '\\r'), $s);
        } else {
            return $s;
        }
    }

    protected function _textstring($s): string
    {
        // Format a text string
        if (!$this->_isascii($s)) {
            $s = $this->_UTF8toUTF16($s);
        }

        return '(' . $this->_escape($s) . ')';
    }

    protected function _dounderline($x, $y, $txt): string
    {
        // Underline text
        $up = $this->currentFont['up'];
        $ut = $this->currentFont['ut'];
        $w = $this->getStringWidth($txt) + $this->ws * substr_count($txt, ' ');

        return sprintf('%.2F %.2F %.2F %.2F re f',
            $x * $this->k,
            ($this->h - ($y - $up / 1000 * $this->fontSize)) * $this->k,
            $w * $this->k,
            -$ut / 1000 * $this->fontSizePt
        );
    }

    protected function _parsejpg($file): array
    {
        // Extract info from a JPEG file
        $a = getimagesize($file);
        if (!$a) {
            $this->error('Missing or incorrect image file: ' . $file);
        }
        if ($a[2] != 2) {
            $this->error('Not a JPEG file: ' . $file);
        }
        if (!isset($a['channels']) || $a['channels'] == 3) {
            $colspace = 'DeviceRGB';
        } elseif ($a['channels'] == 4) {
            $colspace = 'DeviceCMYK';
        } else {
            $colspace = 'DeviceGray';
        }
        $bpc = isset($a['bits']) ? $a['bits'] : 8;
        $data = file_get_contents($file);

        return array('w' => $a[0], 'h' => $a[1], 'cs' => $colspace, 'bpc' => $bpc, 'f' => 'DCTDecode', 'data' => $data);
    }

    protected function _parsepng($file): array
    {
        // Extract info from a PNG file
        $f = fopen($file, 'rb');
        if (!$f) {
            $this->error('Can\'t open image file: ' . $file);
        }
        $info = $this->_parsepngstream($f, $file);
        fclose($f);

        return $info;
    }

    protected function _parsepngstream($f, $file): array
    {
        // Check signature
        if ($this->_readstream($f, 8) != chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10)) {
            $this->error('Not a PNG file: ' . $file);
        }
        // Read header chunk
        $this->_readstream($f, 4);
        if ($this->_readstream($f, 4) != 'IHDR') {
            $this->error('Incorrect PNG file: ' . $file);
        }
        $w = $this->_readint($f);
        $h = $this->_readint($f);
        $bpc = ord($this->_readstream($f, 1));
        $ct = ord($this->_readstream($f, 1));
        if ($ct == 0 || $ct == 4) {
            $colspace = 'DeviceGray';
        } elseif ($ct == 2 || $ct == 6) {
            $colspace = 'DeviceRGB';
        } elseif ($ct == 3) {
            $colspace = 'Indexed';
        } else {
            $this->error('Unknown color type: ' . $file);
        }
        if (ord($this->_readstream($f, 1)) != 0) {
            $this->error('Unknown compression method: ' . $file);
        }
        if (ord($this->_readstream($f, 1)) != 0) {
            $this->error('Unknown filter method: ' . $file);
        }
        if (ord($this->_readstream($f, 1)) != 0) {
            $this->error('Interlacing not supported: ' . $file);
        }
        $this->_readstream($f, 4);
        $dp = '/Predictor 15 /Colors ' . ($colspace == 'DeviceRGB' ? 3 : 1) . ' /BitsPerComponent ' . $bpc . ' /Columns ' . $w;

        // Scan chunks looking for palette, transparency and image data
        $pal = '';
        $trns = '';
        $data = '';
        do {
            $n = $this->_readint($f);
            $type = $this->_readstream($f, 4);
            if ($type == 'PLTE') {
                // Read palette
                $pal = $this->_readstream($f, $n);
                $this->_readstream($f, 4);
            } elseif ($type == 'tRNS') {
                // Read transparency info
                $t = $this->_readstream($f, $n);
                if ($ct == 0) {
                    $trns = array(ord(substr($t, 1, 1)));
                } elseif ($ct == 2) {
                    $trns = array(ord(substr($t, 1, 1)), ord(substr($t, 3, 1)), ord(substr($t, 5, 1)));
                } else {
                    $pos = strpos($t, chr(0));
                    if ($pos !== false) {
                        $trns = array($pos);
                    }
                }
                $this->_readstream($f, 4);
            } elseif ($type == 'IDAT') {
                // Read image data block
                $data .= $this->_readstream($f, $n);
                $this->_readstream($f, 4);
            } elseif ($type == 'IEND') {
                break;
            } else {
                $this->_readstream($f, $n + 4);
            }
        } while ($n);

        if ($colspace == 'Indexed' && empty($pal)) {
            $this->error('Missing palette in ' . $file);
        }
        $info = array(
            'w' => $w,
            'h' => $h,
            'cs' => $colspace,
            'bpc' => $bpc,
            'f' => 'FlateDecode',
            'dp' => $dp,
            'pal' => $pal,
            'trns' => $trns
        );
        if ($ct >= 4) {
            // Extract alpha channel
            if (!function_exists('gzuncompress')) {
                $this->error('Zlib not available, can\'t handle alpha channel: ' . $file);
            }
            $data = gzuncompress($data);
            $color = '';
            $alpha = '';
            if ($ct == 4) {
                // Gray image
                $len = 2 * $w;
                for ($i = 0; $i < $h; $i++) {
                    $pos = (1 + $len) * $i;
                    $color .= $data[$pos];
                    $alpha .= $data[$pos];
                    $line = substr($data, $pos + 1, $len);
                    $color .= preg_replace('/(.)./s', '$1', $line);
                    $alpha .= preg_replace('/.(.)/s', '$1', $line);
                }
            } else {
                // RGB image
                $len = 4 * $w;
                for ($i = 0; $i < $h; $i++) {
                    $pos = (1 + $len) * $i;
                    $color .= $data[$pos];
                    $alpha .= $data[$pos];
                    $line = substr($data, $pos + 1, $len);
                    $color .= preg_replace('/(.{3})./s', '$1', $line);
                    $alpha .= preg_replace('/.{3}(.)/s', '$1', $line);
                }
            }
            unset($data);
            $data = gzcompress($color);
            $info['smask'] = gzcompress($alpha);
            $this->withAlpha = true;
            if ($this->pDFVersion < '1.4') {
                $this->pDFVersion = '1.4';
            }
        }
        $info['data'] = $data;

        return $info;
    }

    protected function _readstream($f, $n): string
    {
        // Read n bytes from stream
        $res = '';
        while ($n > 0 && !feof($f)) {
            $s = fread($f, $n);
            if ($s === false) {
                $this->error('Error while reading stream');
            }
            $n -= strlen($s);
            $res .= $s;
        }
        if ($n > 0) {
            $this->error('Unexpected end of stream');
        }

        return $res;
    }

    protected function _readint($f): int
    {
        // Read a 4-byte integer from stream
        $a = unpack('Ni', $this->_readstream($f, 4));

        return $a['i'];
    }

    protected function _parsegif($file): array
    {
        // Extract info from a GIF file (via PNG conversion)
        if (!function_exists('imagepng')) {
            $this->error('GD extension is required for GIF support');
        }
        if (!function_exists('imagecreatefromgif')) {
            $this->error('GD has no GIF read support');
        }
        $im = imagecreatefromgif($file);
        if (!$im) {
            $this->error('Missing or incorrect image file: ' . $file);
        }
        imageinterlace($im, 0);
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);
        $f = fopen('php://temp', 'rb+');
        if (!$f) {
            $this->error('Unable to create memory stream');
        }
        fwrite($f, $data);
        rewind($f);
        $info = $this->_parsepngstream($f, $file);
        fclose($f);

        return $info;
    }

    protected function _out($s): void
    {
        // Add a line to the document
        if ($this->state == 2) {
            $this->pages[$this->page] .= $s . "\n";
        } elseif ($this->state == 1) {
            $this->_put($s);
        } elseif ($this->state == 0) {
            $this->error('No page has been added yet');
        } elseif ($this->state == 3) {
            $this->error('The document is closed');
        }
    }

    protected function _put($s): void
    {
        $this->buffer .= $s . "\n";
    }

    protected function _getoffset(): int
    {
        return strlen($this->buffer);
    }

    protected function _newobj($n = null): void
    {
        // Begin a new object
        if ($n === null) {
            $n = ++$this->n;
        }
        $this->offsets[$n] = $this->_getoffset();
        $this->_put($n . ' 0 obj');
    }

    protected function _putstream($data): void
    {
        $this->_put('stream');
        $this->_put($data);
        $this->_put('endstream');
    }

    protected function _putstreamobject($data): void
    {
        if ($this->compress) {
            $entries = '/Filter /FlateDecode ';
            $data = gzcompress($data);
        } else {
            $entries = '';
        }
        $entries .= '/Length ' . strlen($data);
        $this->_newobj();
        $this->_put('<<' . $entries . '>>');
        $this->_putstream($data);
        $this->_put('endobj');
    }

    protected function _putpage($n): void
    {
        $this->_newobj();
        $this->_put('<</Type /Page');
        $this->_put('/Parent 1 0 R');
        if (isset($this->pageInfo[$n]['size'])) {
            $this->_put(sprintf('/MediaBox [0 0 %.2F %.2F]',
                    $this->pageInfo[$n]['size'][0],
                    $this->pageInfo[$n]['size'][1]
                )
            );
        }
        if (isset($this->pageInfo[$n]['rotation'])) {
            $this->_put('/Rotate ' . $this->pageInfo[$n]['rotation']);
        }
        $this->_put('/Resources 2 0 R');
        if (!empty($this->pageLinks[$n])) {
            $s = '/Annots [';
            foreach ($this->pageLinks[$n] as $pl) {
                $s .= $pl[5] . ' 0 R ';
            }
            $s .= ']';
            $this->_put($s);
        }
        if ($this->withAlpha) {
            $this->_put('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
        }
        $this->_put('/Contents ' . ($this->n + 1) . ' 0 R>>');
        $this->_put('endobj');
        // Page content
        if (!empty($this->aliasNbPages)) {
            $this->pages[$n] = str_replace($this->aliasNbPages, $this->page, $this->pages[$n]);
        }
        $this->_putstreamobject($this->pages[$n]);
        // Annotations
        foreach ($this->pageLinks[$n] as $pl) {
            $this->_newobj();
            $rect = sprintf('%.2F %.2F %.2F %.2F', $pl[0], $pl[1], $pl[0] + $pl[2], $pl[1] - $pl[3]);
            $s = '<</Type /Annot /Subtype /Link /Rect [' . $rect . '] /Border [0 0 0] ';
            if (is_string($pl[4])) {
                $s .= '/A <</S /URI /URI ' . $this->_textstring($pl[4]) . '>>>>';
            } else {
                $l = $this->links[$pl[4]];
                if (isset($this->pageInfo[$l[0]]['size'])) {
                    $h = $this->pageInfo[$l[0]]['size'][1];
                } else {
                    $h = ($this->defOrientation == 'P') ? $this->defPageSize[1] * $this->k : $this->defPageSize[0] * $this->k;
                }
                $s .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>', $this->pageInfo[$l[0]]['n'], $h - $l[1] * $this->k);
            }
            $this->_put($s);
            $this->_put('endobj');
        }
    }

    protected function _putpages(): void
    {
        $nb = $this->page;
        $n = $this->n;
        for ($i = 1; $i <= $nb; $i++) {
            $this->pageInfo[$i]['n'] = ++$n;
            $n++;
            foreach ($this->pageLinks[$i] as &$pl) {
                $pl[5] = ++$n;
            }
            unset($pl);
        }
        for ($i = 1; $i <= $nb; $i++) {
            $this->_putpage($i);
        }
        // Pages root
        $this->_newobj(1);
        $this->_put('<</Type /Pages');
        $kids = '/Kids [';
        for ($i = 1; $i <= $nb; $i++) {
            $kids .= $this->pageInfo[$i]['n'] . ' 0 R ';
        }
        $kids .= ']';
        $this->_put($kids);
        $this->_put('/Count ' . $nb);
        if ($this->defOrientation == 'P') {
            $w = $this->defPageSize[0];
            $h = $this->defPageSize[1];
        } else {
            $w = $this->defPageSize[1];
            $h = $this->defPageSize[0];
        }
        $this->_put(sprintf('/MediaBox [0 0 %.2F %.2F]', $w * $this->k, $h * $this->k));
        $this->_put('>>');
        $this->_put('endobj');
    }

    protected function _putfonts(): void
    {
        foreach ($this->fontFiles as $file => $info) {
            // Font file embedding
            $this->_newobj();
            $this->fontFiles[$file]['n'] = $this->n;
            $font = file_get_contents($this->fontpath . $file, true);
            if (!$font) {
                $this->error('Font file not found: ' . $file);
            }
            $compressed = (substr($file, -2) == '.z');
            if (!$compressed && isset($info['length2'])) {
                $font = substr($font, 6, $info['length1']) . substr($font, 6 + $info['length1'] + 6, $info['length2']);
            }
            $this->_put('<</Length ' . strlen($font));
            if ($compressed) {
                $this->_put('/Filter /FlateDecode');
            }
            $this->_put('/Length1 ' . $info['length1']);
            if (isset($info['length2'])) {
                $this->_put('/Length2 ' . $info['length2'] . ' /Length3 0');
            }
            $this->_put('>>');
            $this->_putstream($font);
            $this->_put('endobj');
        }
        foreach ($this->fonts as $k => $font) {
            // Encoding
            if (isset($font['diff'])) {
                if (!isset($this->encodings[$font['enc']])) {
                    $this->_newobj();
                    $this->_put('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [' . $font['diff'] . ']>>'
                    );
                    $this->_put('endobj');
                    $this->encodings[$font['enc']] = $this->n;
                }
            }
            // ToUnicode CMap
            if (isset($font['uv'])) {
                if (isset($font['enc'])) {
                    $cmapkey = $font['enc'];
                } else {
                    $cmapkey = $font['name'];
                }
                if (!isset($this->cmaps[$cmapkey])) {
                    $cmap = $this->_tounicodecmap($font['uv']);
                    $this->_putstreamobject($cmap);
                    $this->cmaps[$cmapkey] = $this->n;
                }
            }
            // Font object
            $this->fonts[$k]['n'] = $this->n + 1;
            $type = $font['type'];
            $name = $font['name'];
            if ($font['subsetted']) {
                $name = 'AAAAAA+' . $name;
            }
            if ($type == 'Core') {
                // Core font
                $this->_newobj();
                $this->_put('<</Type /Font');
                $this->_put('/BaseFont /' . $name);
                $this->_put('/Subtype /Type1');
                if ($name != 'Symbol' && $name != 'ZapfDingbats') {
                    $this->_put('/Encoding /WinAnsiEncoding');
                }
                if (isset($font['uv'])) {
                    $this->_put('/ToUnicode ' . $this->cmaps[$cmapkey] . ' 0 R');
                }
                $this->_put('>>');
                $this->_put('endobj');
            } elseif ($type == 'Type1' || $type == 'TrueType') {
                // Additional Type1 or TrueType/OpenType font
                $this->_newobj();
                $this->_put('<</Type /Font');
                $this->_put('/BaseFont /' . $name);
                $this->_put('/Subtype /' . $type);
                $this->_put('/FirstChar 32 /LastChar 255');
                $this->_put('/Widths ' . ($this->n + 1) . ' 0 R');
                $this->_put('/FontDescriptor ' . ($this->n + 2) . ' 0 R');
                if (isset($font['diff'])) {
                    $this->_put('/Encoding ' . $this->encodings[$font['enc']] . ' 0 R');
                } else {
                    $this->_put('/Encoding /WinAnsiEncoding');
                }
                if (isset($font['uv'])) {
                    $this->_put('/ToUnicode ' . $this->cmaps[$cmapkey] . ' 0 R');
                }
                $this->_put('>>');
                $this->_put('endobj');
                // Widths
                $this->_newobj();
                $cw = &$font['cw'];
                $s = '[';
                for ($i = 32; $i <= 255; $i++) {
                    $s .= $cw[chr($i)] . ' ';
                }
                $this->_put($s . ']');
                $this->_put('endobj');
                // Descriptor
                $this->_newobj();
                $s = '<</Type /FontDescriptor /FontName /' . $name;
                foreach ($font['desc'] as $kk => $v) {
                    $s .= ' /' . $kk . ' ' . $v;
                }
                if (!empty($font['file'])) {
                    $s .= ' /FontFile' . ($type == 'Type1' ? '' : '2') . ' ' . $this->fontFiles[$font['file']]['n'] . ' 0 R';
                }
                $this->_put($s . '>>');
                $this->_put('endobj');
            } else {
                // Allow for additional types
                $mtd = '_put' . strtolower($type);
                if (!method_exists($this, $mtd)) {
                    $this->error('Unsupported font type: ' . $type);
                }
                $this->$mtd($font);
            }
        }
    }

    protected function _tounicodecmap($uv): string
    {
        $ranges = '';
        $nbr = 0;
        $chars = '';
        $nbc = 0;
        foreach ($uv as $c => $v) {
            if (is_array($v)) {
                $ranges .= sprintf("<%02X> <%02X> <%04X>\n", $c, $c + $v[1] - 1, $v[0]);
                $nbr++;
            } else {
                $chars .= sprintf("<%02X> <%04X>\n", $c, $v);
                $nbc++;
            }
        }
        $s = "/CIDInit /ProcSet findresource begin\n";
        $s .= "12 dict begin\n";
        $s .= "begincmap\n";
        $s .= "/CIDSystemInfo\n";
        $s .= "<</Registry (Adobe)\n";
        $s .= "/Ordering (UCS)\n";
        $s .= "/Supplement 0\n";
        $s .= ">> def\n";
        $s .= "/CMapName /Adobe-Identity-UCS def\n";
        $s .= "/CMapType 2 def\n";
        $s .= "1 begincodespacerange\n";
        $s .= "<00> <FF>\n";
        $s .= "endcodespacerange\n";
        if ($nbr > 0) {
            $s .= "$nbr beginbfrange\n";
            $s .= $ranges;
            $s .= "endbfrange\n";
        }
        if ($nbc > 0) {
            $s .= "$nbc beginbfchar\n";
            $s .= $chars;
            $s .= "endbfchar\n";
        }
        $s .= "endcmap\n";
        $s .= "CMapName currentdict /CMap defineresource pop\n";
        $s .= "end\n";
        $s .= "end";

        return $s;
    }

    protected function _putImages(): void
    {
        foreach (array_keys($this->images) as $file) {
            $this->_putImage($this->images[$file]);
            unset($this->images[$file]['data']);
            unset($this->images[$file]['smask']);
        }
    }

    protected function _putImage(&$info): void
    {
        $this->_newobj();
        $info['n'] = $this->n;
        $this->_put('<</Type /XObject');
        $this->_put('/Subtype /Image');
        $this->_put('/Width ' . $info['w']);
        $this->_put('/Height ' . $info['h']);
        if ($info['cs'] == 'Indexed') {
            $this->_put('/ColorSpace [/Indexed /DeviceRGB ' . (strlen($info['pal']
                    ) / 3 - 1) . ' ' . ($this->n + 1) . ' 0 R]'
            );
        } else {
            $this->_put('/ColorSpace /' . $info['cs']);
            if ($info['cs'] == 'DeviceCMYK') {
                $this->_put('/Decode [1 0 1 0 1 0 1 0]');
            }
        }
        $this->_put('/BitsPerComponent ' . $info['bpc']);
        if (isset($info['f'])) {
            $this->_put('/Filter /' . $info['f']);
        }
        if (isset($info['dp'])) {
            $this->_put('/DecodeParms <<' . $info['dp'] . '>>');
        }
        if (isset($info['trns']) && is_array($info['trns'])) {
            $trns = '';
            for ($i = 0; $i < count($info['trns']); $i++) {
                $trns .= $info['trns'][$i] . ' ' . $info['trns'][$i] . ' ';
            }
            $this->_put('/Mask [' . $trns . ']');
        }
        if (isset($info['smask'])) {
            $this->_put('/SMask ' . ($this->n + 1) . ' 0 R');
        }
        $this->_put('/Length ' . strlen($info['data']) . '>>');
        $this->_putstream($info['data']);
        $this->_put('endobj');
        // Soft mask
        if (isset($info['smask'])) {
            $dp = '/Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns ' . $info['w'];
            $smask = array(
                'w' => $info['w'],
                'h' => $info['h'],
                'cs' => 'DeviceGray',
                'bpc' => 8,
                'f' => $info['f'],
                'dp' => $dp,
                'data' => $info['smask']
            );
            $this->_putImage($smask);
        }
        // Palette
        if ($info['cs'] == 'Indexed') {
            $this->_putstreamobject($info['pal']);
        }
    }

    protected function _putxobjectdict(): void
    {
        foreach ($this->images as $image) {
            $this->_put('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
        }
    }

    protected function _putresourcedict(): void
    {
        $this->_put('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
        $this->_put('/Font <<');
        foreach ($this->fonts as $font) {
            $this->_put('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
        }
        $this->_put('>>');
        $this->_put('/XObject <<');
        $this->_putxobjectdict();
        $this->_put('>>');
    }

    protected function _putresources(): void
    {
        $this->_putfonts();
        $this->_putImages();
        // Resource dictionary
        $this->_newobj(2);
        $this->_put('<<');
        $this->_putresourcedict();
        $this->_put('>>');
        $this->_put('endobj');
    }

    protected function _putinfo(): void
    {
        $this->metadata['Producer'] = 'FPDF ' . FPDF_VERSION;
        $this->metadata['CreationDate'] = 'D:' . @date('YmdHis');
        foreach ($this->metadata as $key => $value) {
            $this->_put('/' . $key . ' ' . $this->_textstring($value));
        }
    }

    protected function _putcatalog(): void
    {
        $n = $this->pageInfo[1]['n'];
        $this->_put('/Type /Catalog');
        $this->_put('/Pages 1 0 R');
        if ($this->zoomMode == 'fullpage') {
            $this->_put('/OpenAction [' . $n . ' 0 R /Fit]');
        } elseif ($this->zoomMode == 'fullwidth') {
            $this->_put('/OpenAction [' . $n . ' 0 R /FitH null]');
        } elseif ($this->zoomMode == 'real') {
            $this->_put('/OpenAction [' . $n . ' 0 R /XYZ null null 1]');
        } elseif (!is_string($this->zoomMode)) {
            $this->_put('/OpenAction [' . $n . ' 0 R /XYZ null null ' . sprintf('%.2F', $this->zoomMode / 100) . ']');
        }
        if ($this->layoutMode == 'single') {
            $this->_put('/PageLayout /SinglePage');
        } elseif ($this->layoutMode == 'continuous') {
            $this->_put('/PageLayout /OneColumn');
        } elseif ($this->layoutMode == 'two') {
            $this->_put('/PageLayout /TwoColumnLeft');
        }
    }

    protected function _putheader(): void
    {
        $this->_put('%PDF-' . $this->pDFVersion);
    }

    protected function _puttrailer(): void
    {
        $this->_put('/Size ' . ($this->n + 1));
        $this->_put('/Root ' . $this->n . ' 0 R');
        $this->_put('/Info ' . ($this->n - 1) . ' 0 R');
    }

    protected function _enddoc(): void
    {
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();
        // Info
        $this->_newobj();
        $this->_put('<<');
        $this->_putinfo();
        $this->_put('>>');
        $this->_put('endobj');
        // Catalog
        $this->_newobj();
        $this->_put('<<');
        $this->_putcatalog();
        $this->_put('>>');
        $this->_put('endobj');
        // Cross-ref
        $offset = $this->_getoffset();
        $this->_put('xref');
        $this->_put('0 ' . ($this->n + 1));
        $this->_put('0000000000 65535 f ');
        for ($i = 1; $i <= $this->n; $i++) {
            $this->_put(sprintf('%010d 00000 n ', $this->offsets[$i]));
        }
        // Trailer
        $this->_put('trailer');
        $this->_put('<<');
        $this->_puttrailer();
        $this->_put('>>');
        $this->_put('startxref');
        $this->_put($offset);
        $this->_put('%%EOF');
        $this->state = 3;
    }

    protected function getFileInfo($file, string $type, $isMask = false, $maskImage = null): array
    {
        if (!isset($this->images[$file])) {
            // First use of this image, get info
            if ($type == '') {
                $pos = strrpos($file, '.');
                if (!$pos) {
                    $this->error('Image file has no extension and no type was specified: ' . $file);
                }
                $type = substr($file, $pos + 1);
            }
            $type = strtolower($type);
            if ($type == 'jpeg') {
                $type = 'jpg';
            }
            $mtd = '_parse' . $type;
            if (!method_exists($this, $mtd)) {
                $this->error('Unsupported image type: ' . $type);
            }
            $info = $this->$mtd($file);

            if ($isMask) {
                $info['cs'] = "DeviceGray"; // try to force grayscale (instead of indexed)
            }

            $info['i'] = count($this->images) + 1;

            if (!is_null($maskImage)) {
                $info['masked'] = $maskImage;
            }

            $this->images[$file] = $info;
        } else {
            $info = $this->images[$file];
        }

        return $info;
    }

    protected function calculateWidthAndHeight($w, $h, array $info): array
    {
        if ($w == 0 && $h == 0) {
            // Put image at 96 dpi
            $w = -96;
            $h = -96;
        }
        if ($w < 0) {
            $w = -$info['w'] * 72 / $w / $this->k;
        }
        if ($h < 0) {
            $h = -$info['h'] * 72 / $h / $this->k;
        }
        if ($w == 0) {
            $w = $h * $info['w'] / $info['h'];
        }
        if ($h == 0) {
            $h = $w * $info['h'] / $info['w'];
        }

        return array($w, $h);
    }

    protected function applyFlowingMode($y, $h): int
    {
        if ($y === null) {
            if ($this->y + $h > $this->pageBreakTrigger && !$this->inHeader && !$this->inFooter && $this->acceptPageBreak(
                )) {
                // Automatic page break
                $x2 = $this->x;
                $this->addPage($this->curOrientation, $this->curPageSize, $this->curRotation);
                $this->x = $x2;
            }
            $y = $this->y;
            $this->y += $h;
        }

        return $y;
    }

    /**
     * Needs GD 2.x extension
     * Pixel-wise operation, not very fast
     */
    protected function imagePngWithAlpha($file, $x, $y, $w = 0, $h = 0, $link = ''): int
    {
        $tmp_alpha = tempnam('.', 'mska');
        $this->tmpFiles[] = $tmp_alpha;
        $tmp_plain = tempnam('.', 'mskp');
        $this->tmpFiles[] = $tmp_plain;

        list($wpx, $hpx) = getimagesize($file);
        $img = imagecreatefrompng($file);
        $alpha_img = imagecreate($wpx, $hpx);

        // generate gray scale pallete
        for ($c = 0; $c < 256; $c++) {
            ImageColorAllocate($alpha_img, $c, $c, $c);
        }

        // extract alpha channel
        $xpx = 0;
        while ($xpx < $wpx) {
            $ypx = 0;
            while ($ypx < $hpx) {
                $color_index = imagecolorat($img, $xpx, $ypx);
                $alpha = 255 - ($color_index >> 24) * 255 / 127; // GD alpha component: 7 bit only, 0..127!
                imagesetpixel($alpha_img, $xpx, $ypx, $alpha);
                ++$ypx;
            }
            ++$xpx;
        }

        imagepng($alpha_img, $tmp_alpha);
        imagedestroy($alpha_img);

        // extract image without alpha channel
        $plain_img = imagecreatetruecolor($wpx, $hpx);
        imagecopy($plain_img, $img, 0, 0, 0, 0, $wpx, $hpx);
        imagepng($plain_img, $tmp_plain);
        imagedestroy($plain_img);

        //first embed mask image (w, h, x, will be ignored)
        $maskImg = $this->image($tmp_alpha, 0, 0, 0, 0, 'PNG', '', true);

        //embed image, masked with previously embedded mask
        return $this->image($tmp_plain, $x, $y, $w, $h, 'PNG', $link, false, $maskImg);
    }
}
