<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\GroupLoan;

class GroupLoans extends Component
{

    public $amount_issued = 0;
    public $amount_repaid = 0;
    public $applications = 0;
    public $grouploans = null;
    public $balance = 0;
    public Constituency $constituency;
    public County $county;


    public function mount (County $county, Constituency $constituency)
    {
        $this->constituency = $constituency;
        $this->county = $county;


        $this->loans();
    }

    function loans () {
        $code = $this->constituency->constituency_code;

        $this->grouploans = GroupLoan::whereHas('loanPhase.constituency', function ($query) use ($code) {
                $query->where('constituency_code', $code);
            })->where('loan_status', 'financed')
            ->with(['loanPhase', 'group.ward'])
            ->get();

        $this->applications = $this->grouploans->count();
        $this->amount_issued = $this->grouploans->sum('amount_issued');
        $this->amount_repaid = $this->grouploans->sum('amount_repaid');
        $this->balance = $this->amount_issued - $this->amount_repaid;

    }


    public function render()
    {
        return view('livewire.loan.group-loans');
    }


}
