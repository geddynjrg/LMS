<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PhaseAgingAnalyticsService
{
    public const SOURCE = 'core_lms';

    public function liveAging(?string $constituencyCode = null, int $minDays = 0): Collection
    {
        $query = DB::table('loan_phases')
            ->select([
                'loan_phases.constituency_code',
                DB::raw('MAX(constituencies.constituency_name) as constituency_name'),
                'loan_phases.status as phase_status',
                DB::raw('COUNT(DISTINCT loan_phases.id) as phase_count'),
                DB::raw('COUNT(phase_applications.id) as application_count'),
                DB::raw('AVG(DATEDIFF(NOW(), COALESCE(loan_phases.updated_at, loan_phases.created_at))) as avg_days_in_phase'),
                DB::raw('MAX(DATEDIFF(NOW(), COALESCE(loan_phases.updated_at, loan_phases.created_at))) as max_days_in_phase'),
            ])
            ->leftJoin('phase_applications', 'phase_applications.loan_phase_id', '=', 'loan_phases.id')
            ->leftJoin('constituencies', 'constituencies.constituency_code', '=', 'loan_phases.constituency_code')
            ->whereNotIn('loan_phases.status', ['approved', 'disbursed', 'cancelled', 'rejected'])
            ->groupBy('loan_phases.constituency_code', 'loan_phases.status')
            ->orderByDesc('max_days_in_phase');

        if ($constituencyCode) {
            $query->where('loan_phases.constituency_code', $constituencyCode);
        }

        return collect($query->get())->filter(fn ($row) => (int) $row->max_days_in_phase >= $minDays)->values();
    }

    public function snapshot(): int
    {
        $rows = $this->liveAging();
        $now = now();
        $payload = [];

        foreach ($rows as $row) {
            $payload[] = [
                'source_system' => self::SOURCE,
                'constituency_code' => $row->constituency_code,
                'constituency_name' => $row->constituency_name,
                'phase_status' => $row->phase_status,
                'application_count' => (int) $row->application_count,
                'avg_days_in_phase' => round((float) $row->avg_days_in_phase, 2),
                'max_days_in_phase' => (int) $row->max_days_in_phase,
                'snapshot_at' => $now,
            ];
        }

        if ($payload === []) {
            return 0;
        }

        DB::table('constituency_phase_aging_snapshots')->insert($payload);

        return count($payload);
    }
}
