   <!--Approve Modal -->
<div class="card card-default">
    <div class="card-body">
        @if($appliedLoan !== null && $appliedLoan['level'] === 'New')
        <form wire:submit.prevent="approveNewApplication">
            <div class="row">
                <div class="form-group col-sm-6">
                    <label for="code">Group code <code>*</code></label>
                    <input required type="text" wire:model.live="code"  class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="code" placeholder="(254)">
                    @error('code')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group col-sm-6">
                    <label for="identifier">GFS Code<code>*</code></label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text">{{$gfscode}}</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group col-sm-6">
                    <label for="identifier">Group Identifier<code>*</code></label>
                    <input required type="text" wire:model="identifier" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="identifier" placeholder="(MZA254)">
                    @error('identifier')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>               
                
                <div class="form-group col-sm-6">
                    <label for="amount">Amount Approved <code>*</code></label>
                    <input required type="number" wire:model.defer="amount" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="amount" placeholder="(100,000)">
                    @error('amount')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="btn-group float-right m-2" role="group">
                <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                    <span wire:loading wire:target="approveApplication">Saving...</span>
                    <span wire:loading.remove wire:target="approveApplication"><i class="fas fa-save"></i> Approve </span>
                </button>
            </div>
        </form>
        @elseif ($appliedLoan !== null && ($appliedLoan['level'] === 'Refinancing' || $appliedLoan['level'] === 'Resubmission'))
        <form wire:submit.prevent="approveRefApplication">
            <div class="row">
                <div class="form-group col-sm-12">
                    <label for="rAmount">Amount Approved <code>*</code></label>
                    <input required type="number" wire:model.defer="rAmount" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="rAmount" placeholder="(200,000)">
                    @error('rAmount')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="btn-group float-right m-2" role="group">
                <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                    <span wire:loading wire:target="approveApplication">Saving...</span>
                    <span wire:loading.remove wire:target="approveApplication"><i class="fas fa-save"></i> Approve </span>
                </button>
            </div>
        </form>
        @endif
    </div>
</div>
            
                
            

