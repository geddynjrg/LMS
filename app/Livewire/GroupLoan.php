<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\Group;
use App\Models\GroupMember;

class GroupLoan extends Component
{
    public Group $group;
    public $county;
    public $defaulters = [];
    public $membersArray = [];
    public $constituency;
    public $identifier;
    public $amount_issued = 0;
    public $amount_repaid = 0;
    public $balance = 0;
    public $applications = 0;
    public function mount(County $county, Constituency $constituency, Group $group)
    {
        $group->load([
            'ward',
            'groupLoans' => function ($query) {
                $query->where('loan_status', 'financed');
            },
            'groupLoans.loanPhase','groupLoans.groupMembers.member', 'groupLoans.loanReceipts'
        ]);
        $this->county = $county;
        $this->constituency = $constituency;
        $this->group = $group;
        $this->getGroup();
    }

    private function getGroup() {

        if ($this->group == null) {
            return redirect()->route( 'grouploans', ['county' => $this->county->county_name,'constituency' => ucwords(strtolower($this->constituency->constituency_name))]);
        }else {
            $this->applications = $this->group->groupLoans->count();
            $this->amount_issued = $this->group->groupLoans->sum('amount_issued');
            $this->amount_repaid = $this->group->groupLoans->sum('amount_repaid');
            $this->membersArray = $this->group->groupLoans->first()->groupMembers;
            $this->getDefaulters();
        }


    }
    private function getDefaulters ()
    {
        $this->defaulters = [];
        foreach ($this->membersArray as $member) {
            # search for member in members
            $memberLoans = GroupMember::where('member_Id', $member->member_Id)->where('group_loans_id', '!=', $this->group->groupLoans->first()->id)->with(['member','groupLoan.group','groupLoan.loanPhase.constituency'])->get();
            $memberDefaultedLoans = [];

            if(!$memberLoans->isEmpty())
            {
                foreach ($memberLoans as $memberloan) {
                    $balance = $memberloan->groupLoan->amount_issued - $memberloan->groupLoan->amount_repaid;

                    if ($balance > 0) {
                        array_push($memberDefaultedLoans, $memberloan);
                    }
                }
                if(count($memberDefaultedLoans) > 0)
                {
                    array_push($this->defaulters, $memberDefaultedLoans);
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.group-loan');
    }
}
