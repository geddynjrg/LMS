<?php

namespace App\Exports;

use App\Models\TempApplication;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ApplicationsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{

    protected $constituencyCode;
    protected $filters;

    public function __construct(string $constituencyCode, array $filters)
    {
        $this->constituencyCode = $constituencyCode;
        $this->filters = $filters;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return TempApplication::query()
            ->where('constituency_code', $this->constituencyCode)
            ->when($this->filters['search'] ?? null, fn($q, $s) =>
                $q->whereAny(['application_id', 'group_name', 'owner_identifier'], 'like', "%{$s}%")
            )
            ->when($this->filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($this->filters['phase'] ?? null, function ($q, $phase) {
                if ($phase === 'not_in_phase') {
                    $q->whereDoesntHave('phaseApplication');
                }
            })
            ->when($this->filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->get();
    }

    public function headings(): array
    {
        return [
            'Application ID',
            'Group Name',
            'Amount (KES)',
            'Status',
            'Submitted At',
            'Phase(s)',
        ];
    }

    public function map($application): array
    {
        $phases = $application->phaseApplication->pluck('reviewPhase.phase')->implode(', ');

        return [
            $application->application_id,
            $application->group_name,
            number_format($application->loan_amount),
            ucfirst($application->status),
            $application->created_at->format('d M Y H:i'),
            $phases ?: 'Not assigned',
        ];
    }
}
