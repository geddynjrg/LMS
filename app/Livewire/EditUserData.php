<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;
use App\Models\User;


class EditUserData extends Component
{
    public $roles = ['admin','user',];
    public $name, $email, $id_number, $phone_number, $role;
    public $loading = true; // default true to show loader initially
    public $user;
    public int $userId;



    #[On('edit-user-data')]
    public function edit($user)
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

    #[On('edit-user-modal-closed')]
    public function closed()
    {

        $this->reset(['name', 'email', 'id_number', 'phone_number', 'role', 'userId']);
        $this->resetErrorBag();
        $this->loading = true;
    }

    public function update()
    {
        $this->email = trim($this->email);
        $this->id_number = trim($this->id_number);
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->userId),
            ],
            'id_number' => [
                'required',
                'string',
                Rule::unique('users')->ignore($this->userId),
            ],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:admin,user'],
        ]);

        if ($this->user['email'] !== $this->email)
        {
            $this->validate(['email' => 'unique:users']);
        }
        if ($this->user['id_number'] !== $this->id_number)
        {
            $this->validate(['id_number' => 'unique:users']);
        }

        $user = User::findOrFail($this->userId);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'id_number' => $this->id_number,
            'phone_number' => $this->phone_number,
        ]);

        // Sync role if changed
        if (!$user->hasRole($this->role)) {
            $user->syncRoles([$this->role]);
        }

        session()->flash('success', 'User updated successfully.');

        $this->dispatch('user-updated'); // optional event
    }



    public function render()
    {
        return view('livewire.users.edit-user-data');
    }
}
