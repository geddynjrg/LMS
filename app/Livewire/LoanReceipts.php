<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\GroupLoan;
use App\Models\LoanReceipts as Receipts;

class LoanReceipts extends Component
{

    public $constituency;


    public $receipts = [];



    public function mount(County $county, Constituency $constituency)
    {
        $this->constituency = $constituency;
        $this->getReceipts();
    }

    function getReceipts() {
        $code = $this->constituency->constituency_code;

        $this->receipts = Receipts::whereHas('groupLoan.loanPhase.constituency', function ($query) use ($code) {
            $query->where('constituency_code', $code);
        })
        ->with('groupLoan.group')
        ->orderBy('transaction_date', 'desc')
        ->get();
    }


    public function render()
    {
        return view('livewire.loan.loan-receipts');
    }
}
