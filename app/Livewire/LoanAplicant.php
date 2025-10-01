<?php

namespace App\Livewire;
use Livewire\Attributes\On;

use Livewire\Component;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use App\Models\Group;

use App\Exports\GroupInstInfor;
use App\Models\County;
use App\Models\Constituency;
use Maatwebsite\Excel\Facades\Excel;


class LoanAplicant extends Component
{
    public $loanId;
    public $groupLoans = [];
    public GroupLoan $appliedLoan;
    public $membersArray = [];
    public $defaulters = [];

    public $amount;
    public $gfscode;
    public $gfCode;
    public $code;
    public $errorCode;
    public $identifier;

    public County $county;
    public Group $group;
    public Constituency $constituency;

    public function mount(County $county, Constituency $constituency, Group $group)
    {
        $group->load([
            'ward',
            'groupLoans' => function ($query) {
                $query->where('loan_status', '!=', 'financed');
            },
        ]);
        $this->county = $county;
        $this->group = $group;
        $this->constituency = $constituency;
        $this->appliedLoan = $this->group->grouploans->last()->load([
            'loanPhase','groupMembers.member',
        ]);
        $this->getGroupData();
    }

    /*public function updatedCode ($value)
    {
        $this->gfscode = $this->gfCode.str($value);
    }*/

    private function getGroupData ()
    {
        $this->appliedLoan = $this->group->grouploans->last()->load([
            'loanPhase','groupMembers.member',
        ]);

        if ($this->appliedLoan !== null)
        {
            $this->membersArray = $this->appliedLoan->groupMembers;
            if ($this->appliedLoan->level === 'Refinancing') $this->groupLoans = $this->group->grouploans;
            $this->getdefaulters();
        } else {
            return redirect()->route('const.loans.applicants', ['county'=> $this->county->slug, 'constituency'=>$this->constituency->slug]);
        }

    }

    private function getdefaulters ()
    {
        $this->defaulters = [];
        foreach ($this->membersArray as $member) {
            # search for member in members
            $memberLoans = GroupMember::where('member_Id', $member->member_Id)->with(['member','groupLoan.group','groupLoan.loanPhase.constituency'])->get();
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

    public function approve()
    {
        $this->dispatch('approve-modal', appliedLoan: $this->appliedLoan)->to(ApproveApplication::class);
        $this->dispatch('show-approve-modal');
    }

    public function finance()
    {
        $this->dispatch('finance', appliedLoan: $this->appliedLoan)->to(IssueCheck::class);
        $this->dispatch('show-cheque-modal');
    }

    #[On('loanApproved')]
    public function refresh($message)
    {
        if($message === 'success')
        {
            $this->getAppliedLoan();
        }
    }

    #[On('refreshData')]
    public function refreshData($message)
    {
        if($message === 'success')
        {
            $this->getGroupData();
        }
    }


    public function save()
    {
        $this->dispatch('post-created', postId: $this->post->id);
    }


    public function addMember($groupLoan_id)
    {
        //dd($this->appliedLoan->id, $groupLoan_id);
        $this->dispatch('addMember-modal', group_loans_id: $this->appliedLoan->id)->to(AddMember::class);
        $this->dispatch('show-addMember-modal');
    }

    public function removeMember($member)
    {
        $this->dispatch('deleteMember-modal', member: $member)->to(DeleteMember::class);
        $this->dispatch('show-deleteMember-modal');
    }

    public function editMember($member)
    {
        //dd($member);
        $this->dispatch('editMember-modal', member: $member)->to(EditMember::class);
        $this->dispatch('show-editMember-modal');
    }

    public function differ()
    {
        $this->dispatch('differ-modal', loanId: $this->appliedLoan->id)->to(DifferApplication::class);
        $this->dispatch('show-differ-modal');
    }

    public function cancel()
    {
        $this->dispatch('cancel-modal', loanId: $this->appliedLoan->id)->to(CancelApplication::class);
        $this->dispatch('show-cancel-modal');
    }

    public function delete()
    {
        $this->dispatch('show-delete-modal', message: 'show');
    }

    public function confirmDelete()
    {
        $delGroupLoan = GroupLoan::destroy($this->appliedLoan->id);
        if ($delGroupLoan > 0) {
            if($this->appliedLoan->level == 'New'){
                $delGroup = Group::destroy($this->appliedLoan->group_id);
                if ($delGroup > 0) {
                    $this->dispatch('deleted_application', message: 'success');
                    return redirect()->route('applicants', ['county'=>auth()->user()->county->county_name, 'constituency'=> ucwords(session('constituency')->constituency_name)]);
                }else {
                    $this->dispatch('show-delete-modal', message: 'error');
                }
            }else {
                $this->dispatch('deleted_application', message: 'success');
                return redirect()->route('applicants', ['county'=>auth()->user()->county->county_name, 'constituency'=> ucwords(session('constituency')->constituency_name)]);
            }
        } else {
            $this->dispatch('show-delete-modal', message: 'error');

        }
    }


    public function editGroupData()
    {
        //dd($this->appliedLoan);
        $this->dispatch('edit-data-modal', groupLoan: $this->appliedLoan)->to(EditGroupData::class);
        $this->dispatch('show-groupdata-modal');
    }

    public function export()
    {
        return Excel::download(new GroupInstInfor($this->group, $this->constituency), $this->group->name.'_institutional_information.xlsx');
    }

    public function render()
    {
        return view('livewire.loan.loan-aplicant');
    }


}
