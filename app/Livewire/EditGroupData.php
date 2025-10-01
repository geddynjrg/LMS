<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\LoanPhase;
use App\Models\Wards;
use App\Models\Group;
use App\Models\GroupLoan;

class EditGroupData extends Component
{
    public $phases = [];
    public $phaseIds;
    public $phase;
    public $ph;
    public $applicationDate;
    public $amount_applied;
    public $groupLoan;
    public $wards = [];
    public $wardIds;

    public $code;
    public $identifier;
    public $amount_approved;
    public $loading = true;

    public $categories = array(
        "1" => "Youth",
        "2" => "Women",
        "3" => "PWD"
    );

    public $activities = array(
        "1" => "Trade",
        "2" => "Service",
        "3" => "Manufacturing",
        "4" => "Agriculture",
        "5" => "Other"
    );

    public $groupData = [
        'groupName' => '',
        'dateFormed' => NULL,
        'dateRegistered' => NULL,
        'registrationNumber' => '',
        'bankName' => NULL,
        'bankAccount' => NULL,
        'category' => '',
        'activity' => '',
        'description' => ''
    ];

    public $groupLocation = [
        'boxNumber' => NULL,
        'ward' => '',
        'sublocation' => NULL,
    ];


    #[On('edit-data-modal')]
    public function editData($groupLoan)
    {
        //dd($groupLoan['group']);
        $this->loading = true;
        $this->groupLoan = $groupLoan;

        $this->getLoanPhases();
        $this->getWards();
        $this->initialData();
        $this->loading = false;
    }

    private function getLoanPhases ()
    {
        $this->phases = LoanPhase::whereHas('constituency', function ($query) {
            $query->where('constituency_code', auth()->user()->currentConstituency->constituency_code);
        })
        ->get();
        $this->phaseIds = $this->phases->pluck('id')->toArray();
    }

    private function getWards ()
    {
        $this->wards = Wards::whereHas('constituency', function ($query) {
            $query->where('constituency_code', auth()->user()->currentConstituency->constituency_code);
        })
        ->orderBy('ward_name', 'desc')
        ->get();
        $this->wardIds = $this->wards->pluck('id')->toArray();
    }

    private function initialData()
    {
        $this->phase = (string)$this->groupLoan['loan_phase_id'];
        $this->applicationDate = $this->groupLoan['application_date'];
        $this->amount_applied = $this->groupLoan['amount_applied'];

        $this->groupData = [
            'groupName' => $this->groupLoan['group']['name'],
            'dateFormed' => $this->groupLoan['group']['date_formed'],
            'dateRegistered' => $this->groupLoan['group']['date_registered'],
            'registrationNumber' => $this->groupLoan['group']['reg_number'],
            'bankName' => $this->groupLoan['group']['bank'],
            'bankAccount' => $this->groupLoan['group']['account'],
            'category' => array_search($this->groupLoan['group']['category'], $this->categories),
            'activity' => array_search($this->groupLoan['group']['activity'], $this->activities),
            'description' => $this->groupLoan['group']['activity_desc'],
        ];

        $this->groupLocation = [
            'boxNumber' => $this->groupLoan['group']['box'],
            'ward' => $this->groupLoan['group']['ward']['id'],
            'sublocation' => $this->groupLoan['group']['location'],
        ];

        if ($this->groupLoan['loan_status'] == 'approved' || $this->groupLoan['loan_status'] == 'financed')
        {
            $this->code = $this->groupLoan['group']['code'];
            $this->identifier = $this->groupLoan['group']['identifier'];
            $this->amount_approved = $this->groupLoan['amount_approved'];
        }
    }

    public function submitData()
    {
        $category = array_keys($this->categories);
        $activity = array_keys($this->activities);
        try {
            $this->validate([
                'phase' => 'required|numeric|in:' . implode(',', $this->phaseIds),
                'applicationDate' => 'required|date',
                'groupData.groupName' => 'required|string',
                'groupData.dateFormed' => 'required|date',
                'groupData.dateRegistered' => 'required|date',
                'groupData.registrationNumber' => 'required',
                'groupData.bankName' => 'required|string',
                'groupData.bankAccount' => 'required|string|regex:/^[0-9]+$/',
                'groupData.category' => 'required|in:' . implode(',', $category),
                'groupData.activity' => 'required|in:' . implode(',', $activity),
                'groupData.description' => 'required|string|min:10',
                'groupLocation.boxNumber' => 'required|numeric',
                'groupLocation.ward' => 'required|numeric|in:' . implode(',', $this->wardIds),
                'groupLocation.sublocation' => 'required|string',
            ]);

            if ($this->groupLoan['loan_status'] !== 'financed')
            {
                $this->validate([
                    'amount_applied' => 'required|numeric',
                ]);
            }

            if ($this->groupData['registrationNumber'] != $this->groupLoan['group']['reg_number']) {

                $this->validate([
                    'groupData.registrationNumber' => 'unique:groups,reg_number',
                ]);
            }

            if ($this->groupLoan['loan_status'] == 'approved' || $this->groupLoan['loan_status'] == 'financed')
            {
                $this->validate([
                    'code' => 'required|string|regex:/^[0-9]+$/',
                    'identifier' => 'required|string',
                ]);

                if ($this->groupLoan['loan_status'] == 'approved')
                {
                    $this->validate([
                        'amount_approved' => 'required|numeric',
                    ]);
                }

                if ($this->groupLoan['group']['code'] != $this->code)
                {
                    $this->validate([
                        'code' => [
                            function($attribute, $value, $fail){
                                $existingCode = Group::whereHas('ward.constituency', function($query) {
                                    $query->where('constituency_code', auth()->user()->currentConstituency->constituency_code);
                                })->where('code', $this->code)->first();

                                if($existingCode) {
                                    $fail('The code has already been taken.');
                                }
                            }
                        ],
                    ]);
                }
            }

            $this->saveData();
        } catch (ValidationException $th) {
            dd('error');
        }
    }

    private function saveData()
    {
        $groupLoan = GroupLoan::find($this->groupLoan['id']);
        if($groupLoan){
            $group = Group::find($groupLoan->group_id);
            if($group){
                $groupLoan->loan_phase_id = (int)$this->phase;
                $groupLoan->application_date = $this->applicationDate;
                $groupLoan->amount_applied = $this->amount_applied;

                if ($this->groupLoan['loan_status'] == 'approved')$groupLoan->amount_approved = $this->amount_approved;
                if($this->groupLoan['loan_status'] == 'approved' || $this->groupLoan['loan_status'] == 'financed')
                {
                    $group->code = $this->code;
                    $group->identifier = $this->identifier;
                    $gfCode = '4-'.auth()->user()->county->county_code.'-'.auth()->user()->currentConstituency->constituency_code.'-411020'.$this->groupData['category'].'-'.$this->groupData['activity'].'0'.$this->groupData['category'].'-'.$this->code;
                    $group->gfs_code = $gfCode;
                }

                $group->name = ucwords($this->groupData['groupName']);
                $group->ward_id = $this->wards->where('id', $this->groupLocation['ward'])->first()->id;
                $group->date_formed = $this->groupData['dateFormed'];
                $group->date_registered = $this->groupData['dateRegistered'];
                $group->reg_number = $this->groupData['registrationNumber'];
                $group->bank = $this->groupData['bankName'];
                $group->account = $this->groupData['bankAccount'];
                $group->category = $this->categories[$this->groupData['category']];
                $group->activity = $this->activities[$this->groupData['activity']];
                $group->activity_desc = $this->groupData['description'];
                $group->location = $this->groupLocation['sublocation'];
                $group->box = $this->groupLocation['boxNumber'];


                if(auth()->user()->hasPermissionTo('update group') && $groupLoan->save() && $group->save())
                {
                    $this->dispatch('refreshData', message: 'success')->to(LoanAplicant::class);
                    $this->dispatch('refreshData', message: 'success')->to(GroupInfo::class);
                    $this->dispatch('isEdited', message: 'success');
                }else {
                    $this->dispatch('isEdited', message: 'error');
                }
            }
        }
    }
    public function render()
    {
        return view('livewire.loan.edit-group-data');
    }
}
