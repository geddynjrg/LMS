<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Member;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Route;
class AddMember extends Component
{
    public $group_loans_id;
    public $roles = ['Chairperson','Secretary','Treasurer','Member'];
    public $genders = ['Male', 'Female'];
    public $groupMember = [
        'name' => '',
        'role' => '',
        'phonenumber' => '',
        'gender' => '',
        'disabled' => false,
        'member_Id' => '',
    ];
    public $error_id;
    public $loading = true;


    #[On('addMember-modal')]
    public function addMember($group_loans_id)
    {
        $this->loading = true;
        $this->group_loans_id = $group_loans_id;
        $this->loading = false;
    }

    public function submitMember()
    {
        $this->validate([
            'groupMember.name' => 'required|string',
            'groupMember.role' => 'required|in:' . implode(',', $this->roles),
            'groupMember.gender' => 'required|in:' . implode(',', $this->genders),
            'groupMember.member_Id' => 'required|string|regex:/^[0-9]{6,8}$/',
            'groupMember.phonenumber' => 'required|regex:/^[0-9]{10}$/',
        ]);

        $this->memberIdValidation($this->groupMember['member_Id']);
    }

    private function memberIdValidation($member_Id)
    {
        if (strlen($member_Id) >= 6 && strlen($member_Id) <= 8)
        {

            $exiMember = GroupMember::where('group_loans_id', $this->group_loans_id)
                    ->where('member_Id', $member_Id)->first();

            if ($exiMember){
                $this->dispatch('memberIsAdded', message: 'exists');
                return;
            }else {
                $member = Member::updateOrCreate(
                    ['member_Id' => $this->groupMember['member_Id']],
                    [
                        'name' => ucwords($this->groupMember['name']),
                        'number' => $this->groupMember['phonenumber'],
                        'gender' => $this->groupMember['gender'],
                        'disabled' => $this->groupMember['disabled'],
                    ]
                );

                if($member){
                    $this->addGroupMember($member_Id);
                }else {
                    $this->dispatch('memberIsAdded', message: 'error');
                }
            }


        }else
        {
            $this->error_id = 'The ID number is invalid.';
        }
    }

    private function addGroupMember($member_Id)
    {
        $groupMember = new GroupMember();
        $groupMember->member_Id = $member_Id;
        $groupMember->group_loans_id = $this->group_loans_id;
        $groupMember->role = $this->groupMember['role'];

        /** @var \App\Models\User $user */
        if (auth()->user()->hasPermissionTo('create member')) {
            $groupMember->save();

            if($groupMember)
            {
                $this->resetMembers();
                $this->dispatch('refreshData', message: 'success')->to(LoanAplicant::class);
                $this->dispatch('refreshData', message: 'success')->to(GroupInfo::class);
                $this->dispatch('memberIsAdded', message: 'success');
            }else {
                $this->dispatch('memberIsAdded', message: 'error');
            }
        }
    }

    private function resetMembers()
    {
        $this->groupMember = [
            'name' => '',
            'role' => '',
            'phonenumber' => '',
            'gender' => '',
            'disabled' => false,
            'member_Id' => '',
        ];
        $this->group_loans_id = null;
    }

    public function render()
    {
        return view('livewire.loan.add-member');
    }
}
