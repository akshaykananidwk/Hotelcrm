<?php
namespace App\Services;

/**
 * Minimal, dependency-free PDF writer. Produces a single-page (auto-paginating)
 * A4 document from text lines — enough for GST invoices and receipts without
 * pulling in a third-party library. For richer layouts swap in FPDF/Dompdf via
 * this same interface; callers only use addLine()/output().
 */
class PdfService
{
    private array $lines = [];
    private float $fontSize = 10;

    public function heading(string $text): self
    {
        $this->lines[] = ['text' => $text, 'size' => 16, 'gap' => 8];
        return $this;
    }

    public function line(string $text = '', float $size = 10, float $gap = 4): self
    {
        $this->lines[] = ['text' => $text, 'size' => $size, 'gap' => $gap];
        return $this;
    }

    public function rule(): self
    {
        $this->lines[] = ['text' => str_repeat('_', 80), 'size' => 8, 'gap' => 6];
        return $this;
    }

    /** Build the raw PDF byte string. */
    public function output(): string
    {
        $pageHeight = 842; // A4 points
        $pageWidth = 595;
        $margin = 50;
        $y = $pageHeight - $margin;

        $pages = [];
        $content = '';
        foreach ($this->lines as $l) {
            $size = $l['size'];
            if ($y < $margin) {
                $pages[] = $content;
                $content = '';
                $y = $pageHeight - $margin;
            }
            $text = $this->escape($l['text']);
            $content .= "BT /F1 {$size} Tf {$margin} {$y} Td ({$text}) Tj ET\n";
            $y -= ($size + $l['gap']);
        }
        $pages[] = $content;

        // Assemble PDF objects.
        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $kids = [];
        $pageObjNums = [];
        $objNum = 3;
        $contentObjs = [];
        foreach ($pages as $pageContent) {
            $stream = $pageContent;
            $contentNum = $objNum + 1;
            $objects[$objNum] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] "
                . "/Resources << /Font << /F1 " . ($objNum + 2) . " 0 R >> >> /Contents {$contentNum} 0 R >>";
            $objects[$contentNum] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
            $objects[$objNum + 2] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
            $kids[] = "{$objNum} 0 R";
            $objNum += 3;
        }
        $objects[2] = "<< /Type /Pages /Count " . count($pages) . " /Kids [" . implode(' ', $kids) . "] >>";

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $maxObj = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxObj + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $maxObj; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . ($maxObj + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";
        return $pdf;
    }

    /** Write the PDF to disk and return its path. */
    public function save(string $path): string
    {
        file_put_contents($path, $this->output());
        return $path;
    }

    private function escape(string $text): string
    {
        // Keep to WinAnsi-safe ASCII; strip characters the base font can't show.
        $text = str_replace('₹', 'Rs.', $text);
        $text = preg_replace('/[^\x20-\x7E]/', '', $text);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string) $text);
    }
}
