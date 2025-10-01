<div class="card card-default">
    <div class="card-body">
      @can('update receipt')

        <form wire:submit.prevent="submitEditReceipt">
            <div class="row">
                <div class="col-md-6 col-sm-12 form-group">
                    <label for="receiptNumber">Receipt Number<code>*</code></label>
                    <input required wire:model="receipt.receipt_number" type="text" class="form-control form-control-border border-width-2 text-cyan" id="receiptNumber" placeholder="D125639">
                    @error('receipt.receipt_number')
                      <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6 col-sm-12 form-group">
                    <label for="transactionDate">Transaction Date</label>
                    <input required wire:model="receipt.transaction_date" type="date" class="form-control form-control-border border-width-2 text-cyan" id="transactionDate">
                    @error('receipt.transaction_date')
                      <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6 col-sm-12 form-group">
                    <label for="amount">Amount</label>
                    <input required wire:model="receipt.amount" type="number" min="1" step="1" class="form-control form-control-border border-width-2 text-cyan" id="amount" placeholder="5000">
                    @error('receipt.amount')
                      <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6 col-sm-12 form-group">
                    <label>Mode of Payment</label>
                    <select required wire:model="receipt.payment" class="form-control form-control-border border-width-2 text-cyan">
                      <option selected >Select...</option>
                      <option value="Bank">Bank</option>
                      <option value="Mpesa">Mpesa</option>
                    </select>
                    @error('receipt.payment')
                      <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6 col-sm-12 form-group">
                    <label for="referenceNumber">Reference Number</label>
                    <input required wire:model="receipt.reference_number" type="text" class="form-control form-control-border border-width-2 text-cyan" id="referenceNumber" placeholder="RFJ78JKSL9">
                    @error('receipt.reference_number')
                      <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="btn-group float-right m-2" role="group">
                <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                    <span wire:loading wire:target="submitEditReceipt">Saving...</span>
                    <span wire:loading.remove wire:target="submitEditReceipt"><i class="fas fa-save"></i> Submit </span>
                </button>
            </div>
        </form>

      @else
       <span class="text-danger">You do not have permission to edit receipts.</span>

      @endcan
    </div>
</div>
