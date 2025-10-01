<?php

namespace App\Livewire;

use App\Models\Constituency;
use Livewire\Component;
use App\Models\GroupMember;
use Illuminate\View\View;

class GroupMembers extends Component
{

    public $members = null;
    public $totalMembers = 0;
    public $male = 0;
    public $female = 0;

    public $disabled = 0;
    public Constituency $constituency;
    public $isReady = false;

    public function mount(Constituency $constituency)
    {
        $this->isReady = false; // Set to false initially
        $this->constituency =  $constituency;
        $this->getMembers();

    }

    private function getMembers (): void
    {
        $code = $this->constituency->constituency_code;

        $this->members = GroupMember::whereHas('groupLoan.loanPhase.constituency', function ($query) use ($code) {
            $query->where('constituency_code', $code);
        })
        ->select('member_Id')
        ->with(['member'])
        ->distinct('member_Id')
        ->get();

        $this->totalMembers = $this->members->count();
        $mm = $this->members;

        $this->male = $mm->filter(function ($mm) {
            return $mm->member->gender === 'Male';
        })->count();

        $this->disabled = $mm->filter(function ($mm) {
            return $mm->member->disabled == true;
        })->count();

        $this->female = $this->members->count() - $this->male;

        $this->isReady = true; // Set to true after fetching members
    }

    public function ViewMember($member_Id)
    {
        dd($member_Id);
    }

    public function render():View
    {
        return view('livewire.group.group-members');
    }
}
