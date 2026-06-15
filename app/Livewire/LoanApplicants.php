<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\GroupLoan;
use Livewire\Attributes\On;

class LoanApplicants extends Component
{
    public $applications;
    public $county;
    public $constituency;

    public $pending;
    public $approved;
    public $deferred;
    public $cancelled;

    public function mount (County $county, Constituency $constituency)
    {
        $this->county = $county;
        $this->constituency = $constituency;
        $this->getApplications();
    }

    private function getApplications ()
    {
        $this->applications = GroupLoan::whereHas('loanPhase', function($query){
            $query->where('constituency_code', $this->constituency->constituency_code);
        })
        ->whereIn('loan_status', ['pending', 'approved', 'deferred', 'cancelled'])
        ->with(['loanPhase', 'group.ward'])
        ->orderBy('application_date', 'asc')
        ->get();

        $this->pending = $this->applications->where('loan_status', 'pending');
        $this->approved = $this->applications->where('loan_status', 'approved');
        $this->deferred = $this->applications->where('loan_status', 'deferred');
        $this->cancelled = $this->applications->where('loan_status', 'cancelled');
    }

    #[On('deleted_application')]
    public function deleted(){
        $this->dispatch('application-deleted');
    }

    public function render()
    {
        return view('livewire.loan.loan-applicants');
    }


}
