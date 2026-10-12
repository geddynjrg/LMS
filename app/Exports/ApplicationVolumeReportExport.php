<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ApplicationVolumeReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(
        protected array $headings,
        protected Collection $rows,
        protected string $title = 'Report'
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return substr(preg_replace('/[\\\\\/\?\*\[\]]/', '', $this->title) ?: 'Report', 0, 31);
    }
}
