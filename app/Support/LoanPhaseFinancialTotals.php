<?php

namespace App\Support;

use App\Models\LoanPhase;
use Illuminate\Support\Facades\DB;

class LoanPhaseFinancialTotals
{
    private const PHASE_ID = 'loan_phases.id';

    /**
     * Raw SQL select expressions for phase list queries (completed tab, etc.).
     *
     * @return array<string, \Illuminate\Contracts\Database\Query\Expression>
     */
    public static function selectExpressions(): array
    {
        $expressions = [
            'total_apps' => self::caseWhen(self::usesPhaseApplications(), self::paCountSql(), self::glActiveCountSql()),
            'pending_apps' => self::caseWhen(self::usesPhaseApplications(), self::paStatusCountSql('pending'), '0'),
            'approved_apps' => self::caseWhen(self::usesPhaseApplications(), self::paStatusCountSql('approved'), self::glStatusCountSql('financed')),
            'rejected_apps' => self::caseWhen(self::usesPhaseApplications(), self::paStatusCountSql('rejected'), self::glStatusCountSql('cancelled')),
            'disbursed_apps' => self::caseWhen(self::usesPhaseApplications(), self::paStatusCountSql('disbursed'), self::glStatusCountSql('financed')),
            'total_requested' => self::caseWhen(
                self::usesPhaseApplications(),
                self::paSumSql('amount_applied'),
                self::glSumSql('amount_approved', "loan_status = 'financed'")
            ),
            'total_approved' => self::caseWhen(
                self::usesPhaseApplications(),
                self::paSumSql('amount_approved', 'status != "pending"'),
                self::glSumSql('amount_approved', "loan_status = 'financed'")
            ),
            'total_issued' => self::caseWhen(
                self::usesPhaseApplications(),
                self::paSumSql('amount_approved', 'status = "disbursed"'),
                self::glSumSql('amount_issued', "loan_status = 'financed'")
            ),
        ];

        $selects = [];
        foreach ($expressions as $alias => $sql) {
            $selects[] = DB::raw("{$sql} as {$alias}");
        }

        return $selects;
    }

    /**
     * Recompute and persist financial totals on the loan_phases row.
     */
    public static function refresh(LoanPhase|int $phase): void
    {
        $phase = $phase instanceof LoanPhase ? $phase : LoanPhase::findOrFail($phase);

        if ($phase->phaseApplications()->exists()) {
            $stats = $phase->phaseApplications()
                ->selectRaw('
                    COALESCE(SUM(amount_applied), 0) as total_amount_applied,
                    COALESCE(SUM(CASE WHEN status != "pending" THEN amount_approved ELSE 0 END), 0) as total_amount_approved,
                    COALESCE(SUM(CASE WHEN status = "disbursed" THEN amount_approved ELSE 0 END), 0) as amount_disbursed
                ')
                ->first();
        } else {
            $stats = $phase->grouploans()
                ->where('loan_status', 'financed')
                ->selectRaw('
                    COALESCE(SUM(amount_approved), 0) as total_amount_applied,
                    COALESCE(SUM(amount_approved), 0) as total_amount_approved,
                    COALESCE(SUM(amount_issued), 0) as amount_disbursed
                ')
                ->first();
        }

        $phase->update([
            'total_amount_applied' => $stats->total_amount_applied ?? 0,
            'total_amount_approved' => $stats->total_amount_approved ?? 0,
            'amount_disbursed' => $stats->amount_disbursed ?? 0,
        ]);
    }

    /**
     * Refresh all phases, optionally scoped to a constituency code.
     */
    public static function refreshAll(?string $constituencyCode = null): int
    {
        $query = LoanPhase::query()->orderBy('id');

        if ($constituencyCode !== null) {
            $query->where('constituency_code', $constituencyCode);
        }

        $count = 0;
        $query->each(function (LoanPhase $phase) use (&$count) {
            self::refresh($phase);
            $count++;
        });

        return $count;
    }

    private static function usesPhaseApplications(): string
    {
        return self::paCountSql() . ' > 0';
    }

    private static function caseWhen(string $condition, string $then, string $else): string
    {
        return "(CASE WHEN {$condition} THEN {$then} ELSE {$else} END)";
    }

    private static function paCountSql(): string
    {
        return '(SELECT COUNT(*) FROM phase_applications WHERE loan_phase_id = ' . self::PHASE_ID . ')';
    }

    private static function paStatusCountSql(string $status): string
    {
        return '(SELECT COUNT(*) FROM phase_applications WHERE loan_phase_id = ' . self::PHASE_ID . " AND status = \"{$status}\")";
    }

    private static function glActiveCountSql(): string
    {
        return "(SELECT COUNT(*) FROM group_loans WHERE loan_phase_id = " . self::PHASE_ID . " AND loan_status != 'cancelled')";
    }

    private static function glStatusCountSql(string $status): string
    {
        return "(SELECT COUNT(*) FROM group_loans WHERE loan_phase_id = " . self::PHASE_ID . " AND loan_status = '{$status}')";
    }

    private static function paSumSql(string $column, ?string $where = null): string
    {
        $clause = $where ? " AND {$where}" : '';

        return '(SELECT COALESCE(SUM(' . $column . '), 0) FROM phase_applications WHERE loan_phase_id = ' . self::PHASE_ID . $clause . ')';
    }

    private static function glSumSql(string $column, string $where): string
    {
        return '(SELECT COALESCE(SUM(' . $column . '), 0) FROM group_loans WHERE loan_phase_id = ' . self::PHASE_ID . ' AND ' . $where . ')';
    }
}
