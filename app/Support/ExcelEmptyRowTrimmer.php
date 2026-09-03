<?php

namespace App\Support;

use ZipArchive;

/**
 * Excel often stores tens of thousands of empty formatted rows (used range
 * A1:K49978). PhpSpreadsheet then times out while reading them. This keeps
 * only rows that have a cell value before import.
 */
class ExcelEmptyRowTrimmer
{
    /**
     * @return array{kept: int, removed: int, original: int}
     */
    public static function trim(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return ['kept' => 0, 'removed' => 0, 'original' => 0];
        }

        $sheetName = self::firstWorksheetPath($zip);
        if ($sheetName === null) {
            $zip->close();

            return ['kept' => 0, 'removed' => 0, 'original' => 0];
        }

        $xml = $zip->getFromName($sheetName);
        if (! is_string($xml) || $xml === '') {
            $zip->close();

            return ['kept' => 0, 'removed' => 0, 'original' => 0];
        }

        $original = preg_match_all('/<row\b/i', $xml) ?: 0;
        if ($original < 2000) {
            $zip->close();

            return ['kept' => $original, 'removed' => 0, 'original' => $original];
        }

        $keptRows = [];
        $lastCol = 'A';
        if (preg_match_all('/<row\b[^>]*>.*?<\/row>/is', $xml, $matches)) {
            foreach ($matches[0] as $rowXml) {
                if (! self::rowHasValue($rowXml)) {
                    continue;
                }
                $keptRows[] = $rowXml;
                if (preg_match_all('/\br="([A-Z]{1,3})\d+"/i', $rowXml, $cols)) {
                    foreach ($cols[1] as $col) {
                        if (strlen($col) > strlen($lastCol) || (strlen($col) === strlen($lastCol) && strcmp($col, $lastCol) > 0)) {
                            $lastCol = $col;
                        }
                    }
                }
            }
        }

        $kept = count($keptRows);
        $removed = max(0, $original - $kept);
        if ($removed === 0 || $kept === 0) {
            $zip->close();

            return ['kept' => $kept, 'removed' => 0, 'original' => $original];
        }

        $sheetData = '<sheetData>'.implode('', $keptRows).'</sheetData>';
        $xml = preg_replace('/<sheetData\b[^>]*>.*?<\/sheetData>/is', $sheetData, $xml, 1) ?? $xml;
        $xml = preg_replace(
            '/<dimension\b[^>]*\/>/i',
            '<dimension ref="A1:'.$lastCol.max($kept, 1).'"/>',
            $xml,
            1
        ) ?? $xml;

        $zip->addFromString($sheetName, $xml);
        $zip->close();

        return ['kept' => $kept, 'removed' => $removed, 'original' => $original];
    }

    protected static function firstWorksheetPath(ZipArchive $zip): ?string
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                return $name;
            }
        }

        return null;
    }

    protected static function rowHasValue(string $rowXml): bool
    {
        if (! preg_match_all('/<v[^>]*>(.*?)<\/v>/is', $rowXml, $values)) {
            return false;
        }

        foreach ($values[1] as $value) {
            if (trim(html_entity_decode($value, ENT_QUOTES | ENT_XML1)) !== '') {
                return true;
            }
        }

        return false;
    }
}
