<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared GFS column styling and validation for upload templates.
 *
 * Text format (@) is required because GFS codes are 20–21 digits; Excel numeric
 * cells only preserve 15 significant digits, so a custom number mask would corrupt
 * digit-only entry. Import normalizes dashed or continuous digits via GfsCode.
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
        $sheet->getColumnDimension($column)->setWidth(28);

        $headerCell = $column . '1';
        if ($sheet->getCell($headerCell)->getValue() !== null) {
            $comment = $sheet->getComment($headerCell);
            $comment->setAuthor('LMS');
            $comment->getText()->createTextRun(self::inputMessage());
            $comment->setWidth('280pt');
            $comment->setHeight('80pt');
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
            . 'Enter with or without dashes (20-21 digits).';
    }

    public static function errorMessage(): string
    {
        return 'GFS Code must contain only digits and dashes, with 20-21 digits total '
            . '(e.g. ' . self::SAMPLE . ').';
    }

    public static function validationFormula(string $cell): string
    {
        $digits = 'LEN(SUBSTITUTE(SUBSTITUTE(' . $cell . ',"-","")," ",""))';
        $validChars = self::onlyValidCharsFormula($cell);

        return '=OR(' . $cell . '="",AND(' . $validChars . ',' . $digits . '<=21))';
    }

    private static function onlyValidCharsFormula(string $cell): string
    {
        $stripped = $cell;
        foreach (['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '-'] as $char) {
            $stripped = 'SUBSTITUTE(' . $stripped . ',"' . $char . '","")';
        }

        return 'LEN(' . $cell . ')=LEN(' . $stripped . ')';
    }
}
