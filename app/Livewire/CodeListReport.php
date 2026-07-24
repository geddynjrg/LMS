<?php

namespace App\Livewire;

use App\Exports\LoanSubmExport;
use App\Models\Constituency;
use App\Models\County;
use App\Services\Reports\LoanSubmissionFormCalculator;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

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

    protected $cacheTtl = 3600;

    protected LoanSubmissionFormCalculator $calculator;

    public function mount(County $county, Constituency $constituency): void
    {
        $this->calculator = app(LoanSubmissionFormCalculator::class);
        $this->county = $county;
        $this->constituency = $constituency;
        $this->loadData();
    }

    private function cacheKey(): string
    {
        return "code_list_report_{$this->constituency->constituency_code}";
    }

    private function loadData(bool $forceRefresh = false): void
    {
        if ($forceRefresh) {
            Cache::forget($this->cacheKey());
        }

        $data = Cache::remember($this->cacheKey(), $this->cacheTtl, function () {
            return $this->constituency->load([
                'phases.grouploans' => function ($query) {
                    $query->whereIn('loan_status', ['financed', 'cancelled'])
                        ->with('group');
                },
            ]);
        });

        $this->constituency = $data;
        $this->phases = $this->constituency->phases;
        $this->applyCalculations();
    }

    private function applyCalculations(): void
    {
        $this->loanPhases = $this->calculator->preparePhases($this->phases);

        $allGroupLoans = $this->loanPhases->pluck('grouploans')->flatten();

        $this->totalAmountApproved = $allGroupLoans->sum('amount_approved');
        $this->totalAmountIssued = $allGroupLoans->sum('amount_issued');
        $this->totalVariance = $allGroupLoans->sum('variance');
        $this->totalAmountDue = $allGroupLoans->sum('amount_due');
        $this->totalAmountRepaid = $allGroupLoans->sum('amount_repaid');
        $this->totalArrears = $allGroupLoans->sum('arrears');
        $this->totalOutstandingBalance = $allGroupLoans->sum('outstanding_balance');
    }

    public function render()
    {
        return view('livewire.reports.code-list-report');
    }

    public function export()
    {
        $this->loadData(true);

        $fileName = ucwords(strtolower($this->constituency->constituency_name))
            . '_Loan_Submission_Report_'
            . now()->format('Y-m-d_His')
            . '.xlsx';

        return Excel::download(
            new LoanSubmExport($this->constituency, $this->loanPhases, $this->calculator),
            $fileName
        );
    }

    public function clearCache(): void
    {
        $this->loadData(true);
        session()->flash('message', 'Cache cleared successfully.');
    }
}
