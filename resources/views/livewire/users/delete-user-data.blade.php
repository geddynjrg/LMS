<div>
    @if ($loading)
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2">Loading user data...</div>
        </div>
    @else
        <form wire:submit.prevent="destroy" class="">
            <!-- input states -->
            <div class="form-group">
                <label class="col-form-label" for="name"> Input users's full name</label>
                <input disabled wire:model.defer="name" required type="text" class="form-control" id="name" placeholder="Enter ...">
            </div>
            <div class="row">
                <div class="col-sm-8">
                    <div class="form-group">
                        <label class="col-form-label" for="email"> Email</label>
                        <input disabled wire:model.defer="email" required type="email" class="form-control" id="email" placeholder="Enter ...">
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label class="col-form-label" for="role">Select User Role</label>
                        <select disabled id="role" wire:model.defer="role" required class="form-control">
                            <option>Select...</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label class="col-form-label" for="phone"> Phone Number</label>
                        <input disabled wire:model.defer="phone_number" required type="text" class="form-control" id="phone" placeholder="Enter ...">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label class="col-form-label" for="idnumber"> ID Number</label>
                        <input disabled wire:model.defer="id_number" required type="text" class="form-control" id="idnumber" placeholder="Enter ...">
                    </div>
                </div>
            </div>
            <div class="btn-group float-right m-2" role="group">
                <button type="submit" class="btn btn-danger mr-2" wire:loading.attr="disabled">
                    <span wire:loading wire:target="destroy">deleting...</span>
                    <span wire:loading.remove wire:target="destroy"><i class=" fas fa-trash"></i> Confirm Remove User </span>
                </button>
            </div>
        </form>
    @endif
</div>
