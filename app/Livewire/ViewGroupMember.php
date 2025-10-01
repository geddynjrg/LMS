<?php

namespace App\Livewire;

use App\Models\Constituency;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use App\Models\GroupMember;
use App\Models\Member;

class ViewGroupMember extends Component
{
    public $member;

    public $memberGroupLoans;

    public $name =  '';
    public $role = '';
    public $phonenumber =  '';
    public $gender =  '';
    public $disabled =  '';
    public $member_Id =  '';

    public $data = [];

    public $error_id;
    public $genders = ['Male', 'Female'];

    public Constituency $constituency;
    public $loading = true;

    #[On('group-member-info')]
    public function edit($data)
    {
        $this->loading = true;
        $this->resetMembers();
        $this->data = $data;
        if (!empty($this->data)){
            $this->phonenumber = $data[3];
            $this->name = $data[1];
            $this->member_Id = $data[2];
            $this->gender = $data[4];
            $this->disabled = $data[5]=='Yes'?true:false;
            $this->getMemberGroupLoans();
        }
        $this->loading = false;

    }

    public function mount (Constituency $constituency)
    {
        $this->constituency = $constituency;
    }

    public function updateMemberInformation()
    {
        if (auth()->user()->hasPermissionTo('update member')) {

        $this->validate([
            'name' => 'required|string',
            'gender' => 'required|in:' . implode(',', $this->genders),
            'member_Id' => 'required|string|between:6,10|regex:/^[0-9]+$/',
            'phonenumber' => 'required|regex:/^[0-9]{10}$/',
        ]);

        //if member has edited the ID
        if($this->data[2] === $this->member_Id)
        {//not changed
           $updatedMember = Member::where('member_Id', $this->data[2])
                ->update([
                    'name' => ucwords($this->name),
                    'number' => $this->phonenumber,
                    'gender' => $this->gender,
                    'disabled' => $this->disabled]);
            if ($updatedMember) {
                $this->dispatch('groupMemberEdited', message: 'success');
                $this->data[3] = $this->phonenumber;
                $this->data[1] = $this->name;
                $this->data[4] = $this->gender;
                $this->data[5] = $this->disabled;
            }else {
                $this->dispatch('groupMemberEdited', message: 'error');
            }
        }else {
            //changed
            //validate new id
            $this->validate([
                'member_Id' => [function($attribute, $value, $fail) {
                    $existsIdNumber = Member::where('member_Id', $value)->first();

                    if($existsIdNumber) {
                        $fail('The ID Number is already registered with another member.');
                    }
                }],
            ]);

            //update accordingly
            $updatedMember = Member::where('member_Id', $this->data[2])
                ->update([
                    'member_Id' => $this->member_Id,
                    'name' => ucwords($this->name),
                    'number' => $this->phonenumber,
                    'gender' => $this->gender,
                    'disabled' => $this->disabled]);
            if ($updatedMember) {
                $this->dispatch('groupMemberEdited', message: 'success');
                $this->data[3] = $this->phonenumber;
                $this->data[2] = $this->member_Id;
                $this->data[1] = $this->name;
                $this->data[4] = $this->gender;
                $this->data[5] = $this->disabled;
            }else {
                $this->dispatch('groupMemberEdited', message: 'error');
            }
        }
    }
    }

    private function resetMembers()
    {
        $this->name = '';
        $this->role = '';
        $this->phonenumber = '';
        $this->gender = '';
        $this->disabled = false;
        $this->member_Id = '';
        $this->memberGroupLoans = null;
    }

    private function getMemberGroupLoans()
    {
        if (!empty($this->data)){
            $this->memberGroupLoans = GroupMember::where('member_Id', $this->data[2])
                ->with(['groupLoan.group','groupLoan.loanPhase.constituency'])->get();
        }
    }

    public function deleteAccount()
    {
        if ( auth()->user()->hasPermissionTo('delete member') && !empty($this->data))
        {
            $deleted = Member::where('member_Id', $this->data[2])->delete();
            if ($deleted) {
                $this->dispatch('groupMemberEdited', message: 'deleted');
                $this->resetMembers();
            } else {
                $this->dispatch('groupMemberEdited', message: 'error');
            }
        }
    }

    public function render()
    {
        return view('livewire.group.view-group-member');
    }
}
