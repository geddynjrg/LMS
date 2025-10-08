<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Member;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Log;

class ViewGroupMember extends Component
{
    public $memberId;
    public $member;
    public $groups = [];
    public $activeTab = 'edit';
    public $loading = true;

    // Member fields for editing
    public $name;
    public $member_Id;
    public $number;
    public $gender;
    public $disabled = false;

    protected $listeners = [
        'loadMemberData',
        'memberModalClosed' => 'resetComponent'
    ];

    public function mount()
    {
        \Log::info('ViewGroupMember component mounted');
    }

    public function loadMemberData($data)
    {
        \Log::info('loadMemberData received:', ['data' => $data]);

        if (is_array($data) && isset($data['memberId'])) {
            $this->memberId = $data['memberId'];
        } else {
            $this->memberId = $data;
        }

        \Log::info('Processing member ID:', ['memberId' => $this->memberId]);

        $this->loading = true;
        $this->loadMember();
    }

    public function loadMember()
    {
        try {
            \Log::info('Loading member from database:', ['memberId' => $this->memberId]);

            // Load member basic info
            $this->member = Member::where('member_Id', $this->memberId)->first();

            if (!$this->member) {
                \Log::error('Member not found in database:', ['memberId' => $this->memberId]);
                $this->loading = false;
                return;
            }

            \Log::info('Member found:', ['memberName' => $this->member->name]);

            // Set form fields
            $this->name = $this->member->name;
            $this->member_Id = $this->member->member_Id;
            $this->number = $this->member->number;
            $this->gender = $this->member->gender;
            $this->disabled = (bool)$this->member->disabled;

            // Load member groups - adjust based on your actual relationships
            $this->groups = GroupMember::where('member_Id', $this->memberId)
                ->get()
                ->map(function($groupMember) {
                    return [
                        'group_name' => $groupMember->group_name ?? 'N/A',
                        'constituency' => $groupMember->constituency ?? 'N/A',
                        'position' => $groupMember->position ?? 'N/A',
                        'loan_phase' => $groupMember->loan_phase ?? 'N/A',
                        'amount_issued' => $groupMember->amount_issued ?? 0,
                        'balance' => $groupMember->balance ?? 0,
                    ];
                });

            \Log::info('Groups loaded:', ['count' => count($this->groups)]);

            $this->loading = false;

        } catch (\Exception $e) {
            \Log::error('Error in loadMember:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->loading = false;
        }
    }

    public function resetComponent()
    {
        \Log::info('Resetting component');
        $this->resetExcept(['memberId']);
        $this->loading = true;
        $this->activeTab = 'edit';
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updateMember()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'number' => 'nullable|string|max:20',
            'gender' => 'required|in:Male,Female',
            'disabled' => 'boolean',
        ]);

        try {
            $this->member->update([
                'name' => $this->name,
                'number' => $this->number,
                'gender' => $this->gender,
                'disabled' => $this->disabled,
            ]);

            $this->dispatch('groupMemberEdited', message: 'success');
            session()->flash('message', 'Member updated successfully.');

        } catch (\Exception $e) {
            $this->dispatch('groupMemberEdited', message: 'error');
            session()->flash('error', 'Error updating member: ' . $e->getMessage());
        }
    }

    public function deleteMember()
    {
        try {
            GroupMember::where('member_Id', $this->memberId)->delete();
            $this->member->delete();
            $this->dispatch('groupMemberEdited', message: 'deleted');
        } catch (\Exception $e) {
            $this->dispatch('groupMemberEdited', message: 'error');
            session()->flash('error', 'Error deleting member: ' . $e->getMessage());
        }
    }

    public function render()
    {
        \Log::info('Rendering component:', [
            'loading' => $this->loading,
            'memberId' => $this->memberId,
            'member' => $this->member ? $this->member->name : 'null',
            'groups_count' => count($this->groups)
        ]);

        return view('livewire.group.view-group-member');
    }
}
