<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On; 
use App\Models\GroupLoan;
class DifferApplication extends Component
{

    public $loanId;
    public $comment;


    #[On('differ-modal')] 
    public function differ($loanId)
    {
        $this->loanId = $loanId;
    }


    public function submitComment()
    {
        $this->validate([
            'comment' => ['required','string', function ($attribute, $value, $fail) {
                $wordCount = str_word_count($value);
                if($wordCount < 8) {
                    $fail("The $attribute must have a minimum of 8 words.");
                }
            }],
        ]);

        $groupLoan = GroupLoan::find($this->loanId);
        if($groupLoan){
            $groupLoan->comments = $this->comment;
            $groupLoan->loan_status = 'deferred';
            $groupLoan->amount_approved = 0;
            $groupLoan->status = 'Differed';
            $groupLoan->save();

            $this->comment = null;
            if($groupLoan){
                $this->dispatch('refreshData', message: 'success')->to(LoanAplicant::class);
                $this->dispatch('isDeffered', message: 'success');
            }else{
                $this->dispatch('isDeffered', message: 'error');
            }
        }else {
            $this->dispatch('isDeffered', message: 'error');
        }
    }

    public function render()
    {
        return view('livewire.loan.differ-application');
    }
}
