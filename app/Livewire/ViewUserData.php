<?php

namespace App\Livewire;

use App\Models\County;
use App\Models\User;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class ViewUserData extends Component
{

    public County $county;
    public User $user;
    public $roles = ['admin','user',];
    public $name, $email, $id_number, $phone_number, $role;
    public $updateLoading = true; // default true to show loader initially
    public array $userPermissions = [];
    public array $userConstituencies = [];
    public string $activeTab = 'activity';
    public $activities;

    public function mount (County $county, User $user)
    {

        $this->county = $county;
        $user->load([
            'constituencies',
        ]);

        $this->user = $user;
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->id_number = $this->user->id_number;
        $this->phone_number = $this->user->phone_number;
        $this->role = $this->user->roles->pluck('name')->join(', ');
        $this->userPermissions = $this->user->getAllPermissions()->pluck('name')->toArray();
        $this->userConstituencies = $user->constituencies->pluck('constituency_code')->toArray();
        //$userPermission = $user->getPermissionNames()->toArray();
        //dd($userPermission, $this->userPermissions);
        $this->activities = \Spatie\Activitylog\Models\Activity::with('subject')
        ->where('causer_type', User::class)
        ->where('causer_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

        /*if ($user->slug !== $this->user->slug) {
            return redirect()->route('users.viewuser', [
                'county' => $county->slug,
                'user' => $this->user->slug,
            ]);
        }*/
    }

    public function updateUser()
    {
        $this->email = trim($this->email);
        $this->id_number = trim($this->id_number);
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user->id),
            ],
            'id_number' => [
                'required',
                'string',
                Rule::unique('users')->ignore($this->user->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:admin,user'],
        ]);



        $user = User::findOrFail($this->user->id);

        $user->name = $this->name;
        $user->email = $this->email;
        $user->id_number = $this->id_number;
        $user->phone_number = $this->phone_number;

        $user->save();

        // Sync role if changed
        if (!$user->hasRole($this->role)) {
            $user->syncRoles([$this->role]);
        }

        $this->dispatch('user-updated'); // optional event

        if ($user->wasChanged('slug'))
        {
            return redirect()->route('users.viewuser', [
                'county' => $this->county->slug,
                'user' => $user->slug,
            ]);
        }



        $this->user = $user;
    }

    public function togglePermission($permission)
    {
        if (in_array($permission, $this->userPermissions)) {
            $this->user->revokePermissionTo($permission);
            $this->userPermissions = array_diff($this->userPermissions, [$permission]);
            $this->dispatch('user-updated'); // optional event
        } else {
            $this->user->givePermissionTo($permission);
            $this->userPermissions[] = $permission;
            $this->dispatch('user-updated'); // optional event
        }
    }

    public function toggleConstituency($code)
    {
        if (in_array($code, $this->userConstituencies)) {
            $this->user->constituencies()->detach($code);
            $this->userConstituencies = array_diff($this->userConstituencies, [$code]);
            $this->dispatch('user-updated'); // optional event
        } else {
            $this->user->constituencies()->attach($code);
            $this->userConstituencies[] = $code;
            $this->dispatch('user-updated'); // optional event
        }
    }

    public function render()
    {
        $categorizedPermissions = [
            'Receipt' => ['create receipt', 'update receipt', 'delete receipt'],
            'Member' => ['create member', 'update member', 'delete member'],
            'Group' => ['create group', 'update group', 'delete group'],
            'User' => ['create user', 'update user', 'delete user'],
            'Loan Application' => ['create loan application', 'update loan application', 'delete loan application'],
            'Others' => ['download documents', 'create new phase'],
        ];



        //dd($activities);

        $availableConstituencies = auth()->user()->constituencies;

        return view('livewire.users.view-user-data', [
            'categorizedPermissions' => $categorizedPermissions,
            'availableConstituencies' => $availableConstituencies,
            'activities' => $this->activities,
        ]);
    }
}
