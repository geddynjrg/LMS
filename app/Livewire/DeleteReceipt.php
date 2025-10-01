<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\LoanReceipts as Receipt;
use App\Models\GroupLoan;
class DeleteReceipt extends Component
{

    public $receipt = [
        'id' => '',
        'receipt_number' => '',
        'transaction_date' => '',
        'amount' => '',
        'payment' => '',
        'reference_number' => '',
        'group' => '',
    ];

    public $data = [];

    #[On('delete-receipt-modal')]
    public function delete($data)
    {
        $this->data = $data;
        if (!empty($this->data)){
            $this->receipt = [
                'id' => $data[1],
                'receipt_number' => $data[2],
                'transaction_date' => $data[3],
                'group' => $data[4],
                'amount' => intval(str_replace(',', '', $data[5])),
                'payment' => $data[6],
                'reference_number' => $data[7]
            ];
        }

    }


    public function deleteReceipt()
    {
        //ensure data is not null
        if (!empty($this->data)){
         //ensure you have valid receipt
            $receipt = Receipt::find($this->data[1]);
            $groupLoan = GroupLoan::find($receipt->group_loans_id);
            if(auth()->user()->hasPermissionTo('delete receipt') && $receipt !== null && $groupLoan !== null)
            {
                //edit balance
                $initBal = $groupLoan->amount_repaid;
                $groupLoan->amount_repaid = $initBal - intval(str_replace(',', '', $this->data[5]));
                if ($groupLoan->save() && $receipt->delete()){
                    $this->dispatch('receipt-deleted');
                }
            }
        }

    }

    public function render()
    {
        return view('livewire.delete-receipt');
    }
}
