<?php

namespace App\Livewire\Users;

use Livewire\WithPagination;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use App\Services\LocationService;
use App\Services\UserService;
use App\Models\County;
use App\Models\User;
use App\Services\MailService;

class ViewUserData extends Component
{
    use WithPagination;

    protected LocationService $locationService;
    protected UserService $userService;
    protected MailService $mailService;

    // Use Bootstrap styling for pagination links
    protected $paginationTheme = 'bootstrap';

    public County $county;

    public User $user;

    public bool $isSuperAdmin = false;
    public bool $isConstituencyOfficer = false;
    public bool $isIntern = false;

    public $perPage = 10;
    public $searchActivity = '';

    #[Url(except: 'activity')]
    public string $activeTab = 'activity';

    public $state = [
        'email' => '',
        'password' => '',
        'name' => '',
        'idNumber' => '',
        'phoneNumber' => '',
        'isActive' => false,
        'county' => null,
        'role' => '',
        'userPermissions' => [],
        'userConstituencies' => [],
    ];

    public $selectedCounty = null;
    public $selectedConstituencies = [];
    public $selectedRole = '';

    public $countyOptions = [];
    public $constituencyOptions = [];

    public function boot(
        UserService $userService,
        LocationService $locationService,
        MailService $mailService)
    {
        $this->locationService = $locationService;
        $this->userService = $userService;
        $this->mailService = $mailService;
    }

    public function mount (County $county, User $user)
    {

        $this->county = $county;

        $this->user = $user->load([
                'roles',
                'permissions',
                'constituencies',
                'county'
            ]);

        $this->setAuthContext();

        $this->loadUserState();

        $this->loadLocations();
    }

    public function updatedSelectedCounty($value)
    {
        $this->reset('constituencyOptions', 'selectedConstituencies');

        if (!empty($value)) {
            $this->loadUserConstituencyOptions($value);
        }
    }

    private function setAuthContext()
    {
        $user = auth()->user();

        $this->isSuperAdmin = $user->hasRole('super-admin');
        $this->isConstituencyOfficer = $user->hasRole('constituency_officer');
        $this->isIntern = $user->hasRole('intern');
    }

    private function loadLocations()
    {
        $this->countyOptions = $this->locationService->getAllCounties()
                ->when($this->isConstituencyOfficer, function ($collection) {
                    return $collection->where('county_code', $this->county->county_code);
                })
                ->map(fn($c) => [
                        'id' => $c->county_code,
                        'name' => $c->county_name
                    ])
                ->values()
                ->toArray();

        if ($this->isConstituencyOfficer) {
            $this->state['county'] = $this->county->county_code;
            $this->state['role'] = 'intern';
        }

    }

    public function loadUserConstituencyOptions($countyCode = null)
    {
        if (!$countyCode) {
            $this->constituencyOptions = [];
            return;
        }


        $allConstituencies = $this->locationService->getAllConstituencies();

        $this->constituencyOptions = $allConstituencies
            ->where('county_code', $countyCode)
            ->when($this->isConstituencyOfficer, function ($collection) {
                $user = auth()->user();
                $assignedCodes = $user->constituencies->pluck('constituency_code')->toArray();
                return $collection->whereIn('constituency_code', $assignedCodes);
            })
            ->map(fn($c) => [
                'id' => $c->constituency_code,
                'name' => $c->constituency_name
            ])
            ->values()
            ->toArray();

    }

    private function loadUserState()
    {
        if (!$this->user) return;

        // Populate the state array so Entangle can find the keys
        $this->state = [
            'email' => $this->user->email,
            'name' => $this->user->name,
            'idNumber' => $this->user->id_number,
            'phoneNumber' => $this->user->phone_number,
            'name' => $this->user->name,
            'isActive' => (bool) $this->user->status,
            'county' => $this->user->county_code,
            'role' => $this->user->getRoleNames()->first(),
            'userPermissions' => $this->user->getAllPermissions()->pluck('name')->toArray(),
            'userConstituencies' => $this->user->constituencies->pluck('constituency_code')->toArray(),
            'password' => null,
        ];

        $this->selectedCounty = $this->user->county_code;
        $this->selectedConstituencies = $this->user->constituencies->pluck('constituency_code')->toArray();

        if (in_array($this->state['role'], ['constituency_officer', 'intern'])) {
            $this->loadUserConstituencyOptions($this->state['county']);
        }
    }

    private function getRoleOptions()
    {
        return Role::all()
            ->filter(function($role) {
                if ($this->isSuperAdmin) {
                    return true;
                }
                return $role->name === 'intern';
            })
            ->map(fn($role) => [
                'id' => $role->name,
                'name' => ucwords(str_replace(['-', '_'], ' ', $role->name)),
                'color' => $role->name === 'intern' ? '#6366f1' : '#64748b'
            ])
            ->values()
            ->toArray();
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
        if ($this->user->hasPermissionTo($permission)) {
            $this->user->revokePermissionTo($permission);
            $title = 'Permission Revoked 😊';
        } else {
            $this->user->givePermissionTo($permission);
            $title = 'Permission Granted 😊';
        }

        $this->user->unsetRelation('permissions');

        $this->dispatch('swal:toast',
            title: $title,
            message: "Permissions updated successfully.",
            type: 'success'
        );
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

    public function updateUserStatus() : void
    {

    }

    public function updatingSearchActivity()
    {
        $this->resetPage();
    }

    public function loadMore()
    {
        $this->perPage += 10;
    }

    public function getActivities()
    {
        return $this->userService->getUserActions(
            $this->user,
            $this->perPage,
            $this->searchActivity
        );
    }

    public function syncAllPermissions()
    {
        $this->userService->syncAllPermissions($this->user);
        $this->refreshPermissions();
        $this->dispatch('swal:toast',
            title: 'Permissions 😊',
            message: "All manageable permissions granted.",
            type: 'success'
        );
    }

    public function revokeAllPermissions()
    {
        $this->userService->revokeAllPermissions($this->user);
        $this->refreshPermissions();
        $this->dispatch('swal:toast',
            title: 'Permissions 😊',
            message: "All manageable permissions revoked.",
            type: 'warning'
        );
    }

    public function syncPermissionGroup($category)
    {
        $this->userService->syncPermissionGroup($this->user, $category);

        $this->user->unsetRelation('permissions');

        $this->refreshPermissions();
        $this->dispatch('swal:toast',
            title: 'Permissions 😊',
            message: "All manageable permissions granted.",
            type: 'success'
        );
    }

    private function refreshPermissions()
    {
        $this->user->load('permissions');
        $this->userPermissions = $this->user->getPermissionNames()->toArray();
    }

    public function updateUserJurisdiction()
    {
        if (auth()->user()->cannot('update user')) {
            return $this->dispatch('swal:toast',
                title: 'Access Denied 😕',
                message: 'You do not have permission to update users.',
                type: 'error'
            );
        }

        $this->validate([
            'state.role' => [
                'required',
                'exists:roles,name',
                Rule::when(auth()->user()->hasRole('constituency_officer'), [
                    'in:intern'
                ]),
            ],
            'selectedCounty' => [
                'bail',
                Rule::requiredIf(fn() => in_array($this->state['role'], ['constituency_officer', 'intern'])),
                'nullable',
                'exists:counties,county_code'
            ],
            'selectedConstituencies' => [
                'array',
                Rule::requiredIf(fn() => in_array($this->state['role'], ['constituency_officer', 'intern'])),
                'nullable',

                Rule::when(
                    in_array($this->state['role'], ['constituency_officer', 'intern']),
                    ['min:1']
                ),

                // 3. County Integrity Check
                function ($attribute, $value, $fail) {
                    if (!in_array($this->state['role'], ['constituency_officer', 'intern'])) {
                        return;
                    }

                    $user = auth()->user();

                    $authorizedCounty = $user->hasRole('super-admin')
                        ? $this->selectedCounty
                        : $user->county_code;

                    if ($authorizedCounty && !empty($value)) {
                        $hasInvalid = \App\Models\Constituency::whereIn('constituency_code', $value)
                            ->where('county_code', '!=', $authorizedCounty)
                            ->exists();

                        if ($hasInvalid) {
                            $fail('One or more selected constituencies do not belong to the authorized county.');
                        }
                    }
                },
            ],
        ], [
            'state.role.required' => 'Please select a role.',
            'state.role.exists'   => 'The selected role is invalid.',
            'state.role.in'       => 'As a Constituency Officer, you are only authorized to assign the Intern role.',
        ]);


        try {
            DB::transaction(function () {
                $user = $this->user;

                $user->update([
                    'county_code' => $this->selectedCounty,
                    'current_constituency_code' => $this->selectedConstituencies[0] ?? null,
                ]);

                $user->syncRoles([$this->state['role']]);

                $constituencyData = collect($this->selectedConstituencies)->map(function ($code) use ($user) {
                    return [
                        'user_id' => $user->id,
                        'constituency_code' => $code,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                DB::table('user_constituencies')->where('user_id', $user->id)->delete();
                DB::table('user_constituencies')->insert($constituencyData);

            });

            $this->dispatch('swal:toast',
                title: 'Success! 🎉',
                message: 'User jurisdiction and roles updated successfully.',
                type: 'success'
            );
        } catch (\Throwable $th) {
            dd($th);
            $this->dispatch('swal:toast',
                title: 'Update Failed 😕',
                message: 'An unexpected error occurred. Please try again or contact support.',
                type: 'error'
            );
        }

    }

    public function updateUserInfo()
    {
        if (auth()->user()->cannot('update user')) {
            return $this->dispatch('swal:toast',
                title: 'Access Denied 😕',
                message: 'You do not have permission to update users.',
                type: 'error'
            );
        }

        $this->validate([
            'state.name' => 'required|string|max:255',

            'state.email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->user->id),
            ],

            'state.idNumber' => [
                'required',
                Rule::unique('users', 'id_number')->ignore($this->user->id),
            ],

            'state.phoneNumber' => [
                'required',
                'regex:/^254[17][0-9]{8}$/'
            ],
        ], [
            'state.phoneNumber.regex' => 'The phone number must be in the format 2547XXXXXXXX or 2541XXXXXXXX.',
            'state.email.unique' => 'This email is already assigned to another system user.',
            'state.idNumber.unique' => 'This ID Number is already assigned to another system user.',
        ]);

        try {
            DB::transaction(function () {
                $updateData = [
                    'name' => $this->state['name'],
                    'email' => $this->state['email'],
                    'id_number' => $this->state['idNumber'],
                    'phone_number' => $this->state['phoneNumber'],
                ];

                if (!empty($this->state['password'])) {
                    $updateData['password'] = bcrypt($this->state['password']);

                    $this->user->forceFill([
                        'remember_token' => Str::random(60),
                    ]);
                }

                $this->user->update($updateData);
            });

            if (!empty($this->state['password'])) {
                $this->mailService->sendPasswordResetNotification($this->user, $this->state['password']);
            }

            $this->state['password'] = null;

            $this->dispatch('swal:toast',
                title: 'Update Successful 😊',
                message: 'User account has been updated.',
                type: 'success'
            );

            return $this->redirect(route('users.viewuser', [
                'county' => $this->county,
                'user' => $this->user->slug,
                'activeTab' => 'update'
            ]), navigate: true);

        } catch (\Throwable $th) {
            dd($th);
            $this->dispatch('swal:toast',
                title: 'User Update Failed 😕',
                message: 'An unexpected error occurred. Please try again or contact support.',
                type: 'error'
            );
            return;
        }



        // update user data, if password generated if state.password is not empty update the user password, if the user was logged in end that users session
    }

    public function generatePassword()
    {
        $this->state['password'] = Str::random(8);
    }

    public function render()
    {
        return view('livewire.users.view-user-data', [
            'activities' => $this->getActivities(),
            'categorizedPermissions' => $this->userService->getManageablePermissions($this->user),
            'roleOptions' => $this->getRoleOptions(),
        ]);


        // $categorizedPermissions = [
        //     'Receipt' => ['create receipt', 'update receipt', 'delete receipt'],
        //     'Member' => ['create member', 'update member', 'delete member'],
        //     'Group' => ['create group', 'update group', 'delete group'],
        //     'User' => ['create user', 'update user', 'delete user'],
        //     'Loan Application' => ['create loan application', 'update loan application', 'delete loan application'],
        //     'Others' => ['download documents', 'create new phase'],
        // ];



        // //dd($activities);

        // $availableConstituencies = auth()->user()->constituencies;

        // return view('livewire.users.view-user-data', [
        //     'categorizedPermissions' => $categorizedPermissions,
        //     'availableConstituencies' => $availableConstituencies,
        //     'activities' => $this->getActivities(),
        // ]);
    }
}
