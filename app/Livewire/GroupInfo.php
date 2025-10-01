<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupLoan;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
class GroupInfo extends Component
{


    public $group;
    public $groups;
    public $county;
    public $constituency;
    public $defaulters = [];
    public $membersArray = [];
    public $amount_issued = 0;
    public $amount_repaid = 0;
    public $balance = 0;
    public $applications = 0;
    public string|null $selectedGroupSlug = null;
    public ?Group $selectedGroup = null;

    public $identifier;
    protected $listeners = ['groupSelected'];


    #[On('group-select')]
    public function lockRegister(string $slug)
    {
        // Your logic heredd
        $this->group = Group::with([
            'ward','groupLoans.loanPhase','groupLoans.groupMembers.member', 'groupLoans.loanReceipts'
        ])->where('slug', $slug)->first();
        $this->getGroup();
    }


    public function mount(Group $group, Constituency $constituency, County $county) {
        $constituency->load([
            'groups',
        ]);
        // eager‑load everything you need onto the same instance
        $group->load([
            // only “financed” loans + their nested relations
            /*'groupLoans' => function ($query) {
                $query->where('loan_status', 'financed')
                      ->with([
                          'loanPhase',
                          'groupMembers.member',
                          'loanReceipts',
                      ]);
            },*/

            // simple relations
            'ward','groupLoans.loanPhase','groupLoans.groupMembers.member', 'groupLoans.loanReceipts',
        ]);
        $this->group = $group;
        $this->groups = $constituency->groups;
        $this->identifier = $group->identifier;
        $this->county = $county;
        $this->constituency = $constituency;


        $this->getGroup();

    }

    function getGroup() {
        $this->applications = $this->group->groupLoans->count();
        $this->amount_issued = $this->group->groupLoans->sum('amount_issued');
        $this->amount_repaid = $this->group->groupLoans->sum('amount_repaid');
        $this->membersArray = $this->group->groupLoans->first()->groupMembers;
        $this->getDefaulters();
    }


    private function getDefaulters ()
    {
        $this->defaulters = [];
        foreach ($this->membersArray as $member) {
            # search for member in members
            $memberLoans = GroupMember::where('member_Id', $member->member_Id)
            ->where('group_loans_id', '!=', $this->group->groupLoans->first()->id)
            ->with(['groupLoan.group','groupLoan.loanPhase.constituency'])
            ->get();
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

    public function editGroupData()
    {
        $groupLoan = GroupLoan::where('id', $this->group->groupLoans->last()->id)->with(['group.ward'])->first();
        //dd( $groupLoan);
        $this->dispatch('edit-data-modal', groupLoan: $groupLoan)->to(EditGroupData::class);
        $this->dispatch('show-groupdata-modal');
    }

    public function addMember($groupLoan_id)
    {
        //dd( $groupLoan_id);
        $this->dispatch('addMember-modal', group_loans_id: $groupLoan_id)->to(AddMember::class);
        $this->dispatch('show-addMember-modal');
    }

    public function editMember($member)
    {
        //dd($member);
        $this->dispatch('editMember-modal', member: $member)->to(EditMember::class);
        $this->dispatch('show-editMember-modal');
    }

    public function removeMember($member)
    {
        $this->dispatch('deleteMember-modal', member: $member)->to(DeleteMember::class);
        $this->dispatch('show-deleteMember-modal');
    }

    #[On('refreshData')]
    public function refreshData($message)
    {
        if($message === 'success')
        {
            $this->getGroup();
        }
    }

    public function render()
    {
        return view('livewire.group.group-info');
    }
}
