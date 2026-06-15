<?php

namespace App\Livewire;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;


use Livewire\Component;
use App\Models\LoanPhase;
use App\Models\Group;
use App\Models\Wards;
use App\Models\Member;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use App\Models\Constituency;
use Illuminate\Database\Eloquent\Collection;

class Application extends Component
{

    public $group;
    public $phase;
    public $applicationDate;
    public $level;
    public $amount;
    public $searchGroup;
    public $groupLoan;

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

    public $groupMember = [
        'name' => '',
        'role' => '',
        'phonenumber' => '',
        'gender' => '',
        'disabled' => false,
        'member_Id' => '',
    ];

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

    public $roles = ['Chairperson','Secretary','Treasurer','Member'];
    public $genders = ['Male', 'Female'];
    public $error_id;

    public Collection $wards;
    public $wardIds;
    public $phases = [];
    public $phaseIds;
    public $ph;
    public Collection $groups;
    public Collection $members;
    public $membersArray = [];
    public $member;
    public $memberEdit;
    public $memberEditKey;
    public $isMemberEdit = false;
    public $levels = ['New', 'Refinancing'];
    public $isRefinancing = false;
    public $na;
    public $step;
    public $icon;
    public $constituency;
    public $dataValidations;


    public function mount(Constituency $constituency)
    {
        $this->step = 'phaseStep';
        $constituency->load([
            'wards',
            'phases',
        ]);
        $this->constituency = $constituency;
        $this->icon = [
            'phase' => 'fa-info',
            'data' => 'fa-info',
            'location' => 'fa-info',
            'membership' => 'fa-info',
            'summary' => 'fa-info',
        ];

        $this->dataValidations = [
            'phaseStep' => false,
            'dataStep' => false,
            'locationStep' => false,
        ];

    }

    private function getLoanPhases ()
    {
        $this->phases = $this->constituency->phases;
        $this->phaseIds = $this->constituency->phases->pluck('id')->toArray();
    }

    private function getWards ()
    {
        $this->wards = $this->constituency->wards;
        $this->wardIds = $this->constituency->wards->pluck('id')->toArray();
    }

    public function submitLoanPhase()
    {
        $this->dataValidations['phaseStep'] = false;
        $this->icon['phase'] = 'fa-exclamation';

        try {
            $this->validate([
                'phase' => 'required|numeric|in:' . implode(',', $this->phaseIds),
                'level' => 'required|in:' . implode(',', $this->levels),
                'applicationDate' => 'required|date',
                'amount' => 'required|numeric',
            ]);
            if($this->isRefinancing)
            {
                $this->validate([
                    'searchGroup' => 'required',
                ]);

                if ($this->group == null && !($this->group instanceof Group) ){
                    $this->step = 'phaseStep';
                    $this->na = 'Select Group From List';
                }else {
                    $this->dataValidations['phaseStep'] = true;
                    $this->icon['phase'] = 'fa-check';
                    $this->step = 'dataStep';
                    $this->ph = $this->constituency->phases->where('id', $this->phase)->first();
                }
            } else {
                $this->dataValidations['phaseStep'] = true;
                $this->icon['phase'] = 'fa-check';
                $this->step = 'dataStep';
                $this->ph = $this->constituency->phases->where('id', $this->phase)->first();
            }

        } catch (ValidationException $e) {
            $this->step = 'phaseStep';
            $errors = $e->validator->errors()->messages();
            foreach ($errors as $field => $message) {
                $this->addError($field, $message[0]); // Add the first error message for each field
            }
            return;
        }


    }

    public function updatedLevel($value)
    {
        if ($value !== null && $value == 'Refinancing'){
            $this->isRefinancing = true;
        }else {
            $this->isRefinancing = false;
            $this->groups = collect();
            $this->searchGroup = '';
            $this->emptyGroupData();
        }
    }

    public function updatedSearchGroup($value)
    {
        $this->emptyGroupData();

        if (empty($value)){
            $this->groups = collect();

           }else {
            $code = $this->constituency->constituency_code;
            $this->groups = Group::whereHas('ward', function ($query) use ($code)
            {
                $query->where('constituency_code', $code);
            })->whereHas('groupLoans', function ($query) {
                $query->where('loan_status', 'financed');
            })
            ->where('name', 'like', '%' . $value . '%')
            ->with(['ward', 'groupLoans.groupMembers.member'])
            ->limit(5)
            ->get();
           }
           if (count($this->groups) < 1) {
                $this->na = 'Group Not Found';
           }else {
                $this->na = '';
           }
    }

    private function emptyGroupData()
    {
        $this->group = null;
        $this->groupData = [
            'groupName' => '',
            'dateFormed' => NULL,
            'dateRegistered' => NULL,
            'registrationNumber' => '',
            'bankName' => NULL,
            'bankAccount' => NULL,
            'category' => '',
            'activity' => '',
            'description' => '',
        ];
        $this->groupLocation = [
            'boxNumber' => NULL,
            'ward' => '',
            'sublocation' => NULL,
        ];
        $this->members = collect();
        $this->membersArray = [];
        $this->memberEditKey = null;
        $this->isMemberEdit = false;
        $this->groupMember = [
            'name' => '',
            'role' => '',
            'phonenumber' => '',
            'gender' => '',
            'disabled' => false,
            'member_Id' => '',
        ];
        $this->groupLoan = null;
    }

    public function selectedGroup($id)
    {
        $this->group = $this->groups->where('id', $id)->first();


        if(!empty($this->group)) {
            $this->searchGroup = $this->group->name;
            $this->na = null;
            $this->groupData = [
                'groupName' => $this->group->name,
                'dateFormed' => $this->group->date_formed,
                'dateRegistered' => $this->group->date_registered,
                'registrationNumber' => $this->group->reg_number,
                'bankName' => $this->group->bank,
                'bankAccount' => $this->group->account,
                'category' => array_search($this->group->category, $this->categories),
                'activity' => array_search($this->group->activity, $this->activities),
                'description' => $this->group->activity_desc,
            ];

            $this->groupLocation = [
                'boxNumber' => $this->group->box,
                'ward' => $this->group->ward->id,
                'sublocation' => $this->group->location,
            ];
            $this->members = $this->group->groupLoans->last()->groupMembers;
            $this->membersArray = $this->members->map(function ($tmember) {
                return [
                    'name' => $tmember->member->name,
                    'role' => $tmember->role,
                    'phonenumber' => $tmember->member->number,
                    'gender' => $tmember->member->gender,
                    'disabled' => $tmember->member->disabled,
                    'member_Id' => $tmember->member_Id,
                ];
            })->toArray();

        }else {
            $this->na = 'Invalid group!!!';
        }
        $this->groups = collect();

    }

    public function submitGroupData()
    {
        $this->dataValidations['dataStep'] = false;
        $this->icon['data'] = 'fa-exclamation';
        $category = array_keys($this->categories);
        $activity = array_keys($this->activities);
        try {
            $validData = $this->validate([
                'groupData.groupName' => 'required|string',
                'groupData.dateFormed' => 'required|date',
                'groupData.dateRegistered' => 'required|date',
                'groupData.registrationNumber' => 'required',
                'groupData.bankName' => 'required|string',
                'groupData.bankAccount' => 'required|string|regex:/^[0-9]+$/',
                'groupData.category' => 'required|in:' . implode(',', $category),
                'groupData.activity' => 'required|in:' . implode(',', $activity),
                'groupData.description' => 'required|string|min:10',
            ]);

            if (!$this->isRefinancing) {
                $this->validate(['groupData.registrationNumber' => 'required|unique:groups,reg_number']);
            }

            $this->dataValidations['dataStep'] = true;
            $this->icon['data'] = 'fa-check';
            $this->step = 'locationStep';
        } catch (ValidationException $e) {
            $this->step = 'dataStep';
            $errors = $e->validator->errors()->messages();
            foreach ($errors as $field => $message) {
                $this->addError($field, $message[0]); // Add the first error message for each field
            }
            return;
        }

    }

    public function submitGroupLocation()
    {
        $this->dataValidations['locationStep'] = false;
        $this->icon['location'] = 'fa-exclamation';
        try {
            $this->validate([
                'groupLocation.boxNumber' => 'required|numeric',
                'groupLocation.ward' => 'required|numeric|in:' . implode(',', $this->wardIds),
                'groupLocation.sublocation' => 'required|string',
            ]);
            $this->dataValidations['locationStep'] = true;
            $this->icon['location'] = 'fa-check';
            $this->step = 'membershipStep';

        } catch (ValidationException $e) {
            $this->step = 'locationStep';
            $errors = $e->validator->errors()->messages();
            foreach ($errors as $field => $message) {
                $this->addError($field, $message[0]); // Add the first error message for each field
            }
            return;
        }

    }



    public function submitMember()
    {
        $this->icon['membership'] = 'fa-exclamation';

        $this->validate([
            'groupMember.name' => 'required|string',
            'groupMember.role' => 'required|in:' . implode(',', $this->roles),
            'groupMember.gender' => 'required|in:' . implode(',', $this->genders),
            'groupMember.member_Id' => 'required|string|between:6,10|regex:/^[0-9]+$/',
            'groupMember.phonenumber' => 'required|regex:/^[0-9]{10}$/',
        ]);


        if ($this->isMemberEdit && $this->memberEditKey != null)
        {

            if ($this->membersArray[$this->memberEditKey]['member_Id'] === $this->groupMember['member_Id'])
            {
                #edit
                $this->editMember();
            }else {
                $this->memberIdValidation($this->groupMember['member_Id']);
            }
        }else
        {
            $this->memberIdValidation($this->groupMember['member_Id']);
        }


        $this->icon['membership'] = 'fa-check';
    }

    private function memberIdValidation($member_Id)
    {
        if (strlen($member_Id) > 6 && strlen($member_Id) < 10)
        {
            # search for member with that id
            $members = GroupMember::where('member_Id', $member_Id)->with(['groupLoan.group','groupLoan.loanPhase.constituency'])->get();

            #if there is no member with such id then add the member to members list
            if ($members->isEmpty())
            {
                if ($this->isMemberEdit){
                    if($this->membersArray[$this->memberEditKey]['member_Id'] === $member_Id){
                        $this->editMember();
                    }else {
                        if (!($this->checkIdInArray())){
                            $this->editMember();
                        }
                    }

                } else {
                    $this->addmember();
                }
            }else {
                $memberloans = [];
                foreach ($members as $member) {
                    $balance = $member->groupLoan->amount_issued - $member->groupLoan->amount_repaid;
                    if ($balance > 0) {
                        $memberloans [] = [
                            'member' => $member->member->name,
                            'member_id' => $member->member_Id,
                            'group' => $member->groupLoan->group->name,
                            'constituency' => $member->groupLoan->loanPhase->constituency->constituency_name,
                            'role' => $member->role,
                            'issued' => number_format($member->groupLoan->amount_issued),
                            'phase' => $member->groupLoan->loanPhase->phase,
                            'balance' => number_format($balance),
                        ];
                    }
                }
                if (empty($memberloans))
                {
                    if ($this->isMemberEdit){
                        if($this->membersArray[$this->memberEditKey]['member_Id'] === $member_Id){
                            $this->editMember();
                        }else {
                            if (!($this->checkIdInArray())){
                                $this->dispatch('editUsedId');
                            }
                        }

                    } else {
                        $this->addmember();
                    }
                } else
                {
                    $this->dispatch('memberloans', memberloans: $memberloans);
                }

            }
        }else
        {
            $this->error_id = 'The ID number is invalid.';
        }

    }

    public function selectEditMember(string $member_id)
    {
        $this->step = 'membershipStep';
        $desiredMembers = array_filter($this->membersArray, function ($member) use ($member_id) {
            return $member['member_Id'] === $member_id;
        });
        if (!empty($desiredMembers)){
            $this->groupMember = $this->membersArray[key($desiredMembers)];
            $this->memberEditKey = key($desiredMembers);
            $this->isMemberEdit = true;
        }
    }

    private function editMember()
    {
        $this->groupMember['name'] = ucwords($this->groupMember['name']);
        $this->membersArray[$this->memberEditKey] = $this->groupMember;
        $this->error_id = null;
        $this->memberEditKey = null;
        $this->isMemberEdit = false;
        $this->groupMember = [
            'name' => '',
            'role' => '',
            'phonenumber' => '',
            'gender' => '',
            'disabled' => false,
            'member_Id' => '',
        ];
        $this->dispatch('memberEdited');
    }

    private function addmember()
    {
        #new member
        $memberExists = array_filter($this->membersArray, function ($member)  {
            return $member['member_Id'] === $this->groupMember['member_Id'];
        });
        if(empty($memberExists)){
            $this->groupMember['name'] = ucwords($this->groupMember['name']);
            array_push($this->membersArray, $this->groupMember);
            $this->error_id = null;
            $this->groupMember = [
                'name' => '',
                'role' => '',
                'phonenumber' => '',
                'gender' => '',
                'disabled' => false,
                'member_Id' => '',
            ];
            $this->dispatch('member_added');
        }else {
            $this->dispatch('member_exists');
        }

    }

    private function checkIdInArray()
    {
        $memberExists = array_filter($this->membersArray, function ($member)  {
            return $member['member_Id'] === $this->groupMember['member_Id'];
        });

        if(empty($memberExists)){
            return false;
        } else
        {
            $this->dispatch('member_exists');
            return true;
        }
    }

    public function removeMember(string $member_id)
    {
        $this->memberEditKey = null;
        $this->isMemberEdit = false;
        $this->membersArray = array_filter($this->membersArray, function ($member) use ($member_id) {
            return $member['member_Id'] !== $member_id;
        });

        $this->dispatch('member_removed');
    }

    public function continue()
    {
        $this->step = "summary";
    }

    public function addDefaulter()
    {
        $this->addmember();
    }
    public function saveGroupData()
    {
        $this->submitLoanPhase();
        $this->submitGroupData();
        $this->submitGroupLocation();
        if ($this->checkDataSaved()){
            $this->step = "summary";
            $this->finalMembers();
        }
    }

    private function finalMembers()
    {
        if (count($this->membersArray) < 4)
        {
            $this->step = "membershipStep";
            $this->dispatch('dataNotSaved', message: 'Please Add Members!');
            return;
        } else
        {
            $this->md();
        }

    }

    private function md ()
    {
        $defaulterMembers = [];
        foreach ($this->membersArray as $member) {
            # search for member in members
            $memberLoans = GroupMember::where('member_Id', $member['member_Id'])->with(['member','groupLoan.group','groupLoan.loanPhase.constituency'])->get();
            $memberDefaultedLoans = [];

            if(!$memberLoans->isEmpty())
            {
                foreach ($memberLoans as $memberloan) {
                    $balance = $memberloan->groupLoan->amount_issued - $memberloan->groupLoan->amount_repaid;
                    if ($balance > 0) {
                        array_push($memberDefaultedLoans, $memberloan);
                    }
                }
                if(count($memberDefaultedLoans) > 0)
                {
                    array_push($defaulterMembers, $memberDefaultedLoans);
                }
            }
        }

        if (count($defaulterMembers) > 0)
        {
            $this->dispatch('defaulterMembers', defaultermembers: $defaulterMembers);
            return;
        } else {
            $this->saveToDatabase();
        }
    }


    public function applyDefaulter()
    {
        $this->saveToDatabase();
    }

    private function checkDataSaved()
    {

        if(!$this->dataValidations['phaseStep'])
        {
            $this->step = "phaseStep";
            $this->dispatch('dataNotSaved', message: 'Please Save the loan phase Data!');
            return false;
        } elseif (!$this->dataValidations['dataStep']) {
            $this->step = "dataStep";
            $this->dispatch('dataNotSaved', message: 'Please Save the Group Data!');
            return false;
        } elseif (!$this->dataValidations['locationStep']) {
            $this->step = "locationStep";
            $this->dispatch('dataNotSaved', message: 'Please Save the location Data!');
            return false;
        } elseif ($this->membersArray == null) {
            $this->step = "membershipStep";
            $this->dispatch('dataNotSaved', message: 'Please Add Members!');
            return false;
        } else {
            return true;
        }
    }

    #add to database
    public function saveToDatabase()
    {
        if (auth()->user()->hasPermissionTo('create loan application')){
            #1. update or create group information
            if ($this->dbGroupData()) {
                if($this->dbGroupLoan()) {
                    if($this->dbGroupMembers()) {
                        $this->dispatch('application', status:'success');
                        $this->emptyGroupData();
                        $this->step = 'phaseStep';
                        $this->phase = null;
                        $this->level = null;
                        $this->applicationDate = null;

                        $this->searchGroup = '';
                        $this->amount = null;
                    }
                }
            } else {
                $this->dispatch('application', status:'invalid');
                return;
            }
        }
    }

    private function dbGroupLoan() : bool
    {
        $this->groupLoan = new GroupLoan();

        $this->groupLoan->loan_phase_id = $this->phase;
        $this->groupLoan->group_id = $this->group->id;
        $this->groupLoan->application_date = $this->applicationDate;
        $this->groupLoan->amount_applied = $this->amount;
        $this->groupLoan->amount_issued = 0;
        $this->groupLoan->amount_repaid = 0;
        $this->groupLoan->loan_status = 'pending';
        $this->groupLoan->status = 'Pending';
        $this->groupLoan->level = $this->level;

        return $this->groupLoan->save();
    }

    private function dbGroupData() : bool
    {
        if (!$this->isRefinancing){
            $this->group = new Group();
        }

        $this->group->name = ucwords($this->groupData['groupName']);
        $this->group->ward_id = $this->wards->where('id', $this->groupLocation['ward'])->first()->id;
        $this->group->date_formed = $this->groupData['dateFormed'];
        $this->group->date_registered = $this->groupData['dateRegistered'];
        $this->group->reg_number = $this->groupData['registrationNumber'];
        $this->group->bank = $this->groupData['bankName'];
        $this->group->account = $this->groupData['bankAccount'];
        $this->group->category = $this->categories[$this->groupData['category']];
        $this->group->activity = $this->activities[$this->groupData['activity']];
        $this->group->activity_desc = $this->groupData['description'];
        $this->group->location = $this->groupLocation['sublocation'];
        $this->group->box = $this->groupLocation['boxNumber'];

        return $this->group->save();
    }

    private function dbGroupMembers () : bool
    {
        foreach ($this->membersArray as $member) {
            $memberUpCre = Member::updateOrCreate(
                ['member_Id' => $member['member_Id']],
                [
                    'name' => ucwords($member['name']),
                    'number' => $member['phonenumber'],
                    'gender' => $member['gender'],
                    'disabled' => $member['disabled'],
                ]
            );

            if ($memberUpCre instanceof Member){
                $groupMember = new GroupMember();
                $groupMember->member_Id = $member['member_Id'];
                $groupMember->group_loans_id = $this->groupLoan->id;
                $groupMember->role = $member['role'];
                $isInsered = $groupMember->save();
            }else {
                return false;
            }
        }
        return $isInsered;
    }

    public function render()
    {
        $this->getLoanPhases();
        $this->getWards();

        return view('livewire.loan.application');
    }


}



