<div class="card card-default">
    <div class="card-body">
        <form wire:submit.prevent="submitComment">
            <div class="row">
                <div class="form-group col-sm-12">
                    <label for="activitydesc">Reason for Loan Application Denial.<code>*</code></label>
                    <textarea required wire:model="comment" id="activitydesc" class="form-control form-control-border border-width-2 text-cyan" rows="5" placeholder="describe as to why the application has been deffered."></textarea>
                    @error('comment')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>
            </div>
            <div class="btn-group float-right m-2" role="group">
                <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                    <span wire:loading wire:target="submitComment">Saving...</span>
                    <span wire:loading.remove wire:target="submitComment"><i class="fas fa-save"></i> Submit </span>
                </button>
            </div>
        </form>
    </div>
</div>
