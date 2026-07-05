<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\LoanPhase as Phases;
use App\Livewire\Concerns\ManagesPhaseDetailEditing;

class LoanPhase extends Component
{
    use ManagesPhaseDetailEditing;

    public $loanPhases;
    public $totalApplications;
    public $totalAmountIssued;
    public $totalAmountRepaid;
    public $phase;
    public County $county;
    public Constituency $constituency;


    public function mount (County $county, Constituency $constituency)
    {
        $this->county = $county;
        $this->constituency = $constituency;
        $this->getLoanPhases ();
    }

    public function getLoanPhases(): void
    {
        $this->loanPhases = Phases::whereHas('constituency', function ($query) {
            $query->where('constituency_code', $this->constituency->constituency_code);
        })
        ->with(['grouploans:id,loan_phase_id,amount_issued,amount_repaid'])
        ->withCount(['grouploans' => function ($query) {
            $query->whereIn('loan_status', ['approved', 'financed']);
        }])
        ->get();

        $this->totalApplications = $this->loanPhases->sum('grouploans_count');
        $this->totalAmountIssued = $this->loanPhases->flatMap(function ($phase) {
                return $phase->grouploans->pluck('amount_issued');
            })->sum();
        $this->totalAmountRepaid = $this->loanPhases->flatMap(function ($phase) {
                return $phase->grouploans->pluck('amount_repaid');
            })->sum();
    }

    public function addPhase()
    {
        $this->validate([
            'phase' => 'required|string'
        ]);

        if (auth()->user()->hasPermissionTo('create new phase')) {
            try {
                $newPhase = new Phases();
                $newPhase->phase = ucwords($this->phase);
                $newPhase->constituency_code = $this->constituency->constituency_code;
                $newPhase->save();
                $this->dispatch('addphase', message: 'New Phase Added');
                $this->getLoanPhases ();
            } catch (\Exception $e) {
                $this->dispatch('addphase', message: 'An error has occured please try again');
            }
        }
    }

    public function render()
    {
        return view('livewire.loan.loan-phase');
    }
}
