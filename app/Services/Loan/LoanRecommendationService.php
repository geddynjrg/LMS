<?php

namespace App\Services\Loan;

use App\Models\TempApplication;

class LoanRecommendationService
{
    public const PROFIT_SHARE = 2 / 3;
    public const MIN_CAPACITY_THRESHOLD = 30000;
    public const MIN_LOAN_AMOUNT = 50000;
    public const WEZESHA_CAP = 100000;
    public const DEFAULT_MONTHS = 7;

    public function __construct(
        protected TempApplicationService $applicationService,
    ) {
    }

    /**
     * @param  array{min:float|int,max:float|int,balance:float|int,can_borrow:bool}  $eligibility
     * @return array{
     *   decision:string,
     *   recommended_amount:float,
     *   raw_capacity:float,
     *   monthly_profit:float,
     *   table_banking_monthly:float,
     *   base_amount:float,
     *   months:int,
     *   loan_type:string,
     *   formula:string,
     *   explanation:string,
     *   should_decline:bool
     * }
     */
    public function recommend(TempApplication $application, array $eligibility = []): array
    {
        $monthlyProfit = $this->monthlyProfit($application);
        $tableBankingMonthly = $this->tableBankingMonthly($application);
        $months = $this->repaymentMonths($application);
        $loanType = $this->resolveLoanType($application);
        $isEndeleza = $this->isEndeleza($application, $loanType);

        if ($monthlyProfit <= 0 && $tableBankingMonthly <= 0) {
            return $this->result(
                decision: 'decline',
                recommendedAmount: 0,
                rawCapacity: 0,
                monthlyProfit: $monthlyProfit,
                tableBankingMonthly: $tableBankingMonthly,
                baseAmount: 0,
                months: $months,
                loanType: $loanType,
                explanation: $monthlyProfit < 0
                    ? 'Group is operating at a loss. Recommend declining this application.'
                    : 'Insufficient profit and table-banking data to support a loan recommendation. Recommend declining.',
            );
        }

        $baseAmount = $this->baseAmount($monthlyProfit, $tableBankingMonthly);
        $rawCapacity = round(self::PROFIT_SHARE * $baseAmount * $months, 2);

        if ($rawCapacity < self::MIN_CAPACITY_THRESHOLD) {
            return $this->result(
                decision: 'decline',
                recommendedAmount: 0,
                rawCapacity: $rawCapacity,
                monthlyProfit: $monthlyProfit,
                tableBankingMonthly: $tableBankingMonthly,
                baseAmount: $baseAmount,
                months: $months,
                loanType: $loanType,
                explanation: 'Repayment capacity (⅔ × base amount × months) is below KES 30,000. Recommend declining.',
            );
        }

        $recommended = $rawCapacity < self::MIN_LOAN_AMOUNT
            ? self::MIN_LOAN_AMOUNT
            : $rawCapacity;

        $cap = $isEndeleza
            ? (float) ($eligibility['max'] ?? self::WEZESHA_CAP)
            : self::WEZESHA_CAP;

        $recommended = min($recommended, $cap);

        if (! empty($eligibility) && isset($eligibility['max'])) {
            $recommended = min($recommended, (float) $eligibility['max']);
        }

        if ($recommended < self::MIN_LOAN_AMOUNT) {
            return $this->result(
                decision: 'decline',
                recommendedAmount: 0,
                rawCapacity: $rawCapacity,
                monthlyProfit: $monthlyProfit,
                tableBankingMonthly: $tableBankingMonthly,
                baseAmount: $baseAmount,
                months: $months,
                loanType: $loanType,
                explanation: 'Recommended amount falls below the minimum lendable amount after applying loan-type caps. Recommend declining.',
            );
        }

        $explanation = $rawCapacity < self::MIN_LOAN_AMOUNT
            ? 'Capacity is between KES 30,000 and KES 50,000, so the minimum recommended loan of KES 50,000 applies.'
            : 'Capacity supports a recommended loan based on ⅔ of monthly profit or table-banking amount over '.$months.' months.';

        if ($recommended < $rawCapacity) {
            $explanation .= ' Capped at KES '.number_format($cap).' for '.$loanType.'.';
        }

        return $this->result(
            decision: 'approve',
            recommendedAmount: round($recommended, 2),
            rawCapacity: $rawCapacity,
            monthlyProfit: $monthlyProfit,
            tableBankingMonthly: $tableBankingMonthly,
            baseAmount: $baseAmount,
            months: $months,
            loanType: $loanType,
            explanation: $explanation,
        );
    }

    public function monthlyProfit(TempApplication $application): float
    {
        $income = (float) ($application->average_income ?? 0);
        $expenses = collect($this->applicationService->getProcessedExpenses($application))
            ->sum(fn (array $row) => (float) ($row['amount'] ?? 0));

        return round($income - $expenses, 2);
    }

    public function tableBankingMonthly(TempApplication $application): float
    {
        $raw = $application->raw_data ?? [];

        return round((float) ($raw['monthly_share_contribution'] ?? 0), 2);
    }

    public function repaymentMonths(TempApplication $application): int
    {
        $raw = $application->raw_data ?? [];
        $months = (int) ($raw['table_banking_duration'] ?? 0);

        if ($months <= 0) {
            $months = (int) ($application->proposed_repayment_period ?? 0);
        }

        return max($months, 1);
    }

    protected function baseAmount(float $monthlyProfit, float $tableBankingMonthly): float
    {
        if ($monthlyProfit > 0 && $tableBankingMonthly > 0) {
            return max($monthlyProfit, $tableBankingMonthly);
        }

        if ($monthlyProfit > 0) {
            return $monthlyProfit;
        }

        return $tableBankingMonthly;
    }

    protected function resolveLoanType(TempApplication $application): string
    {
        if ($this->isEndeleza($application, (string) ($application->loan_type ?? ''))) {
            return 'Endeleza';
        }

        return 'Wezesha';
    }

    protected function isEndeleza(TempApplication $application, string $loanType = ''): bool
    {
        $loanType = strtolower($loanType !== '' ? $loanType : (string) ($application->loan_type ?? ''));

        return $application->link_status === 'linked'
            || $loanType === 'endeleza';
    }

    protected function result(
        string $decision,
        float $recommendedAmount,
        float $rawCapacity,
        float $monthlyProfit,
        float $tableBankingMonthly,
        float $baseAmount,
        int $months,
        string $loanType,
        string $explanation,
    ): array {
        $formula = $baseAmount > 0
            ? '(⅔ × '.number_format($baseAmount, 0).' × '.$months.') = KES '.number_format($rawCapacity, 0)
            : 'N/A';

        return [
            'decision' => $decision,
            'recommended_amount' => $recommendedAmount,
            'raw_capacity' => $rawCapacity,
            'monthly_profit' => $monthlyProfit,
            'table_banking_monthly' => $tableBankingMonthly,
            'base_amount' => $baseAmount,
            'months' => $months,
            'loan_type' => $loanType,
            'formula' => $formula,
            'explanation' => $explanation,
            'should_decline' => $decision === 'decline' || $recommendedAmount <= 0,
        ];
    }
}
