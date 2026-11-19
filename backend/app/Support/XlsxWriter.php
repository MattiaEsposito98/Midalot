<?php

namespace App\Support;

use ZipArchive;

/**
 * Scrittore minimale di file .xlsx (un solo foglio) senza librerie esterne.
 * Larghezza delle colonne calcolata sul contenuto, intestazione in grassetto,
 * prima riga bloccata e filtri attivi. Le stringhe sono "inline": mai interpretate come formule.
 */
class XlsxWriter
{
    private const MAX_WIDTH = 60;
    private const MIN_WIDTH = 8;

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     * @return string percorso di un file temporaneo (da cancellare dopo l'uso)
     */
    public static function build(array $headers, iterable $rows, string $sheetName = 'Foglio1'): string
    {
        $widths = array_map(fn ($h) => self::len((string) $h) + 3, $headers);
        $sheetRows = [self::row(1, $headers, true)];
        $n = 1;

        foreach ($rows as $row) {
            $n++;
            $values = array_values($row);
            foreach ($values as $i => $v) {
                $widths[$i] = max($widths[$i] ?? 0, self::longestLine((string) $v) + 2);
            }
            $sheetRows[] = self::row($n, $values, false);
        }

        $cols = '';
        foreach ($headers as $i => $_) {
            $w = max(self::MIN_WIDTH, min(self::MAX_WIDTH, $widths[$i] ?? self::MIN_WIDTH));
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }

        $last = self::col(count($headers) - 1) . max($n, 1);
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData>' . implode('', $sheetRows) . '</sheetData>'
            . '<autoFilter ref="A1:' . $last . '"/>'
            . '</worksheet>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8EEF3"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs></styleSheet>';

        $name = htmlspecialchars(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $sheetName), 0, 31), ENT_XML1);

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $name . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return $path;
    }

    private static function row(int $r, array $values, bool $header): string
    {
        $xml = '<row r="' . $r . '">';
        foreach (array_values($values) as $i => $v) {
            $ref = self::col($i) . $r;
            $style = $header ? 1 : 2;
            if (! $header && (is_int($v) || is_float($v))) {
                $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $v . '</v></c>';
            } elseif ($v === null || $v === '') {
                $xml .= '<c r="' . $ref . '" s="' . $style . '"/>';
            } else {
                $text = preg_replace('/[^\P{C}\n\t]/u', '', (string) $v) ?? '';
                $xml .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
                    . htmlspecialchars($text, ENT_XML1) . '</t></is></c>';
            }
        }

        return $xml . '</row>';
    }

    private static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }

        return $s;
    }

    private static function len(string $s): int
    {
        return mb_strlen($s);
    }

    private static function longestLine(string $s): int
    {
        return max(array_map([self::class, 'len'], preg_split('/\R/', $s) ?: ['']));
    }
}
