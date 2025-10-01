   <!--Approve Modal -->
   <div class="card card-default">
    <div class="card-body">
        <form wire:submit.prevent="financeLoan">
            <div class="row">
                <div class="form-group col-sm-6">
                    <label for="chequeNo">Cheque Number <code>*</code></label>
                    <input required type="text" wire:model.defer="chequeNo"  class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="chequeNo" placeholder="(069254)">
                    @error('chequeNo')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group col-sm-6">
                    <label for="dateOfIssue">Date of Issue<code>*</code></label>
                    <input required type="date" wire:model.defer="dateOfIssue" class="form-control form-control-border border-width-2  text-cyan" id="dateOfIssue">
                    @error('dateOfIssue')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div> 
                <div class="form-group col-sm-6">
                    <label for="amount">Amount Issued <code>*</code></label>
                    <input required type="number" wire:model.defer="amount"  class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="amount" placeholder="100000">
                    @error('amount')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="btn-group float-right m-2" role="group">
                <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                    <span wire:loading wire:target="financeLoan">Saving...</span>
                    <span wire:loading.remove wire:target="financeLoan"><i class="fas fa-save"></i> Issue Cheque </span>
                </button>
            </div>
        </form> 
    </div>
</div>
            
                
            

