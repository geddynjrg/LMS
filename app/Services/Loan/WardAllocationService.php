<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use Illuminate\Support\Collection;

class WardAllocationService
{
    /**
     * Summarise recommended/approved amounts by ward for a disbursement phase.
     *
     * @return array{
     *   wards: list<array{ward_id:int|null,ward_name:string,application_count:int,applied_total:float,approved_total:float,share_percent:float}>,
     *   phase_total_approved: float,
     *   ward_count: int,
     *   target_per_ward: float|null,
     *   imbalance_notes: list<string>
     * }
     */
    public function summarisePhase(LoanPhase $phase): array
    {
        $applications = PhaseApplication::query()
            ->where('loan_phase_id', $phase->id)
            ->whereNotIn('status', ['rejected', 'deferred'])
            ->with(['tempApplication:id,ward_id,ward_name,loan_amount'])
            ->get();

        $rows = $applications
            ->groupBy(fn (PhaseApplication $app) => $app->tempApplication?->ward_id ?? 0)
            ->map(function (Collection $group, $wardId) {
                $first = $group->first();
                $approvedTotal = $group->sum(fn (PhaseApplication $app) => (float) ($app->amount_approved ?? 0));
                $appliedTotal = $group->sum(fn (PhaseApplication $app) => (float) ($app->amount_applied ?? $app->tempApplication?->loan_amount ?? 0));

                return [
                    'ward_id' => $wardId ?: null,
                    'ward_name' => $first->tempApplication?->ward_name ?? 'Unassigned ward',
                    'application_count' => $group->count(),
                    'applied_total' => round($appliedTotal, 2),
                    'approved_total' => round($approvedTotal, 2),
                ];
            })
            ->values()
            ->sortByDesc('approved_total')
            ->values()
            ->all();

        $phaseTotalApproved = round(collect($rows)->sum('approved_total'), 2);
        $wardCount = max(count($rows), 1);
        $targetPerWard = $phaseTotalApproved > 0 ? round($phaseTotalApproved / $wardCount, 2) : null;

        foreach ($rows as &$row) {
            $row['share_percent'] = $phaseTotalApproved > 0
                ? round(($row['approved_total'] / $phaseTotalApproved) * 100, 1)
                : 0.0;
        }
        unset($row);

        return [
            'wards' => $rows,
            'phase_total_approved' => $phaseTotalApproved,
            'ward_count' => count($rows),
            'target_per_ward' => $targetPerWard,
            'imbalance_notes' => $this->imbalanceNotes($rows, $targetPerWard),
        ];
    }

    /**
     * @param  list<array{ward_name:string,approved_total:float,share_percent:float}>  $rows
     * @return list<string>
     */
    protected function imbalanceNotes(array $rows, ?float $targetPerWard): array
    {
        if ($targetPerWard === null || $targetPerWard <= 0 || $rows === []) {
            return [];
        }

        $notes = [];

        foreach ($rows as $row) {
            $variance = $row['approved_total'] - $targetPerWard;
            $variancePercent = ($variance / $targetPerWard) * 100;

            if ($variancePercent >= 35) {
                $notes[] = $row['ward_name'].' is '.number_format(abs($variancePercent), 0).'% above the equal-share ward target.';
            } elseif ($variancePercent <= -35) {
                $notes[] = $row['ward_name'].' is '.number_format(abs($variancePercent), 0).'% below the equal-share ward target.';
            }
        }

        return $notes;
    }
}
