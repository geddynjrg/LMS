<?php

namespace App\Exports;

use App\Models\Constituency;
use App\Models\TempApplication;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Traits\HandlesFinancialYears;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ApplicationInstitutionalDataExport implements FromView, WithStyles, WithTitle, WithEvents, WithColumnWidths
{
    use HandlesFinancialYears;

    protected $application;
    protected $date;
    protected $constituency;
    public function __construct(TempApplication $application, Constituency $constituency)
    {

        $this->application = $application;
        $this->constituency = $constituency;

    }

    public function view(): View
    {
        return view('exports.application_instititional_info', [
            'application' => $this->application,
            'constituency' => $this->constituency,
            'currentFY' => $this->getFinancialYearData(),
        ]);
    }


    public function styles($sheet)
    {
        return [
            'A1:A6' => [
                'alignment' => ['wrapText' => true, 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
            'A:G' => [
                'alignment' => ['wrapText' => true],
                'font' => ['name' => 'Calibri', 'size' => 11],
            ],
            11 => ['alignment' => ['wrapText' => true]],
        ];
    }

    public function title(): string
    {
        return strtoupper($this->application->group_name);
    }

    public function columnWidths(): array {
        return [
            'A' => 7, 'B' => 20, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 15,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->addLogo($event);

                // // Set auto-fit for column widths
                // foreach (range('A', $sheet->getHighestColumn()) as $column) {
                //     $sheet->getColumnDimension($column)->setAutoSize(true);
                // }

                // // Set auto-fit for row heights
                // foreach (range(1, $sheet->getHighestRow()) as $row) {
                //     $sheet->getRowDimension($row)->setRowHeight(-1); // -1 indicates automatic height
                // }

                // // Set specific row heights
                // $sheet->getRowDimension(11)->setRowHeight(35);
                // if($this->group->groupLoans->count()>2)
                // {
                //     $sheet->getRowDimension(13)->setRowHeight(45);
                // }



                // Apply borders to the entire sheet
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getStyle("E18:E{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('0');

                // // Set the height for the last two rows
                // $sheet->getRowDimension($highestRow)->setRowHeight(35);
                // if ($highestRow > 1) {
                //     $sheet->getRowDimension($highestRow - 1)->setRowHeight(35);
                // }

                $sheet->getStyle("A17:$highestColumn$highestRow")->applyFromArray([
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


    private function addLogo(AfterSheet $event)
    {
        try {
            $logoPath = public_path('images/docs/doc_logo.png');

            $usedLogoPath = null;

            if (file_exists($logoPath)) {
                $usedLogoPath = $logoPath;
            }

            if ($usedLogoPath) {
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo');
                $drawing->setPath($usedLogoPath);
                $drawing->setHeight(65); // Adjust height as needed
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(5);
                $drawing->setWorksheet($event->sheet->getDelegate());
            }

        } catch (\Exception $e) {
            // Logo not found or error loading - continue without logo
            logger()->warning('Logo not found for Excel export: ' . $e->getMessage());
        }
    }
}
