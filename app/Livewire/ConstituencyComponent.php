<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Group;
use App\Models\Member;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use App\Models\Constituency;


class ConstituencyComponent extends Component
{
    public $groups;
    public $totalgroups;
    public $members;
    public $totalmembers;
    public $totalAmountIssued;
    public $totalAmountRepaid;
    public Constituency $constituency;


    public function mount (Constituency $constituency)
    {
        $this->constituency = $constituency;
        $this->allGroups();
        $this->allmembers();
        $this->loans();
    }

    function allGroups() {
        $const_code = $this->constituency->constituency_code;
        $this->groups = Group::whereIn('ward_id', function ($query) use ($const_code) {
            $query->select('id')
                ->from('wards')
                ->where('constituency_code', $const_code);
        })
        ->with('ward:id,ward_name')
        ->get();

        $this->totalgroups = $this->groups->count();
    }

    function allmembers() {

        $this->constituency->load([
            'phases.groupLoans.groupMembers' => function ($query) {
                //$query->distinct('member_Id');
            }
        ]);

        $membersArr = $this->constituency->phases->flatMap->groupLoans->flatMap->groupMembers;

        $this->totalmembers = $membersArr->unique('member_Id')->count();

    }

    function loans () {
        $code = $this->constituency->constituency_code;
        $loandetails = GroupLoan::with(['loanPhase.constituency', 'group'])->
        whereHas('loanPhase.constituency', function ($query) use ($code) {
            $query->where('constituency_code', $code);
        })->where('loan_status', 'financed');
        $this->totalAmountIssued = $loandetails->sum('amount_issued');

        $this->totalAmountRepaid = $loandetails->sum('amount_repaid');

    }

    public function render()
    {
        return view('livewire.constituency-component');
    }
}
