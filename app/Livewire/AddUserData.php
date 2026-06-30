<?php

namespace App\Livewire;

use App\Models\User;
use App\Support\SystemRoles;
use App\Rules\SingletonRoleAvailable;
use Spatie\Permission\Models\Role;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AddUserData extends Component
{
    public $name, $email,  $password, $phone_number, $id_number, $selected_role, $constituency_code;

    public $county;
    public $all_counties;
    public $selected_county;
    public $selected_constituencies = [];
    public $available_constituencies = [];

    public $isOpen = false;

    public User $user;

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'id_number' => 'required|unique:users,id_number',
            'phone_number' => 'required|regex:/^254[17][0-9]{8}$/',
            'selected_role' => ['required', 'exists:roles,name', new SingletonRoleAvailable()],

            'selected_county' => [
                // Only require county if the user is a constituency_officer
                Rule::requiredIf($this->selected_role === 'constituency_officer'),
                'nullable',
                'exists:counties,county_code'
            ],

            'selected_constituencies' => [
                // Only require constituencies if the user is a constituency_officer
                Rule::requiredIf($this->selected_role === 'constituency_officer'),
                'nullable',
                'array',
                // 'min:1' is the culprit; it should only apply if the role is constituency_officer
                $this->selected_role === 'constituency_officer' ? 'min:1' : '',

                function ($attribute, $value, $fail) {
                    // Skip validation logic entirely if it's an HQ user
                    if ($this->selected_role === 'hq_officer' || $this->selected_role === 'super-admin' || $this->selected_role === 'accountant' || $this->selected_role === 'head_of_secretariat') {
                        return;
                    }

                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    $countyToCheck = $user->hasRole('super-admin')
                        ? $this->selected_county
                        : $user->county_code;

                    if ($this->selected_role === 'constituency_officer') {
                        if ($countyToCheck && !empty($value)) {
                            $invalidCount = \App\Models\Constituency::whereIn('constituency_code', $value)
                                ->where('county_code', '!=', $countyToCheck)
                                ->count();

                            if ($invalidCount > 0) {
                                $fail('One or more selected constituencies do not belong to the correct county.');
                            }
                        }
                    }
                },
            ],
        ];
    }

    #[On('open-add-user-modal')]
    public function initialize($county_code = null)
    {
        $this->all_counties = cache()->rememberForever('global_counties_with_constituencies', function () {
            return \App\Models\County::with('constituencies')->get();
        });

        $this->generatePassword();

        if ($county_code) {

            $this->selected_county = $county_code;

            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($user->hasRole('constituency_officer')) {
                $this->updatedSelectedCounty($county_code);
            }
        }
        $this->isOpen = true;
    }

    public function updatedSelectedCounty($value)
    {
        if (empty($value)) {
            $this->available_constituencies = collect();
            return;
        }

        $allCounties = cache()->rememberForever('global_counties_with_constituencies', function () {
            return \App\Models\County::with('constituencies')->get();
        });

        $selectedCountyObj = $allCounties->where('county_code', $value)->first();

        $this->available_constituencies = optional($selectedCountyObj)->constituencies ?? collect();

        $this->selected_constituencies = [];
    }

    public function updatedPhoneNumber($value)
    {
        // If user typed 07..., remove the leading 0 and prepend 254
        if (str_starts_with($value, '0')) {
            $this->phone_number = '254' . substr($value, 1);
        }
        // If they just typed 7..., prepend 254
        elseif (str_starts_with($value, '7') || str_starts_with($value, '1')) {
            if (!str_starts_with($value, '254')) {
                $this->phone_number = '254' . $value;
            }
        }
    }

    public function store()
    {
        $this->validate();
        try {
            $finalCountyCode = ($this->selected_role === 'constituency_officer')
                ? $this->selected_county
                : '047';

            DB::transaction(function () use ($finalCountyCode){
                $initialConstituencyCode = in_array($this->selected_role, ['constituency_officer', 'intern'], true)
                    ? ($this->selected_constituencies[0] ?? null)
                    : null;

                $user = \App\Models\User::create([
                    'name'         => $this->name,
                    'email'        => $this->email,
                    'id_number'    => $this->id_number,
                    'phone_number' => $this->phone_number,
                    'password'     => bcrypt($this->password),
                    'county_code'  => $finalCountyCode,
                    'current_constituency_code' => $initialConstituencyCode,
                    'status'       => 1,
                ]);

                $user->assignRole($this->selected_role);

                $user->constituencies()->sync($this->selected_constituencies);

                $this->dispatch('swal:modal', [
                    'type'               => 'success',
                    'title'              => 'Success!',
                    'text'               => 'New User created successfully. PASSWORD: '.$this->password,
                    'timer'              => 5000,
                    'showConfirmButton'  => false,
                ]);

                $this->reset(['name', 'email', 'id_number', 'phone_number', 'selected_role', 'selected_county', 'selected_constituencies']);
                $this->generatePassword();
                $this->isOpen = false;
            });
        } catch (\Throwable $th) {
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => "Invalid Action",
                'text'  => $th->getMessage(),
            ]);
            return;
        }

    }

    public function generatePassword()
    {
        // Generates a readable but secure 10-character string
        $this->password = Str::random(8);
    }

    public function render()
    {
        $roleQuery = \Spatie\Permission\Models\Role::query();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->hasRole('constituency_officer')) {
            $roleQuery->where('name', 'intern');

            $available_constituencies = auth()->user()->constituencies;
        } else {
            $roleQuery->where('name', '!=', 'super-admin')->where('name', '!=', 'intern');

            $roles = $roleQuery->get()->filter(function ($role) {
                return ! SystemRoles::singletonRoleTaken($role->name);
            });

            $available_constituencies = $this->available_constituencies;

            return view('livewire.users.add-user-data', [
                'roles' => $roles,
                'available_constituencies' => $available_constituencies
            ]);
        }

        return view('livewire.users.add-user-data', [
            'roles' => $roleQuery->get(),
            'available_constituencies' => $available_constituencies
        ]);
    }


}
