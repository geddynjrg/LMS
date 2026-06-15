<?php
namespace App\Traits;

trait HandlesFinancialYears
{
    public function getFinancialYearData()
    {
        $month = date('n');
        $year = date('Y');

        $startYear = ($month >= 7) ? $year : $year - 1;
        $endYear = $startYear + 1;
        $quarter = ceil((($month + 5) % 12 + 1) / 3);

        $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
        $suffix = (($quarter % 100) >= 11 && ($quarter % 100) <= 13)
            ? 'th' : $ends[$quarter % 10];

        return (object)[
            'start'   => $startYear,
            'end'     => $endYear,
            'label'   => $startYear . '/' . $endYear,
            'quarter' => $quarter,
            'quarter_suffix'  => $quarter . $suffix,
            'full'    => "FY " . $startYear . "/" . $endYear
        ];
    }
}
