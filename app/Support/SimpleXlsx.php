<?php

namespace App\Support;

/**
 * Minimal .xlsx writer (no external library): several sheets of plain rows,
 * right-to-left, bold header row, numbers kept as numbers so Excel can sum them.
 */
class SimpleXlsx
{
    /** @var array<string, array{rows: array<int, array>, widths: array<int, int>}> */
    private array $sheets = [];

    public function addSheet(string $name, array $rows, array $widths = []): self
    {
        $name = mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '', $name), 0, 31) ?: 'Sheet' . (count($this->sheets) + 1);
        $this->sheets[$name] = ['rows' => $rows, 'widths' => $widths];
        return $this;
    }

    public function save(string $path): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create xlsx');
        }

        $n = count($this->sheets);
        $overrides = '';
        $sheetsXml = '';
        $rels = '';
        for ($i = 1; $i <= $n; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides . '</Types>');

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $i = 0;
        foreach (array_keys($this->sheets) as $name) {
            $i++;
            $sheetsXml .= '<sheet name="' . $this->esc($name) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
        }
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetsXml . '</sheets></workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels
            . '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');

        // style 0 = normal, 1 = bold header with fill, 2 = number with 2 decimals
        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="0.00"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Arial"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0A2540"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>');

        $i = 0;
        foreach ($this->sheets as $sheet) {
            $i++;
            $zip->addFromString('xl/worksheets/sheet' . $i . '.xml', $this->sheetXml($sheet['rows'], $sheet['widths']));
        }
        $zip->close();
    }

    private function sheetXml(array $rows, array $widths): string
    {
        $cols = '';
        foreach ($widths as $idx => $w) {
            $c = $idx + 1;
            $cols .= '<col min="' . $c . '" max="' . $c . '" width="' . (int) $w . '" customWidth="1"/>';
        }
        $data = '';
        foreach (array_values($rows) as $r => $row) {
            $rowNum = $r + 1;
            $data .= '<row r="' . $rowNum . '">';
            foreach (array_values($row) as $c => $value) {
                $ref = $this->col($c) . $rowNum;
                $style = $r === 0 ? ' s="1"' : '';
                if ($r > 0 && (is_int($value) || is_float($value))) {
                    $data .= '<c r="' . $ref . '"' . (is_float($value) ? ' s="2"' : '') . '><v>' . $value . '</v></c>';
                } elseif ($value === null || $value === '') {
                    $data .= '<c r="' . $ref . '"' . $style . '/>';
                } else {
                    $data .= '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . $this->esc((string) $value) . '</t></is></c>';
                }
            }
            $data .= '</row>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" rightToLeft="1"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . ($cols ? '<cols>' . $cols . '</cols>' : '')
            . '<sheetData>' . $data . '</sheetData></worksheet>';
    }

    private function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private function esc(string $v): string
    {
        // strip characters that are invalid in XML
        $v = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $v) ?? '';
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
