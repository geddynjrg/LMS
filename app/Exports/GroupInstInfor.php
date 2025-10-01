<?php

namespace App\Exports;

use App\Models\Constituency;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GroupInstInfor implements FromView, WithStyles, WithTitle, WithEvents

{
    protected $group;
    protected $date;
    protected $constituency;
    public function __construct(Group $group, Constituency $constituency)
    {
        $this->group = $group;
        $this->constituency = $constituency;
    }

    private function fy($date)
    {
        $date = Carbon::parse($date);
        $year = $date->year;

        // Financial year starts on 1st July and ends on 30th June
        if ($date->month > 6) {
            $financialYear = $year . '/' . ($year + 1);
        } else {
            $financialYear = ($year - 1) . '/' . $year;
        }

        return $financialYear;
    }

    public function view(): View
    {
        /*$this->group = Group::where('id', 192)->with(['groupLoans' => function($query) {
            $query->whereIn('loan_status', ['financed', 'cancelled']);
        }, 'grouploans.groupMembers.member'])->first();*/

        $this->date = $this->fy(Carbon::now()->isoFormat('Do MMMM YYYY'));

        return view('exports.group_loan_info', [
            'group' => $this->group,
            'date' => $this->date,
            'constituency' => $this->constituency,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            /*1 => [
                'font' => ['bold' => true, 'name' => 'Times New Roman', 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['wrapText' => true, 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '00BCD4'] // Cyan background for header
                ]
            ],*/
            'A1:A6' => [
                'alignment' => ['wrapText' => true, 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
            'A:E' => [
                'alignment' => ['wrapText' => true],
                'font' => ['name' => 'Calibri', 'size' => 11],
            ],
        ];
    }


    public function title(): string
    {
        return strtoupper($this->group->name);
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set column widths
                /*$columnWidths = [
                    'A' => 7,
                    'B' => 14,
                    'C' => 22,
                    'D' => 28,
                    'E' => 12,
                ];

                foreach ($columnWidths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }*/
                // Set auto-fit for column widths
                foreach (range('A', $sheet->getHighestColumn()) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Set auto-fit for row heights
                foreach (range(1, $sheet->getHighestRow()) as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(-1); // -1 indicates automatic height
                }

                // Set specific row heights
                $sheet->getRowDimension(11)->setRowHeight(35);
                if($this->group->groupLoans->count()>2)
                {
                    $sheet->getRowDimension(13)->setRowHeight(45);
                }



                // Apply borders to the entire sheet
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Set the height for the last two rows
                $sheet->getRowDimension($highestRow)->setRowHeight(35);
                if ($highestRow > 1) {
                    $sheet->getRowDimension($highestRow - 1)->setRowHeight(35);
                }

                $sheet->getStyle("A1:$highestColumn$highestRow")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
            }
        ];
    }
}
