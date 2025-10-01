<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On; 
use App\Models\GroupLoan;
class IssueCheck extends Component
{
    public $appliedLoan;
    public $dateOfIssue;
    public $chequeNo;
    public $amount;

    #[On('finance')] 
    public function cheque($appliedLoan)
    {
        $this->appliedLoan = $appliedLoan;
    }

    public function financeLoan()
    {
        $this->validate([
            'dateOfIssue' => 'required|date',
            'chequeNo' => 'required|string|regex:/^[0-9]+$/',
            'amount' => 'required|numeric|gt:0',
        ]);

        $groupLoan = GroupLoan::with('group')->find($this->appliedLoan['id']);
        $groupLoan->cheque_no = $this->chequeNo;
        $groupLoan->amount_issued = $this->amount;
        $groupLoan->disbursement_date = $this->dateOfIssue;
        $groupLoan->loan_status = 'financed';
        $groupLoan->status = 'Active';
        $groupLoan->save();
        $this->dispatch('chequeIssued');
        return redirect()->route('groupinfo', ['county'=>auth()->user()->county->county_name,'constituency'=>ucwords(session('constituency')->constituency_name),'Group', 'group'=>$groupLoan->group->identifier]);
    }


    public function render()
    {
        return view('livewire.loan.issue-check');
    }
}
