<?php

namespace App\Livewire;

use App\Exports\LoanSubmExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use App\Models\LoanPhase;
use App\Models\Constituency;
use App\Models\County;

class CodeListReport extends Component
{
    public $loanPhases;
    public $totalAmountIssued;

    public $totalAmountApproved;
    public $totalAmountRepaid;

    public County $county;
    public Constituency $constituency;

    public $phases;

    public function mount(County $county, Constituency $constituency)
    {
        $constituency->load([
            'phases',
        ]);
        $this->county = $county;
        $this->constituency = $constituency;
        $this->phases = $this->constituency->phases;
        $this->getLoanPhases();
    }

    private function getLoanPhases()
    {

        $this->loanPhases = $this->constituency->phases
        ->map(function ($phase) {
            $phase->grouploans = $phase->grouploans->sortBy(function ($grouploan) {
                return array_search($grouploan->level, ['New', 'Resubmission', 'Refinancing']);
            });
            return $phase;
        });

         // Calculate total amount across all phases
        $this->totalAmountIssued = $this->loanPhases->flatMap(function ($phase) {
            return $phase->grouploans;
        })->sum('amount_issued');

        $this->totalAmountRepaid = $this->loanPhases->flatMap(function ($phase) {
            return $phase->grouploans;
        })->sum('amount_repaid');

        $this->totalAmountApproved = $this->loanPhases->flatMap(function ($phase) {
            return $phase->grouploans;
        })->sum('amount_approved');
    }

    public function render()
    {

        return view('livewire.reports.code-list-report');
    }



    public function export()
    {
        return Excel::download(new LoanSubmExport, 'loan_submision.xlsx');
    }
}
