<?php

namespace App\Livewire;

use App\Models\County;
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\Auth;

class CreateUser extends Component
{
    public County $county;
    public $roles = ['admin','user',];
    public $name, $email, $password, $id_number, $phone_number;
    public $role = 'user';
    public $assignedConstituencies = []; // for multiselect
    public $availableConstituencies = [];

    public function mount (County $county)
    {
        $this->county = $county;
        $this->availableConstituencies = Auth::user()->constituencies()->get();

    }

    public function create()
    {
        $validated = Validator::make([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'county_code' => $this->county->county_code,
            'id_number' => $this->id_number,
            'phone_number' => $this->phone_number,
            'assignedConstituencies' => $this->assignedConstituencies,
            'role' => $this->role,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'county_code' => 'required|string|exists:counties,county_code',
            'id_number' => 'required|string|max:255|unique:users',
            'phone_number' => 'required|string|max:255',
            'assignedConstituencies' => 'required|array|min:1',
            'role' => 'required|in:' . implode(',', $this->roles),
        ])->validate();


        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'county_code' => $this->county->county_code,
            'password' => Hash::make($this->password),
            'id_number' => $this->id_number,
            'phone_number' => $this->phone_number,
        ]);

        // Assign default 'user' role
        $user->assignRole($this->role);

        // Attach constituencies (national officers operate at HQ/secretariat level only)
        if (! in_array($this->role, ['hq_officer', 'head_of_secretariat', 'accountant'], true)) {
            $user->constituencies()->attach($this->assignedConstituencies);
        }

        session()->flash('success', 'User created successfully.');
        return redirect()->route('users.viewusers', ['county' => $this->county->slug]);
    }


    public function render()
    {
        return view('livewire.users.create-user');
    }
}
