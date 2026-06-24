<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
use App\Models\PhaseApplicationDocument;
use App\Support\PhaseDocumentType;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BankReconciliationService
{
    public const MAX_STATEMENT_AGE_MONTHS = 2;

    public function hasMainAccountReconciliation(LoanPhase $phase): bool
    {
        return $this->documentOfType($phase, PhaseDocumentType::BANK_RECONCILIATION_MAIN_ACCOUNT) !== null
            || $this->documentOfType($phase, PhaseDocumentType::BANK_RECONCILIATION) !== null;
    }

    public function hasLoanRepaymentReconciliation(LoanPhase $phase): bool
    {
        return $this->documentOfType($phase, PhaseDocumentType::BANK_RECONCILIATION_LOAN_REPAYMENT) !== null;
    }

    public function hasAllRequiredReconciliations(LoanPhase $phase): bool
    {
        return $this->hasMainAccountReconciliation($phase)
            && $this->hasLoanRepaymentReconciliation($phase);
    }

    public function documentOfType(LoanPhase $phase, string $type): ?PhaseApplicationDocument
    {
        $phase->loadMissing('phaseApplicationDocuments');

        return $phase->phaseApplicationDocuments->firstWhere('document_type', $type);
    }

    /**
     * @return list<PhaseApplicationDocument>
     */
    public function allReconciliationDocuments(LoanPhase $phase): array
    {
        $phase->loadMissing('phaseApplicationDocuments');

        return $phase->phaseApplicationDocuments
            ->filter(fn (PhaseApplicationDocument $doc) => in_array(
                $doc->document_type,
                PhaseDocumentType::bankReconciliationTypes(),
                true,
            ))
            ->values()
            ->all();
    }

    public function validateStatementPeriod(string $period, string $field = 'statement_period'): void
    {
        $period = trim($period);

        if ($period === '' || !preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw ValidationException::withMessages([
                $field => 'Select the statement month for this bank reconciliation (YYYY-MM).',
            ]);
        }

        $statementMonth = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $oldestAllowed = now()->subMonths(self::MAX_STATEMENT_AGE_MONTHS)->startOfMonth();
        $latestAllowed = now()->endOfMonth();

        if ($statementMonth->lt($oldestAllowed) || $statementMonth->gt($latestAllowed)) {
            throw ValidationException::withMessages([
                $field => 'Bank reconciliation must be current: use this month or either of the previous two months.',
            ]);
        }
    }

    public function formatStatementPeriodLabel(?string $period): ?string
    {
        if (!$period || !preg_match('/^\d{4}-\d{2}$/', $period)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m', $period)->format('F Y');
    }
}
