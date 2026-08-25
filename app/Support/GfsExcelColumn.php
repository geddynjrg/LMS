<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared GFS column styling and validation for upload templates.
 *
 * Text format (@) is required because GFS codes are 20–22 digits; Excel numeric
 * cells only preserve 15 significant digits. Import inserts dashes via GfsCode
 * when the sheet has digits only.
 */
class GfsExcelColumn
{
    public const FORMAT_CODE = '@';

    public const SAMPLE = '4-006-026-4110202-201-001';

    public static function apply(Worksheet $sheet, string $column, int $firstRow, int $lastRow): void
    {
        $range = "{$column}{$firstRow}:{$column}{$lastRow}";
        $anchor = "{$column}{$firstRow}";

        $sheet->getStyle($range)
            ->getNumberFormat()
            ->setFormatCode(self::FORMAT_CODE);

        $sheet->getColumnDimension($column)->setAutoSize(false);
        $sheet->getColumnDimension($column)->setWidth(32);

        $headerCell = $column . '1';
        if ($sheet->getCell($headerCell)->getValue() !== null) {
            $comment = $sheet->getComment($headerCell);
            $comment->setAuthor('LMS');
            $comment->getText()->createTextRun(self::inputMessage());
            $comment->setWidth('300pt');
            $comment->setHeight('90pt');
        }

        $validation = $sheet->getCell($anchor)->getDataValidation();
        $validation->setType(DataValidation::TYPE_CUSTOM)
            ->setAllowBlank(false)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setPromptTitle('GFS Code')
            ->setPrompt(self::inputMessage())
            ->setErrorTitle('Invalid GFS Code')
            ->setError(self::errorMessage())
            ->setFormula1(self::validationFormula($anchor))
            ->setSqref($range);
    }

    public static function inputMessage(): string
    {
        return 'Format: 4-County-Constituency-XXXXXXX-XXX-XXX (e.g. ' . self::SAMPLE . '). '
            . 'Enter with or without dashes (20-22 digits). Trailing dashes are ignored. '
            . 'Dashes are added on upload if omitted.';
    }

    public static function errorMessage(): string
    {
        return 'GFS Code must contain only digits and dashes, with 20-22 digits total '
            . '(e.g. ' . self::SAMPLE . '). Trailing dashes are ignored. Dashes are optional.';
    }

    public static function validationFormula(string $cell): string
    {
        $digits = 'LEN(SUBSTITUTE(SUBSTITUTE(' . $cell . ',"-","")," ",""))';
        $validChars = self::onlyValidCharsFormula($cell);

        return '=OR(' . $cell . '="",AND(' . $validChars . ',' . $digits . '>=20,' . $digits . '<=22))';
    }

    /**
     * True when nothing remains after removing 0-9 and "-".
     */
    private static function onlyValidCharsFormula(string $cell): string
    {
        return 'LEN(' . $cell . ')-SUMPRODUCT(LEN(' . $cell . ')-LEN(SUBSTITUTE(' . $cell
            . ',{"0","1","2","3","4","5","6","7","8","9","-"},"")))=0';
    }
}
