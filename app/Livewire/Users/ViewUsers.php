<?php

namespace App\Livewire\Users;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Services\LocationService;
use App\Services\MailService;
use App\Models\County;
use App\Models\Constituency;
use App\Models\User;
use App\Support\SystemRoles;
use App\Rules\SingletonRoleAvailable;

class ViewUsers extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected LocationService $locationService;
    protected MailService $mailService;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $countyFilter = '';
    public $constituencyFilter = '';
    public $perPage = 10;

    public $counties = [];
    public $constituencies = [];

    public $countyOptions = [];
    public $constituencyOptions = [];


    public bool $isSuperAdmin = false;
    public bool $isConstituencyOfficer = false;

    public County $county;

    // user data
    public $password = '';
    public $selectedCounty;
    public $selectedRole = '';
    public $username;
    public $idNumber;
    public $email;
    public $phoneNumber;
    public $selectedConstituencies = [];
    public $userConstituencyOptions = [];

    public function rules()
    {
        return [
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'idNumber' => 'required|unique:users,id_number',
            'phoneNumber' => 'required|regex:/^254[17][0-9]{8}$/',
            'selectedRole' => ['required', 'exists:roles,name', new SingletonRoleAvailable()],

            'selectedCounty' => [
                'bail',
                Rule::requiredIf(fn() => in_array($this->selectedRole, ['constituency_officer', 'intern'])),
                'nullable',
                'exists:counties,county_code'
            ],

            'selectedConstituencies' => [
                Rule::requiredIf(fn() => in_array($this->selectedRole, ['constituency_officer', 'intern'])),
                'nullable',
                'array',

                in_array($this->selectedRole, ['constituency_officer', 'intern']) ? 'min:1' : '',

                function ($attribute, $value, $fail) {
                    if (in_array($this->selectedRole, ['hq_officer', 'super-admin', 'admin', 'accountant', 'head_of_secretariat'])) {
                        return;
                    }

                    /** @var \App\Models\User $user */
                    $user = Auth::user();

                    // Determine the county context
                    $countyToCheck = $user->hasRole('super-admin')
                        ? $this->selectedCounty
                        : $user->county_code;

                    // 4. Validate that selected constituencies belong to the designated county
                    if (in_array($this->selectedRole, ['constituency_officer', 'intern'])) {
                        if ($countyToCheck && !empty($value)) {
                            $invalidCount = \App\Models\Constituency::whereIn('constituency_code', $value)
                                ->where('county_code', '!=', $countyToCheck)
                                ->count();

                            if ($invalidCount > 0) {
                                $fail('One or more selected constituencies do not belong to the authorized county.');
                            }
                        }
                    }
                },
            ],
        ];
    }

    public function boot(LocationService $locationService, MailService $mailService)
    {
        $this->locationService = $locationService;
        $this->mailService = $mailService;
    }

    public function mount (County $county)
    {
        $this->county = $county;
        $this->setAuthContext();
        $this->loadFilterOptions();

        if (!$this->isSuperAdmin) {
            $this->roleFilter = 'intern';
        }
    }

    public function updatingSearch() { $this->resetPage(); }

    public function updatingRoleFilter() { $this->resetPage(); }

    public function updatingStatusFilter() { $this->resetPage(); }

    public function updatedPerPage ($value) {if ($value === null) $this->perPage = 10;}

    public function updatedCountyFilter($value)
    {
        $this->constituencyFilter = '';

        $this->reset('constituencyOptions');

        if (!empty($value)) {
            $this->loadConstituencies($value);
        }

        $this->resetPage();
    }

    public function updatedSelectedCounty($value)
    {
        $this->reset('userConstituencyOptions', 'selectedConstituencies');

        if (!empty($value)) {
            $this->loadUserConstituencyOptions($value);
        }
    }

    public function updatedPhoneNumber($value)
    {
        if (str_starts_with($value, '0')) {
            $this->phoneNumber = '254' . substr($value, 1);
        }

        elseif (str_starts_with($value, '7') || str_starts_with($value, '1')) {
            if (!str_starts_with($value, '254')) {
                $this->phoneNumber = '254' . $value;
            }
        }
    }

    public function updatedConstituencyFilter() { $this->resetPage(); }

    private function loadFilterOptions()
    {
        // Load counties for super admin
        if ($this->isSuperAdmin) {
            $this->countyOptions = $this->locationService->getAllCounties()
                ->map(fn($c) => [
                    'id' => $c->county_code,
                    'name' => $c->county_name
                ])
                ->toArray();
        }

        if ($this->isConstituencyOfficer) {
            $userCounty = $this->locationService->getAllCounties()
                ->where('county_code', auth()->user()->county_code)
                ->first();



            if ($userCounty) {
                $this->selectedCounty = $userCounty->county_code;
                $this->selectedRole = 'intern';

                $this->countyOptions = [[
                    'id' => $userCounty->county_code,
                    'name' => $userCounty->county_name
                ]];

                $this->loadUserConstituencyOptions($userCounty->county_code);
            }
        }
    }

    public function refreshData()
    {

    }

    public function resetFilters()
    {
        $this->reset(['search', 'roleFilter', 'statusFilter', 'countyFilter', 'constituencyFilter']);
        $this->resetPage(); // Essential to go back to page 1
    }

    private function resetFormFields()
    {
        if($this->isConstituencyOfficer){
            $this->reset(['selectedConstituencies', 'username', 'email', 'idNumber', 'phoneNumber']);
        } else {
            $this->reset(['selectedRole', 'selectedCounty', 'selectedConstituencies', 'username', 'email', 'idNumber', 'phoneNumber']);
        }
    }

    public function clearFilter($filterName)
    {
        $this->reset($filterName);
    }

    private function setAuthContext()
    {
        $user = Auth::user();

        $this->isSuperAdmin = $user->hasRole('super-admin');
        $this->isConstituencyOfficer = $user->hasRole('constituency_officer');
    }

    protected function getBaseQuery()
    {
        $auth = auth()->user();

        $query = User::with(['roles', 'permissions', 'constituencies', 'county'])
            ->where('id', '!=', $auth->id);

        if ($this->isSuperAdmin) {
            return $query;
        }

        if ($this->isConstituencyOfficer) {
            $assignedConstCodes = $auth->constituencies->pluck('constituency_code');

            return $query->whereHas('constituencies', function($q) use ($assignedConstCodes) {
                $q->whereIn('constituencies.constituency_code', $assignedConstCodes);
            })->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['admin', 'super-admin', 'hq_officer', 'constituency_officer']));
        }

        return $query->whereRaw('1 = 0');
    }

    private function getUsers()
    {
        $users = $this->getBaseQuery()
            // 1. Search Filter (Name/Email)
            ->when($this->search, function ($q) {
                $q->where(fn($inner) => $inner->where('name', 'like', '%'.$this->search.'%')
                                            ->orWhere('email', 'like', '%'.$this->search.'%'));
            })
            // 2. Role Filter (Spatie)
            ->when($this->roleFilter, fn($q) => $q->role($this->roleFilter))

            // 3. Status Filter (Active/Inactive)
            ->when($this->statusFilter !== '' && $this->statusFilter !== null, function ($q) {
                return $q->where('status', (bool)$this->statusFilter);
            })
            // 4. Geographical Filters (Super Admin only)
            ->when($this->isSuperAdmin && $this->countyFilter, function ($q) {
                $q->where('county_code', $this->countyFilter);
            })
            ->when($this->isSuperAdmin && $this->constituencyFilter, function ($q) {
                $q->whereHas('constituencies', fn($c) => $c->where('constituencies.constituency_code', $this->constituencyFilter));
            })
            ->latest();

        return $users;
    }

    #[Computed]
    public function stats()
    {
        // We use the base query (which already handles Super Admin vs Officer logic)
        // but we remove pagination to get the full count of the filtered set.
        $query = $this->getBaseQuery()
            ->when($this->search, function ($q) {
                $q->where(fn($inner) => $inner->where('name', 'like', '%'.$this->search.'%')
                                            ->orWhere('email', 'like', '%'.$this->search.'%'));
            })
            ->when($this->roleFilter, fn($q) => $q->role($this->roleFilter))
            ->when($this->isSuperAdmin && $this->countyFilter, fn($q) => $q->where('county_code', $this->countyFilter));

        // Get the raw counts in one trip to the DB
        $data = $query->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN email_verified_at IS NULL THEN 1 ELSE 0 END) as unverified,

            /* Status Aggregations */
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as deactivated,

            /* Role Aggregations */
            SUM(CASE WHEN id IN (SELECT model_id FROM model_has_roles JOIN roles ON roles.id = role_id WHERE roles.name = 'hq_officer') THEN 1 ELSE 0 END) as hq,
            SUM(CASE WHEN id IN (SELECT model_id FROM model_has_roles JOIN roles ON roles.id = role_id WHERE roles.name = 'constituency_officer') THEN 1 ELSE 0 END) as field,
            SUM(CASE WHEN id IN (SELECT model_id FROM model_has_roles JOIN roles ON roles.id = role_id WHERE roles.name = 'super-admin') THEN 1 ELSE 0 END) as admins,
            SUM(CASE WHEN id IN (SELECT model_id FROM model_has_roles JOIN roles ON roles.id = role_id WHERE roles.name = 'intern') THEN 1 ELSE 0 END) as inters
        ")->first();

        return [
            'total'             => $data->total ?? 0,
            'unverified'        => $data->unverified ?? 0,
            'hqUsers'           => $data->hq ?? 0,
            'constituencyUsers' => $data->field ?? 0,
            'admins'            => $data->admins ?? 0,
            'interns'           => $data->inters ?? 0,
            'active'            => $data->active ?? 0,
            'deactivated'       => $data->deactivated ?? 0,
        ];
    }

    public function toggleStatus($userId)
    {
        $user = User::findOrFail($userId);

        // Prevent self-deactivation
        if ($userId === auth()->id()) {
            $this->dispatch('swal:toast', title: 'Action Denied', message: 'You cannot lock your own account.', type: 'error');
            return;
        }

        $user->status = !$user->status;
        $user->save();

        $this->dispatch('swal:toast',
            title: 'User Update 😊',
            message: "Account for {$user->name} is now " . ($user->status ? 'Active' : 'Locked'),
            type: 'success'
        );
    }

    private function getRoleOptions()
    {
        return Role::all()
            ->filter(function ($role) {
                if (! $this->isSuperAdmin) {
                    return $role->name === 'intern';
                }

                if (SystemRoles::singletonRoleTaken($role->name)) {
                    return false;
                }

                return true;
            })
            ->map(fn($role) => [
                'id' => $role->name,
                'name' => ucwords(str_replace(['-', '_'], ' ', $role->name)),
                'color' => $role->name === 'intern' ? '#6366f1' : '#64748b'
            ])
            ->values()
            ->toArray();
    }

    private function getStatusOptions()
    {
        return [
            ['id' => '1', 'name' => 'Active Accounts', 'color' => '#10b981'],
            ['id' => '0', 'name' => 'Deactivated', 'color' => '#f59e0b'],
        ];
    }

    public function getPerPageOptionsProperty()
    {
        return [
            ['id' => 10, 'name' => '10 per page'],
            ['id' => 15, 'name' => '15 per page'],
            ['id' => 25, 'name' => '25 per page'],
            ['id' => 50, 'name' => '50 per page'],
            ['id' => 100, 'name' => '100 per page'],
        ];
    }

    /**
     * Load constituencies based on county
     */
    public function loadConstituencies($countyCode = null)
    {
        if (!$countyCode) {
            $this->constituencyOptions = [];
            return;
        }

        $this->constituencyOptions = $this->locationService->getAllConstituencies()
            ->where('county_code', $countyCode)
            ->map(fn($c) => [
                'id' => $c->constituency_code,
                'name' => $c->constituency_name
            ])
            ->values()
            ->toArray();


    }

    public function loadUserConstituencyOptions($countyCode = null)
    {
        if (!$countyCode) {
            $this->constituencyOptions = [];
            return;
        }

        $allConstituencies = $this->locationService->getAllConstituencies();

        $assignedCodes = [];
        if ($this->isConstituencyOfficer){
            $user = auth()->user();
            $assignedCodes = $user->constituencies->pluck('constituency_code')->toArray();
        }


        $this->userConstituencyOptions = $this->locationService->getAllConstituencies()
            ->where('county_code', $countyCode)
            ->when($this->isConstituencyOfficer, function ($collection) use ($assignedCodes) {
                return $collection->whereIn('constituency_code', $assignedCodes);
            })
            ->map(fn($c) => [
                'id' => $c->constituency_code,
                'name' => $c->constituency_name
            ])
            ->values()
            ->toArray();

    }

    public function exportUsers()
    {
        $query = $this->getUsers();

        // return Excel::download(
        //     new ApplicationsExport($query, $this->constituency),
        //     'loan-applications-' . now()->format('Y-m-d-His') . '.xlsx'
        // );
    }

    public function createUser()
    {
        $this->resetErrorBag();
        // $this->resetFormFields();

        if (auth()->user()->cannot('create user')) {
            return $this->dispatch('swal:toast',
                title: 'Access Denied 😕',
                message: 'You do not have permission to register new users.',
                type: 'error'
            );
        }

        $countyCode = auth()->user()->county_code;

        if (!$countyCode) {
            return $this->dispatch('swal:toast',
                title: 'Unauthorised 😕',
                message: 'Your account is not assigned to a specific county.',
                type: 'warning'
            );
        }

        $this->generatePassword();

        $this->dispatch('open-modal', name: 'create-new-user-modal');
    }

    public function generatePassword()
    {
        $this->password = Str::random(8);
    }

    public function storeUser()
    {
        if (auth()->user()->cannot('create user')) {
            return $this->dispatch('swal:toast',
                title: 'Anauthorised 😕',
                message: 'You do not have permission to register new users.',
                type: 'error'
            );
        }

        $this->validate();

        try {
            $newUser = DB::transaction(function () {
                $countyCode = in_array($this->selectedRole, ['constituency_officer', 'intern'])
                    ? $this->selectedCounty
                    : config('app.default_county_code', '047');

                // Create the user
                $user = User::create([
                    'name' => $this->username,
                    'email' => $this->email,
                    'id_number' => $this->idNumber,
                    'phone_number' => $this->phoneNumber,
                    'password' => bcrypt($this->password),
                    'county_code' => $countyCode,
                    'status' => true,
                ]);

                $user->assignRole($this->selectedRole);

                if (in_array($this->selectedRole, ['constituency_officer', 'intern']) && !empty($this->selectedConstituencies)) {
                    $user->constituencies()->sync($this->selectedConstituencies);
                }

                activity()
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'role' => $this->selectedRole,
                        'county' => $countyCode,
                        'constituencies' => $this->selectedConstituencies,
                    ])
                    ->log('created user');

                return $user;
            });

            if (!$newUser) {
                throw new \Exception('User creation failed');
            }

            $newUser->refresh();

            $this->mailService->sendWelcomeEmail($newUser, $this->password);

            $this->dispatch('close-modal', name: 'create-new-user-modal');

            $this->resetFormFields();

            $this->dispatch('swal:toast',
                title: 'User Creation Successful 😊',
                message: "User {$newUser->name} Successfully Created.",
                type: 'success'
            );

        } catch (\Throwable $th) {
            dd($th->getMessage());
            $this->dispatch('swal:toast',
                title: 'User Creation Failed 😕',
                message: 'An unexpected error occurred. Please try again or contact support.',
                type: 'error'
            );
            return;
        }

    }

    public function render()
    {
        // dd($this->getCountyOptions());
        return view('livewire.users.view-users', [
            'users' => $this->getUsers()->paginate($this->perPage),
            'roleOptions' => $this->getRoleOptions(),
            'statusOptions' => $this->getStatusOptions(),
            'countyOptions' => $this->countyOptions,
        ]);
    }
}
