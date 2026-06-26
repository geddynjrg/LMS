<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupLoan;
use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Services\Group\GroupsService;
use Barryvdh\DomPDF\Facade\Pdf;

class GroupInfo extends Component
{

    protected GroupsService $groupsService;

    public $groupData;
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

    public function boot(GroupsService $groupsService)
    {
        $this->groupsService = $groupsService;
    }

    public function mount(Group $group, Constituency $constituency, County $county) {

        $this->groupData = $this->getGroupData($this->group);
        $this->group = $group;


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

        $this->groups = $constituency->groups;
        $this->identifier = $group->identifier;
        $this->county = $county;
        $this->constituency = $constituency;


        $this->getGroup();

    }

    public function getGroupData($group)
    {
        return $this->groupsService->getGroupQuery($group->slug)->first();
    }

     #[On('group-select')]
    public function lockRegister(string $slug)
    {
        // Your logic heredd
        $this->group = Group::with([
            'ward','groupLoans.loanPhase','groupLoans.groupMembers.member', 'groupLoans.loanReceipts'
        ])->where('slug', $slug)->first();
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

    #[Computed(persist: true)]
    public function totalAmountIssued()
    {
        return $this->groupData->total_amount_issued ?? 0;
    }

    #[Computed(persist: true)]
    public function totalAmountRepaid()
    {
        return $this->groupData->total_amount_repaid ?? 0;
    }

    #[Computed(persist: true)]
    public function totalAmountInArrears()
    {
        return $this->groupData->total_arrears ?? 0;
    }

    #[Computed(persist: true)]
    public function totalAmountDue()
    {
        return $this->groupData->total_amount_due ?? 0;
    }

    #[Computed(persist: true)]
    public function successFunding()
    {
        return $this->groupData->successful_applications_count ?? 0;
    }

    #[Computed(persist: true)]
    public function declinedFunding()
    {
        return $this->groupData->unsuccessful_applications_count ?? 0;
    }

    #[Computed(persist: true)]
    public function totalBalance()
    {
        return $this->totalAmountIssued - $this->totalAmountRepaid;
    }

    #[Computed(persist: true)]
    public function recoveryRate()
    {
        return $this->totalAmountIssued > 0
            ? round(($this->totalAmountRepaid / $this->totalAmountIssued) * 100, 1)
            : 0;
    }

    #[Computed(persist: true)]
    public function collectionEfficiency()
    {
        // How much of the amount currently due has actually been collected?
        return $this->totalAmountDue > 0
            ? round(($this->totalAmountRepaid / $this->totalAmountDue) * 100, 1)
            : 0;
    }

    #[Computed(persist: true)]
    public function parRate()
    {
        // Portfolio at Risk: What percentage of the total issued is currently in arrears?
        return $this->totalAmountIssued > 0
            ? round(($this->totalAmountInArrears / $this->totalAmountIssued) * 100, 1)
            : 0;
    }

    #[Computed(persist: true)]
    public function stats()
    {
        return [
            [
                'label' => 'Total Amount Issued',
                'value' => $this->totalAmountIssued,
                'sub_label' => "Total Principal from {$this->successFunding} applications",
                'icon' => 'fa-hand-holding-usd',
                'color' => 'primary',
                'has_progress' => false,
                'percentage' => 0
            ],
            [
                'label' => 'Total Repaid',
                'value' => $this->totalAmountRepaid,
                'sub_label' => 'Recovery Rate',
                'icon' => 'fa-check-double',
                'color' => 'success',
                'has_progress' => true,
                'percentage' => $this->recoveryRate
            ],
            [
                'label' => 'Current Balance',
                'value' => $this->totalBalance,
                'sub_label' => 'Outstanding Amount',
                'icon' => 'fa-file-invoice-dollar',
                'color' => 'info',
                'has_progress' => false,
                'percentage' => 0
            ],
            [
                'label' => 'Total In Arrears',
                'value' => $this->totalAmountInArrears,
                'sub_label' => 'Past Due Amount',
                'icon' => 'fa-exclamation-circle',
                'color' => $this->totalAmountInArrears > 0 ? 'danger' : 'secondary',
                'has_progress' => false,
                'percentage' => 0
            ],
        ];
    }

    public function downloadReport()
    {
        $data = [
            'groupName'            => $this->groupData->name,
            'date'                 => now()->format('d M, Y'),
            'totalAmountIssued'    => $this->totalAmountIssued,
            'totalAmountRepaid'    => $this->totalAmountRepaid,
            'totalAmountInArrears' => $this->totalAmountInArrears,
            'totalAmountDue'       => $this->totalAmountDue,
            'totalBalance'         => $this->totalBalance,
            'recoveryRate'         => $this->recoveryRate,
            'collectionEfficiency' => $this->collectionEfficiency,
            'parRate'              => $this->parRate,
            'successFunding'       => $this->successFunding,
        ];

        $pdf = Pdf::loadView('pdf.group-stats-report', $data);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Group_Report_' . str_replace(' ', '_', $this->groupData->name) . '.pdf');
    }

    public function render()
    {

        // dd($this->groupData->groupLoans->last()->female_count);
        return view('livewire.group.group-info', [
            'groupData' => $this->groupData,
        ]);
    }
}
