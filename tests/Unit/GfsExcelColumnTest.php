<?php

namespace Tests\Unit;

use App\Support\GfsExcelColumn;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

class GfsExcelColumnTest extends TestCase
{
    public function test_validation_formula_is_under_excel_dv_limit(): void
    {
        $formula = GfsExcelColumn::validationFormula('G3');

        $this->assertLessThan(255, strlen($formula), $formula);
        $this->assertMatchesRegularExpression('/LEN\(.+\)=0/', $formula);
        $this->assertStringContainsString('>=20', $formula);
        $this->assertStringContainsString('<=22', $formula);
    }

    public function test_members_column_formula_is_under_excel_dv_limit(): void
    {
        $formula = GfsExcelColumn::validationFormula('E2');

        $this->assertLessThan(255, strlen($formula), $formula);
        $this->assertMatchesRegularExpression('/LEN\(.+\)=0/', $formula);
    }

    /**
     * @dataProvider formulaCases
     */
    public function test_excel_formula_accepts_valid_gfs_codes(string $value, bool $expected): void
    {
        $this->assertSame($expected, $this->evaluateFormula('G3', $value));
        $this->assertSame($expected, $this->evaluateFormula('E2', $value));
    }

    public static function formulaCases(): array
    {
        return [
            'user code' => ['4-039-218-4110202-202-228', true],
            'sample dashed' => ['4-006-026-4110202-201-001', true],
            'twenty-one digit suffix' => ['4-006-026-4110202-201-0001', true],
            'twenty-two digit suffix' => ['4-006-026-4110202-201-00001', true],
            'digits only' => ['40060264110202201001', true],
            'twenty-two digits only' => ['4006026411020220100001', true],
            'empty allowed' => ['', true],
            'too few digits' => ['4-039-218-4110202-202-22', false],
            'letter in code' => ['4-039-218-4110202-202-228A', false],
            'letters only' => ['abc', false],
        ];
    }

    private function evaluateFormula(string $cell, string $value): bool
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        Calculation::getInstance($spreadsheet)->flushInstance();
        $sheet->setCellValue($cell, $value);
        $sheet->setCellValue('Z1', GfsExcelColumn::validationFormula($cell));

        return (bool) $sheet->getCell('Z1')->getCalculatedValue();
    }
}
