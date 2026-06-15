<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\GroupMember;

class LoanedMembers extends Component
{
    public $members = [];
    public $totalMembers = 0;
    public $male = 0;
    public $female = 0;
    public Constituency $constituency;

    public function mount (County $county, Constituency $constituency)
    {
        $this->constituency = $constituency;
        $this->getMembers();
    }

    function getMembers ()
    {
        $code = $this->constituency->constituency_code;

        $this->members = GroupMember::whereHas('groupLoan.loanPhase.constituency', function ($query) use ($code) {
            $query->where('constituency_code', $code);
        })
        ->with(['member', ])
        ->get();

        $this->totalMembers = $this->members->pluck('member.member_Id')->unique()->count();

        $mm = $this->members;

        /*$this->members = $mm->sortBy(function ($mm) {
            return $mm->member->name;
        });*/

        $this->male = $mm->filter(function ($mm) {
            return $mm->member->gender === 'Male';
        })->pluck('member.member_Id')->unique()->count();

        $this->female = $this->totalMembers - $this->male;
    }


    public function render()
    {
        return view('livewire.loan.loaned-members');
    }
}
