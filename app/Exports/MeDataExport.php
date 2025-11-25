<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use App\Models\MeReporting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MeDataExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $constituencyCode;
    protected $financialYear;
    protected $quarter;

    public function __construct($constituencyCode, $financialYear, $quarter = 'all')
    {
        $this->constituencyCode = $constituencyCode;
        $this->financialYear = $financialYear;
        $this->quarter = $quarter;
    }

    public function collection()
    {
        $query = MeReporting::where('constituency_code', $this->constituencyCode)
            ->where('financial_year', $this->financialYear);


        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Data Required',
            'Unit of Measure',
            'Q1 Achieved',
            'Q2 Achieved',
            'Q3 Achieved',
            'Q4 Achieved',
            'Annual Achieved',
            'Remarks/Comments',
            'Last Updated'
        ];
    }

    public function map($indicator): array
    {
        return [
            $indicator->data_required,
            $indicator->unit_of_measure,
            $this->formatNumber($indicator->q1_achieved),
            $this->formatNumber($indicator->q2_achieved),
            $this->formatNumber($indicator->q3_achieved),
            $this->formatNumber($indicator->q4_achieved),
            $this->formatNumber($indicator->annual_achieved),
            $indicator->remarks_comments,
            $indicator->updated_at->format('Y-m-d H:i:s')
        ];
    }

    private function formatNumber($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }
        return $value;
    }

    public function styles(Worksheet $sheet)
    {
        // Header styles
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2E86C1']]
        ]);

        // Auto-size columns
        foreach(range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add borders
        $sheet->getStyle('A1:I' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        $title = 'M&E Data ' . $this->financialYear;
        if ($this->quarter !== 'all') {
            $title .= ' ' . $this->quarter;
        }
        return substr($title, 0, 31); // Excel sheet title limit
    }

}
