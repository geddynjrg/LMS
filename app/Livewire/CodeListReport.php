<?php

namespace App\Livewire;

use App\Exports\LoanSubmExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use App\Models\LoanPhase;
use App\Models\Constituency;
use App\Models\County;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CodeListReport extends Component
{
    public $loanPhases;
    public $totalAmountApproved;
    public $totalAmountIssued;
    public $totalVariance;
    public $totalAmountDue;
    public $totalAmountRepaid;
    public $totalArrears;
    public $totalOutstandingBalance;

    public County $county;
    public Constituency $constituency;
    public $phases;

    // Cache configuration
    protected $cacheTtl = 3600; // 1 hour

    public function mount(County $county, Constituency $constituency)
    {
        $this->county = $county;
        $this->constituency = $constituency;
        $this->loadData();
    }

    private function loadData()
    {
        $cacheKey = "code_list_report_{$this->constituency->constituency_code}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () {
            return $this->constituency->load([
                'phases.grouploans' => function ($query) {
                    $query->whereIn('loan_status', ['financed', 'cancelled'])
                          ->with('group');
                }
            ]);
        });

        $this->constituency = $data;
        $this->phases = $this->constituency->phases;
        $this->getLoanPhases();
    }

    private function getLoanPhases()
    {
        $this->loanPhases = $this->phases->map(function ($phase) {
            $phase->grouploans = $phase->grouploans
                ->whereIn('loan_status', ['financed', 'cancelled'])
                ->sortBy(function ($grouploan) {
                    return array_search($grouploan->level, ['New', 'Resubmission', 'Refinancing', 'Reallocation']);
                })
                ->map(function ($grouploan) {
                    // Add calculated fields to each grouploan
                    $analysis = $this->analyzeSingleLoan($grouploan);
                    $grouploan->variance = $grouploan->amount_approved - $grouploan->amount_issued;
                    $grouploan->amount_due = $analysis['amount_due'];
                    $grouploan->arrears = $analysis['arrears'];
                    $grouploan->outstanding_balance = $grouploan->amount_issued - $grouploan->amount_repaid;
                    $grouploan->repayment_completion_date = $this->calculateRepaymentCompletionDate($grouploan, $analysis);
                    $grouploan->analysis = $analysis;

                    return $grouploan;
                });
            return $phase;
        });

        // Calculate totals using collections
        $allGroupLoans = $this->loanPhases->pluck('grouploans')->flatten();

        $this->totalAmountApproved = $allGroupLoans->sum('amount_approved');
        $this->totalAmountIssued = $allGroupLoans->sum('amount_issued');
        $this->totalVariance = $allGroupLoans->sum('variance');
        $this->totalAmountDue = $allGroupLoans->sum('amount_due');
        $this->totalAmountRepaid = $allGroupLoans->sum('amount_repaid');
        $this->totalArrears = $allGroupLoans->sum('arrears');
        $this->totalOutstandingBalance = $allGroupLoans->sum('outstanding_balance');
    }

    private function analyzeSingleLoan($loan)
    {
        // Handle cancelled loans with null disbursement date
        if (!$loan->disbursement_date || $loan->loan_status === 'cancelled') {
            return [
                'is_active' => false,
                'is_defaulted' => false,
                'amount_due' => 0,
                'arrears' => 0,
                'months_defaulted' => 0,
                'months_in_repayment' => 0,
                'monthly_installment' => 0,
            ];
        }

        $disbursementDate = Carbon::parse($loan->disbursement_date);
        $today = Carbon::now();

        // Calculate total months since disbursement
        $totalMonthsSinceDisbursement = $disbursementDate->diffInMonths($today);

        // Grace period: 6 months
        $gracePeriodEnd = $disbursementDate->copy()->addMonths(6);

        // Check if still in grace period
        if ($today->lte($gracePeriodEnd)) {
            return [
                'is_active' => true,
                'is_defaulted' => false,
                'amount_due' => 0,
                'arrears' => 0,
                'months_defaulted' => 0,
                'months_in_repayment' => 0,
                'monthly_installment' => $loan->amount_issued / 24,
            ];
        }

        // Repayment period calculation
        $repaymentStart = $gracePeriodEnd->copy()->addDay();
        $monthsInRepayment = $repaymentStart->diffInMonths($today);

        // Cap at 24 months for amount due calculation
        $monthsInRepaymentForDue = min($monthsInRepayment, 24);

        // Monthly installment
        $monthlyInstallment = $loan->amount_issued / 24;

        // Amount due (what should have been paid by now)
        $amountDue = $monthlyInstallment * $monthsInRepaymentForDue;

        // Arrears (amount due minus amount repaid)
        $arrears = max($amountDue - $loan->amount_repaid, 0);

        // Check if defaulted (arrears > 0)
        $isDefaulted = $arrears > 0;

        // Calculate months defaulted
        $monthsDefaulted = 0;
        if ($isDefaulted && $monthlyInstallment > 0) {
            $monthsDefaulted = floor($arrears / $monthlyInstallment);
        }

        // Loan is active if still within repayment period or has arrears
        $isActive = $totalMonthsSinceDisbursement <= 30 || $arrears > 0;

        return [
            'is_active' => $isActive,
            'is_defaulted' => $isDefaulted,
            'amount_due' => $amountDue,
            'arrears' => $arrears,
            'months_defaulted' => $monthsDefaulted,
            'months_in_repayment' => $monthsInRepaymentForDue,
            'monthly_installment' => $monthlyInstallment,
        ];
    }

    private function calculateRepaymentCompletionDate($loan, $analysis)
    {
        // Handle cancelled loans or loans with no disbursement date
        if (!$loan->disbursement_date || $loan->loan_status === 'cancelled') {
            return null;
        }

        // Calculate expected completion date
        $disbursementDate = Carbon::parse($loan->disbursement_date);
        $gracePeriodEnd = $disbursementDate->copy()->addMonths(6);

        // If still in grace period, completion is 30 months from disbursement
        if (Carbon::now()->lte($gracePeriodEnd)) {
            return $disbursementDate->copy()->addMonths(30)->format('Y-m-d');
        }

        // Calculate remaining months based on current repayment rate
        $remainingAmount = $loan->amount_issued - $loan->amount_repaid;

        if ($analysis['monthly_installment'] > 0) {
            $remainingMonths = ceil($remainingAmount / $analysis['monthly_installment']);
            $completionDate = Carbon::now()->addMonths($remainingMonths);

            // Cap at maximum loan period (30 months from disbursement)
            $maxCompletionDate = $disbursementDate->copy()->addMonths(30);

            return $completionDate->min($maxCompletionDate)->format('Y-m-d');
        }

        return 'Not Available';
    }

    public function render()
    {
        return view('livewire.reports.code-list-report');
    }

    public function export()
    {
        Cache::forget("code_list_report_{$this->constituency->constituency_code}");
        $fileName = ucwords(strtolower($this->constituency->constituency_name)) .'_Loan_Submission_Report_'. date('Y-m-d') . '.xlsx';
        return Excel::download(new LoanSubmExport($this->constituency, $this->phases), $fileName);
    }

    public function clearCache()
    {
        Cache::forget("code_list_report_{$this->constituency->constituency_code}");
        $this->loadData();
        session()->flash('message', 'Cache cleared successfully.');
    }
}
