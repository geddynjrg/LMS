<?php

namespace App\Support;

use App\Models\GroupLoan;
use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use Illuminate\Support\Str;

/**
 * Normalizes phase applications and historical group loans for codelist exports.
 */
class CodeListRecordMapper
{
    public static function isHistoricalRecord(mixed $record): bool
    {
        return $record instanceof GroupLoan;
    }

    public static function resolvePhase(mixed $record): ?LoanPhase
    {
        if ($record instanceof GroupLoan) {
            return $record->loanPhase;
        }

        return $record->reviewPhase ?? null;
    }

    public static function groupCode(mixed $record): string
    {
        if ($record instanceof GroupLoan) {
            return (string) ($record->group?->identifier ?? $record->group?->code ?? '');
        }

        return (string) ($record->group_code ?? '');
    }

    public static function groupName(mixed $record): string
    {
        if ($record instanceof GroupLoan) {
            return Str::upper($record->group?->name ?? 'N/A');
        }

        return Str::upper($record->tempApplication?->group_name ?? 'N/A');
    }

    public static function gfsCode(mixed $record): string
    {
        if ($record instanceof GroupLoan) {
            return (string) ($record->gfs_code ?? 'N/A');
        }

        return (string) ($record->gfs_code ?? 'N/A');
    }

    public static function amountApplied(mixed $record): float
    {
        if ($record instanceof GroupLoan) {
            $applied = (float) ($record->amount_applied ?? 0);

            return $applied > 0 ? $applied : (float) ($record->amount_approved ?? 0);
        }

        return (float) ($record->amount_applied ?? $record->tempApplication?->loan_amount ?? 0);
    }

    public static function amountApproved(mixed $record): float
    {
        return (float) ($record->amount_approved ?? 0);
    }

    public static function currentActivity(mixed $record): string
    {
        if ($record instanceof GroupLoan) {
            return Str::upper((string) ($record->sector ?? 'N/A'));
        }

        $businessType = $record->tempApplication?->business_type;
        $currentActivity = is_array($businessType) ? ($businessType[0] ?? 'N/A') : ($businessType ?? 'N/A');

        return Str::upper((string) $currentActivity);
    }

    public static function previousLoanAmount(mixed $record): float
    {
        if ($record instanceof GroupLoan) {
            return 0.0;
        }

        if ($record instanceof PhaseApplication && $record->linkedGroup && $record->linkedGroup->currentLoan) {
            return (float) ($record->linkedGroup->currentLoan->amount_issued ?? 0);
        }

        return 0.0;
    }

    public static function loanLevel(mixed $record): string
    {
        if ($record instanceof GroupLoan) {
            return 'L' . ($record->loan_level ?? 1);
        }

        if ($record instanceof PhaseApplication && $record->linkedGroup && $record->linkedGroup->currentLoan) {
            return 'L' . ($record->loan_level ?? 1);
        }

        return 'L1';
    }

    public static function currentStatus(mixed $record): string
    {
        if ($record instanceof GroupLoan) {
            return Str::upper((string) ($record->category ?? 'N/A'));
        }

        return Str::upper((string) ($record->tempApplication?->nature_of_business ?? 'N/A'));
    }

    public static function boardRemarks(mixed $record): string
    {
        if ($record instanceof GroupLoan) {
            return $record->loan_status === 'financed' ? 'APPROVED' : Str::upper((string) $record->loan_status);
        }

        return in_array($record->status, ['approved', 'pending_submission_to_hq', 'pending_hq_approval', 'in_training'], true)
            ? 'APPROVED'
            : Str::upper(str_replace('_', ' ', (string) $record->status));
    }

    public static function previewRow(mixed $record): array
    {
        return [
            'group_name' => self::groupName($record),
            'gfs_code' => self::gfsCode($record),
            'loan_level' => self::loanLevel($record),
            'amount_applied' => self::amountApplied($record),
            'amount_approved' => self::amountApproved($record),
            'status' => $record instanceof GroupLoan ? $record->loan_status : $record->status,
        ];
    }

    public static function pdfRow(mixed $record, int $rowNumber): array
    {
        return [
            'sno' => $rowNumber,
            'code' => self::groupCode($record),
            'group_name' => self::groupName($record),
            'gfs_code' => self::gfsCode($record),
            'applied' => self::amountApplied($record),
            'activity' => self::currentActivity($record),
            'previous_loan' => self::previousLoanAmount($record),
            'loan_level' => self::loanLevel($record),
            'recommended' => self::amountApproved($record),
            'status' => self::currentStatus($record),
        ];
    }
}
