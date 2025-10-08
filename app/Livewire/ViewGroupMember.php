<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Member;
use App\Models\GroupMember;
use Livewire\Attributes\On;

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

    #[On('loadMemberData')]
    public function loadMemberData($memberId = null) // Make parameter optional with default value
    {

        $data = $memberId ?? null; // Use null if no data provided
        \Log::info('loadMemberData received:', ['data' => $data]);

        if (!$data) {
            \Log::warning('loadMemberData called with no data');
            return;
        }

        if (is_array($data) && isset($data['memberId'])) {
            $this->memberId = $data['memberId'];
        } else {
            $this->memberId = $data;
        }

        // Call loadMember after setting the memberId
        $this->loadMember();
    }

    #[On('memberModalClosed')]
    public function resetComponent()
    {
        \Log::info('Resetting component');
        $this->resetExcept(['memberId']);
        $this->loading = true;
        $this->activeTab = 'edit';
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

            // Load member groups with proper relationships
            $this->groups = GroupMember::where('member_Id', $this->memberId)
                ->with([
                    'groupLoan.group',
                    'groupLoan.loanPhase.constituency'
                ])
                ->get()
                ->map(function($groupMember) {
                    $groupLoan = $groupMember->groupLoan;
                    $group = $groupLoan->group ?? null;
                    $loanPhase = $groupLoan->loanPhase ?? null;
                    $constituency = $loanPhase->constituency ?? null;

                    return [
                        'group_name' => $group->name ?? 'N/A',
                        'constituency' => $constituency->constituency_name ?? 'N/A',
                        'position' => $groupMember->role ?? 'N/A',
                        'loan_phase' => $loanPhase->phase ?? 'N/A',
                        'amount_issued' => $groupLoan->amount_issued ?? 0,
                        'balance' => ($groupLoan->amount_issued ?? 0) - ($groupLoan->amount_repaid ?? 0),
                    ];
                });

            \Log::info('Groups loaded:', [
                'count' => count($this->groups),
                'sample' => $this->groups->first() // Log first group for debugging
            ]);

            $this->loading = false;

        } catch (\Exception $e) {
            \Log::error('Error in loadMember:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->loading = false;
        }
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
            //GroupMember::where('member_Id', $this->memberId)->delete();
            //$this->member->delete();
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
