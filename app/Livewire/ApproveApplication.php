<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Group;
use App\Models\GroupLoan;

class ApproveApplication extends Component
{
    public $appliedLoan;
    public $amount;
    public $rAmount;
    public $gfscode;
    public $gfCode;
    public $code;
    public $errorCode;
    public $identifier;
    public $group;

    /*#[On('show-approve-modal')]
    public function updatePostList($title)
    {
        dd('application');
    }*/

    #[On('approve-modal')]
    public function approve($appliedLoan)
    {
        $this->appliedLoan = $appliedLoan;
        $this->amount = $appliedLoan['amount_applied'];
        if($appliedLoan['level'] === 'New'){
            $this->generateGfsCode();
        }

    }

    public function updatedCode($value)
    {
        $this->gfscode = $this->gfCode.str($value);
    }

    public function approveNewApplication()
    {

        $this->code = $this->code->toString();
        $this->validate([
            'code' => ['required', 'string', 'regex:/^[0-9]+$/', function($attribute, $value, $fail){
                $existingCode = Group::whereHas('ward', function($query) {
                    $query->where('constituency_code', '026');
                })->where('code', $this->code)->first();

                if($existingCode) {
                    //$this->errorCode = 'The code has already been taken.';
                    $fail('The code has already been taken.');
                }
            }],
            'identifier' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $group = Group::find($this->appliedLoan['group']['id']);


        #save to database, code, gfscode, identifier, loan_status
        if($group)
        {
            $group->identifier = strtoupper($this->identifier);
            $group->code = $this->code;
            $group->gfs_code = $this->gfscode;
            $group->save();

            $groupLoan = GroupLoan::find($this->appliedLoan['id']);
            $groupLoan->loan_status = 'approved';
            $groupLoan->amount_approved = $this->amount;
            $groupLoan->save();

            $this->dispatch('loanApproved', message: 'success');

        } else{
            $this->dispatch('loanApproved', message: 'error');
        }
        $this->resetInputs();
    }

    public function approveRefApplication()
    {
        $this->validate([
            'rAmount' => 'required|numeric',
        ]);

        $groupLoan = GroupLoan::find($this->appliedLoan['id']);
        if($groupLoan)
        {
            $groupLoan->loan_status = 'approved';
            $groupLoan->amount_approved = $this->rAmount;
            $groupLoan->save();

            $this->dispatch('loanApproved', message: 'success');
        }else {
            $this->dispatch('loanApproved', message: 'error');
        }
        $this->resetInputs();
    }

    private function resetInputs()
    {
        $this->appliedLoan = null;
        $this->amount = null;
        $this->gfscode = null;
        $this->gfCode = null;
        $this->code = null;
        $this->errorCode = null;
        $this->identifier = null;
    }

    private function generateGfsCode()
    {
        $lastCode = Group::whereHas('ward', function($query) {
            $query->where('constituency_code', '026');
        })->where('code', '!=', null)->with('groupLoans')->orderBy('id')->latest()->get()->last()->code;
        $this->code = str($lastCode+1);

        #county_code, constituency_code, default code, category, activity, code
        $county_code = auth()->user()->county->county_code;
        $default_code = '-4110';
        $categories = [
            'Youth' => '1',
            'Women' => '2',
            'PWD' => '3',
        ];
        $sectors = [
            'Trade' => '1',
            'Service' => '2',
            'Manufacturing' => '3',
            'Agriculture' => '4',
            'Other' => '5',
        ];
        $this->gfCode = '4-'.$county_code.'-'.'026'.'-411020'.$categories[$this->appliedLoan['group']['category']].'-'.$sectors[$this->appliedLoan['group']['activity']].'0'.$categories[$this->appliedLoan['group']['category']].'-';
        $this->gfscode = $this->gfCode.str($this->code);
    }

    public function render()
    {
        return view('livewire.loan.approve-application');
    }


}
