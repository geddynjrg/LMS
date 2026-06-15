<div class="card card-default">
    <div class="card-body">
        <form wire:submit.prevent="submitNewReceipt">
            <div class="row">
                <div  class="col-md-12 col-sm-12 form-group">
                    <label for="selectgroup">Select Group</label>
                    <div wire:ignore>
                      <select required  wire:model.live="selectedOption" id="selectgroup" class="form-control select2bs4" style="width: 100%;">
                        <option value="0" >Select group...</option>
                        @foreach ($groups as $group_loan )
                          <option value="{{ $group_loan->id }}">{{ $group_loan->group->code}} - {{ $group_loan->group->name}} - {{ $group_loan->group->identifier}} - {{ $group_loan->loanPhase->phase}}</option>
                        @endforeach
                      </select>
                    </div>
                    @error('selectedOption')
                      <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
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
                    <span wire:loading wire:target="submitNewReceipt">Saving...</span>
                    <span wire:loading.remove wire:target="submitNewReceipt"><i class="fas fa-save"></i> Submit </span>
                </button>
            </div>
        </form>
    </div>
</div>
@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/select2/css/select2.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
@endpush
@push('scripts')
    <script src="{{asset('plugins/select2/js/select2.full.min.js')}}"></script>
    <script>
         $(function () {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })

            $('.select2').on('change', function (e) {
                @this.set('selectedOption', e.target.value);
            })

            $('.select2bs4').on('change', function (e) {
                @this.set('selectedOption', e.target.value);
            })
        });

        document.getElementById('amount').addEventListener('input', function () {
            this.value = Math.round(this.value);
        });
    </script>
@endpush