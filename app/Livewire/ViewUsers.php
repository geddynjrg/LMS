<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\County;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Livewire\WithPagination;

class ViewUsers extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    protected $paginationTheme = 'bootstrap';


    public County $county;

    public function mount (County $county)
    {
        $this->county = $county;
    }

    public function toggleStatus($userId)
    {
        $user = User::findOrFail($userId);
        $user->status = !$user->status;
        $user->save();

        $this->dispatch('swal:success', message: 'User status updated successfully.');
    }

    protected function getBaseQuery()
    {
        $auth = auth()->user();

        // Start with basic eager loading and exclude self
        $query = User::with(['roles', 'constituencies', 'county'])
            ->where('id', '!=', $auth->id);

        if ($auth->hasRole('super-admin')) {
            // Super-admins see all users in their county context
            // $query->where('county_code', $auth->county_code);
        }
        elseif ($auth->hasRole('constituency_officer')) {
            $constituencyCodes = $auth->constituencies->pluck('constituency_code')->toArray();

            $query->where('county_code', $auth->county_code)
                ->whereHas('constituencies', function ($q) use ($constituencyCodes) {
                    $q->whereIn('user_constituencies.constituency_code', $constituencyCodes);
                })
                ->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'super-admin');
                });
        } else {
            // Interns see no one else
            $query->whereRaw('1 = 0');
        }

        // B. Apply Search Logic
        $query->when($this->search, function($q) {
            $q->where(function($sub) {
                $sub->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('id_number', 'like', '%' . $this->search . '%')
                    ->orWhere('phone_number', 'like', '%' . $this->search . '%');
            });
        });

        // C. Apply Dropdown Filters
        $query->when($this->roleFilter, function($q) {
            $q->role($this->roleFilter);
        })->when($this->statusFilter !== '', function($q) {
            $q->where('status', $this->statusFilter);
        });

        return $query;
    }

    public function resetFilters()
    {
        $this->reset(['search', 'roleFilter', 'statusFilter']);
        $this->resetPage(); // Essential to go back to page 1
    }

    private function getUsers()
    {
        $this->users  = $this->getBaseQuery()->latest();
    }

    public function render()
    {
        $query = $this->getBaseQuery();

        $statsData = (clone $query)
            ->selectRaw("
                count(*) as total,
                count(case when exists (
                    select 1 from model_has_roles
                    join roles on roles.id = model_has_roles.role_id
                    where model_has_roles.model_id = users.id and roles.name = 'hq_officer'
                ) then 1 end) as hq,
                count(case when exists (
                    select 1 from model_has_roles
                    join roles on roles.id = model_has_roles.role_id
                    where model_has_roles.model_id = users.id and roles.name = 'constituency_officer'
                ) then 1 end) as field,
                count(case when exists (
                    select 1 from model_has_roles
                    join roles on roles.id = model_has_roles.role_id
                    where model_has_roles.model_id = users.id and roles.name in ('super-admin', 'admin')
                ) then 1 end) as admins
            ")
            ->first();

        return view('livewire.users.view-users', [
            'users' => (clone $query)->latest()->paginate(10),
            'stats' => [
                'total'  => $statsData->total,
                'hq'     => $statsData->hq,
                'field'  => $statsData->field,
                'admins' => $statsData->admins,
                'other'  => $statsData->total - ($statsData->hq + $statsData->field + $statsData->admins)
            ]
        ]);
    }
}
