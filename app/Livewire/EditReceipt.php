<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\LoanReceipts as Receipt;
use App\Models\GroupLoan;
use App\Models\Constituency;

class EditReceipt extends Component
{

    public $receipt = [
        'id' => '',
        'receipt_number' => '',
        'transaction_date' => '',
        'amount' => '',
        'payment' => '',
        'reference_number' => '',
    ];

    public $data = [];
    public $payments = ['Bank', 'Mpesa'];
    public Constituency $constituency;

    #[On('edit-receipt-modal')]
    public function edit($data)
    {
        $this->data = $data;
        if (!empty($this->data)){
            $this->receipt = [
                'id' => $data[1],
                'receipt_number' => $data[2],
                'transaction_date' => $data[3],
                'amount' => intval(str_replace(',', '', $data[5])),
                'payment' => $data[6],
                'reference_number' => $data[7]
            ];
        }

    }

    public function mount (Constituency $constituency)
    {
        $this->constituency = $constituency;
    }

    public function submitEditReceipt()
    {
        $this->validate([
            'receipt.receipt_number' => 'required',
            'receipt.transaction_date' => 'required|date',
            'receipt.amount' => 'required|numeric|gt:0',
            'receipt.payment' => 'required|in:' . implode(',', $this->payments),
            'receipt.reference_number' => 'required',
        ]);

        if (!empty($this->data)){
            if($this->receipt['receipt_number'] !== $this->data[2])
            {
                $this->validate([
                    'receipt.receipt_number' => [function($attribute, $value, $fail) {
                        $existsReceipt = Receipt::whereHas('groupLoan.loanPhase', function ($query) {
                            $query->where('constituency_code', $this->constituency->constituency_code);
                        })->where('receipt_number', strtoupper($value))->first();

                        if ($existsReceipt) {
                            $fail('The Receipt Number is already recorded.');
                        }
                    }],
                ]);
            }



            $receipt = Receipt::find($this->data[1]);
            $groupLoan = GroupLoan::find($receipt->group_loans_id);
            if($receipt !== null && $groupLoan !== null)
            {
                $receipt->receipt_number = strtoupper($this->receipt['receipt_number']);
                $receipt->transaction_date = $this->receipt['transaction_date'];
                $receipt->amount = $this->receipt['amount'];
                $receipt->payment = $this->receipt['payment'];
                $receipt->reference_number = strtoupper($this->receipt['reference_number']);

                if(auth()->user()->hasPermissionTo('update receipt') && $receipt->save())
                {
                    if($this->receipt['amount'] !== intval(str_replace(',', '', $this->data[5])))
                    {
                        $initBal = $groupLoan->amount_repaid;
                        $minus = $initBal - intval(str_replace(',', '', $this->data[5]));
                        $newBal = $minus + $this->receipt['amount'];
                        $groupLoan->amount_repaid = $newBal;
                        $groupLoan->save();
                    }
                    //dispatch event
                    $this->dispatch('receipt-edited');
                }
            }

        }
    }

    public function render()
    {
        return view('livewire.edit-receipt');
    }
}
