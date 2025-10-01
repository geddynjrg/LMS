<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\County;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class ViewUsers extends Component
{
    public County $county;
    public $users;

    public function mount (County $county)
    {
        $this->county = $county;

        $this->getUsers();
    }

    private function getUsers()
    {
        $user = Auth::user();

        if ($user->hasRole('super-admin')) {
            // Super-admin: Get all users in the same county (excluding self)
            $this->users = User::with(['roles', 'permissions'])
                ->where('id', '!=', $user->id)
                ->where('county_code', $this->county->county_code)
                ->get();
        } elseif ($user->hasRole('admin')) {
            // Admin: Get users in same county AND same constituency (from pivot)
            $constituencyCodes = $user->constituencies->pluck('constituency_code')->toArray();

            /*$this->users = User::where('id', '!=', auth()->id())
                ->where('county_code', $this->county->county_code)
                ->whereHas('constituencies', function ($query) use ($constituencyCodes){
                    $query->whereIn('user_constituencies.constituency_code', $constituencyCodes);
                })
                ->get();*/
            $this->users = User::where('id', '!=', auth()->id())
                ->where('county_code', $this->county->county_code)
                ->whereHas('constituencies', function ($query) use ($constituencyCodes) {
                    $query->whereIn('user_constituencies.constituency_code', $constituencyCodes);
                })
                ->whereDoesntHave('roles', function ($query) {
                    $query->where('name', 'super-admin'); // must match exact role name in DB
                })
                ->with(['roles', 'permissions'])
                ->get();


        } else {
            // Regular users: See no one or maybe just themselves (optional)
            $this->users = collect(); // Empty collection
        }
    }

    public function render()
    {
        return view('livewire.users.view-users');
    }
}
