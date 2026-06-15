<div class="card card-default">
    <div class="card-body">
        <h5 class="text-bold capitalize" style="text-decoration: underline">Receipt Details:</h5>
        <ul class="text-capitalize">
            <li><span class="text-bold">Group Name:</span> {{ $receipt['group']}} </li>
            <li><span class="text-bold">Receipt Number:</span> {{ $receipt['receipt_number']}} </li>
            <li><span class="text-bold">Receipt Amount:</span> {{ $receipt['amount']}} </li>
            <li><span class="text-bold">Payment Method:</span> {{ $receipt['payment']}} </li>
            <li><span class="text-bold">Transaction Date:</span> {{ $receipt['transaction_date']}} </li>
            <li><span class="text-bold">Reference:</span> {{ $receipt['reference_number']}} </li>
        </ul>
        @can('delete receipt')
        <p class="text-danger">Are you sure you want to delete this receipt? This action cannot be undone.</p>

        <div class="btn-group float-right m-2" role="group">
            <button type="submit" class="btn btn-warning mr-2" wire:loading.attr="disabled" wire:click="deleteReceipt()">
                <span wire:loading wire:target="deleteReceipt">Deleting...</span>
                <span wire:loading.remove wire:target="deleteReceipt"><i class="fas fa-trash-alt"></i> Confirm Delete </span>
            </button>
        </div>
        @else
            <span class="text-danger">You do not have permission to delete receipts.</span>

        @endcan
    </div>
</div>
