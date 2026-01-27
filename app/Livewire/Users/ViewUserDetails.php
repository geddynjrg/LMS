<?php

namespace App\Livewire\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;

class ViewUserDetails extends Component
{
    public ?User $user = null;
    public $showModal = false;

    #[On('open-view-user-modal')]
    public function loadUser($userId)
    {
        // Load user with roles, constituencies, and recently created assessments
        $this->user = User::with(['roles', 'constituencies', 'assessments' => function($q) {
            $q->latest()->limit(5);
        }])->findOrFail($userId);

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset('user');
    }

    public function render()
    {
        return view('livewire.users.view-user-details');
    }
}
