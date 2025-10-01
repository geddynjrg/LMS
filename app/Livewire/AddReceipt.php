<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\GroupLoan;
use App\Models\LoanReceipts as Receipts;
use App\Models\Constituency;

class AddReceipt extends Component
{

    public $groups;
    public $constituency;
    public $selectedOption = "0";
    public $payments = ['Bank', 'Mpesa'];

    public $receipt = [
        'receipt_number' => '',
        'transaction_date' => '',
        'amount' => '',
        'payment' => '',
        'reference_number' => '',
    ];
    protected $rules = [
        'selectedOption'=> 'required|exists:group_loans,id',
        'receipt.receipt_number' => 'required',
        'receipt.transaction_date' => 'required|date',
        'receipt.amount' => 'required|numeric|gt:0',
        'receipt.payment' => 'required|in',
        'receipt.reference_number' => 'required',
    ];

    protected $messages = [
        'selectedOption.exists' => 'The selected Group is Invalid',
    ];



    #[On('add-receipt-modal')]
    public function data()
    {
        $this->getGroups();
    }

    public function mount (Constituency $constituency)
    {
        $this->constituency = $constituency;
    }

    public function submitNewReceipt()
    {
        $this->validate([
            'selectedOption'=> 'required|exists:group_loans,id',
            'receipt.receipt_number' => ['required', function($attribute, $value, $fail) {
                $existsReceipt = Receipts::whereHas('groupLoan.loanPhase', function ($query) {
                    $query->where('constituency_code', $this->constituency->constituency_code);
                })->where('receipt_number', strtoupper($value))->first();

                if ($existsReceipt) {
                    $fail('The Receipt Number is already recorded.');
                }
            }],
            'receipt.transaction_date' => 'required|date',
            'receipt.amount' => 'required|numeric|gt:0',
            'receipt.payment' => 'required|in:' . implode(',', $this->payments),
            'receipt.reference_number' => 'required',
        ], [
            'selectedOption.exists' => 'The selected group does not exist.',
        ]);


        $nReceipt = new Receipts;
        $nReceipt->group_loans_id = $this->selectedOption;
        $nReceipt->receipt_number = strtoupper($this->receipt['receipt_number']);
        $nReceipt->transaction_date = $this->receipt['transaction_date'];
        $nReceipt->amount = $this->receipt['amount'];
        $nReceipt->payment = $this->receipt['payment'];
        $nReceipt->reference_number = strtoupper($this->receipt['reference_number']);

        $groupLoan = GroupLoan::where('id', $this->selectedOption)->first();

        if ($nReceipt->save())
        {
            $groupLoan->amount_repaid = $groupLoan->amount_repaid + $this->receipt['amount'];

            if ($groupLoan->save())
            {
                $this->selectedOption = "0";
                $this->receipt = [
                    'receipt_number' => '',
                    'transaction_date' => '',
                    'amount' => '',
                    'payment' => '',
                    'reference_number' => '',
                ];

                $this->dispatch('receipt-added');
            }
        }

    }

    public function render()
    {
        $this->getGroups();
        return view('livewire.add-receipt');
    }

    private function getGroups()
    {
        $code = $this->constituency->constituency_code;
        $this->groups = GroupLoan::whereHas('loanPhase.constituency', function ($query) use ($code) {
            $query->where('constituency_code', $code);
        })->where('loan_status', 'financed')
        ->with('group')
        ->orderBy('loan_phase_id', 'desc')
        ->get();
    }


}
