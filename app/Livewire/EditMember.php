<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\GroupMember;
use App\Models\Member;
class EditMember extends Component
{
    public $member;
    public $groupMember = [
        'name' => '',
        'role' => '',
        'phonenumber' => '',
        'gender' => '',
        'disabled' => false,
        'member_Id' => '',
    ];
    public $error_id;
    public $roles = ['Chairperson','Secretary','Treasurer','Member'];
    public $genders = ['Male', 'Female'];
    public $loading = true; // default true to show loader initially


    #[On('editMember-modal')]
    public function editMember($member)
    {
        $this->loading = true;

        $this->member = is_string($member) ? json_decode($member) : $member;
        $this->groupMember = [
            'name' => $this->member->member->name,
            'role' => $this->member->role,
            'phonenumber' => $this->member->member->number,
            'gender' => $this->member->member->gender,
            'disabled' => $this->member->member->disabled == 1 ? true : false,
            'member_Id' => $this->member->member_Id,
        ];
        $this->loading = false;
        $this->error_id = null;
    }

    public function submitMember()
    {
        $this->validate([
            'groupMember.name' => 'required|string',
            'groupMember.role' => 'required|in:' . implode(',', $this->roles),
            'groupMember.gender' => 'required|in:' . implode(',', $this->genders),
            'groupMember.member_Id' => 'required|string|regex:/^[0-9]{6,10}$/',
            'groupMember.phonenumber' => 'required|regex:/^[0-9]{10}$/',
        ]);

        if($this->member->member_Id === $this->groupMember['member_Id'])
        {
            $this->editValidMember();
        }else {
            $this->validate([
                'groupMember.member_Id' => [function($attribute, $value, $fail) {
                    $existsIdNumber = Member::where('member_Id', $value)->first();

                    if($existsIdNumber) {
                        $fail('The ID Number is already registered with another member.');
                    }
                }],
            ]);

            $updatedMemberID = Member::where('member_Id', $this->member->member_Id)
                ->update([
                    'member_Id' => $this->groupMember['member_Id'],
                ]);

            if($updatedMemberID)
            {
                $this->member->member_Id = $this->groupMember['member_Id'];
                $this->editValidMember();
            } else {
                $this->dispatch('memberIsEdited', message: 'idError');
            }
        }
    }


    private function editValidMember()
    {
        $member = Member::where('member_Id', $this->groupMember['member_Id'])->first();
        $member->name = ucwords($this->groupMember['name']);
        $member->number = $this->groupMember['phonenumber'];
        $member->gender = $this->groupMember['gender'];
        $member->disabled = $this->groupMember['disabled'];

        if (auth()->user()->hasPermissionTo('update member')) {
            if ($member->save()){

                if ($this->groupMember['role'] !== $this->member->role)
                {
                    $groupMember = GroupMember::where('id', $this->member->id)
                        ->update([
                            'role' => $this->groupMember['role'],
                    ]);

                }
                $this->dispatch('refreshData', message: 'success')->to(LoanAplicant::class);
                $this->dispatch('refreshData', message: 'success')->to(GroupInfo::class);
                $this->dispatch('memberIsEdited', message: 'success');
                $this->resetMembers();
            } else {
                $this->dispatch('memberIsEdited', message: 'error');
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
    }

    public function render()
    {
        return view('livewire.loan.edit-member');
    }
}
