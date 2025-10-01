<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\GroupMember;
class DeleteMember extends Component
{

    public $member;
    public $loading = true; // default true to show loader initially
    #[On('deleteMember-modal')]
    public function removeMember($member)
    {
        $this->loading = true;
        $this->member = json_decode($member);
        $this->loading = false;
    }

    public function deleteMember()
    {
        if (auth()->user()->hasPermissionTo('delete member'))
        {
            $deletedRows = GroupMember::destroy($this->member->id);
            if ($deletedRows > 0) {
                $this->dispatch('refreshData', message: 'success')->to(LoanAplicant::class);
                $this->dispatch('refreshData', message: 'success')->to(GroupInfo::class);
                $this->dispatch('memberIsDeleted', message: 'success');
            } else {
                $this->dispatch('memberIsDeleted', message: 'error');
            }
        }

    }

    public function render()
    {
        return view('livewire.loan.delete-member');
    }
}
