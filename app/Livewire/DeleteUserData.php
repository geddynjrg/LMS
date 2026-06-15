<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;
use App\Models\User;

class DeleteUserData extends Component
{
    //destroy
    public $name, $email, $id_number, $phone_number, $role;
    public $loading = true; // default true to show loader initially
    public $user;
    public int $userId;

    #[On('delete-user-data')]
    public function delete($user)
    {

        $this->loading = true;

        $this->userId = (int) $user['id'];
        $this->name = $user['name'];
        $this->email = trim($user['email']);
        $this->id_number = trim($user['id_number']);
        $this->phone_number = $user['phone_number'];
        $this->role = $user['role'];
        $this->user = $user;
        $this->loading = false;
    }

    #[On('delete-user-modal-closed')]
    public function closed()
    {

        $this->reset(['name', 'email', 'id_number', 'phone_number', 'role', 'userId']);
        $this->loading = true;
    }

    public function destroy()
    {

        $user = User::find($this->userId);

        if ($user) {
            $user->delete();
            $this->dispatch('user-deleted');
        }else {
            $this->dispatch('user-unknown');
        }

    }


    public function render()
    {
        return view('livewire.users.delete-user-data');
    }
}
