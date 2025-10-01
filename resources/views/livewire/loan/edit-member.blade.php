<div class="card card-default">
    <div class="card-body">
        @if ($loading)
            <div class="text-center">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span>Loading...</span>
            </div>
        @else

        @can('update member')


        <form wire:submit.prevent="submitMember">
            <div class="row">
                <div class="form-group col-sm-12">
                    <label for="name">Member Name <code>(full name)*</code></label>
                    <input required type="text" wire:model.defer="groupMember.name" required class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="name" placeholder="Name">
                    @error('groupMember.name')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group col-sm-6">
                    <label for="IDNumber">ID. Number<code></code></label>
                    <input required type="text" required wire:model="groupMember.member_Id" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="IDNumber" >
                    @error('groupMember.member_Id')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                    <span class="text-sm text-danger">{{ $error_id }}</span>
                </div>
                <div class="form-group col-sm-6">
                    <label for="role">Member Role <code></code></label>
                    <select required wire:model.defer="groupMember.role" class="custom-select form-control-border border-width-2 text-cyan" id="role">
                        <option value="">select...</option>
                        @foreach ($roles as $position )
                            <option value="{{$position}}">{{$position}}</option>
                        @endforeach
                    </select>
                    @error('groupMember.role')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group col-sm-6">
                    <label for="phonenumber">Tell. Number <code></code></label>
                    <input required wire:model.defer="groupMember.phonenumber" type="text" class="form-control form-control-border border-width-2  text-cyan" id="phonenumber">
                    @error('groupMember.phonenumber')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group col-sm-6">
                    <label for="gender">Gender</label>
                    <select required wire:model.defer="groupMember.gender" class="custom-select form-control-border border-width-2 text-cyan" id="gender">
                        <option value="">select...</option>
                        @foreach ($genders as $option)
                            <option value="{{$option}}">{{$option}}</option>
                        @endforeach

                    </select>
                    @error('groupMember.gender')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group col-sm-12">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox"  wire:model="groupMember.disabled" class="custom-control-input" id="exampleCheck1">
                        <label class="custom-control-label" for="exampleCheck1">The Member is Disabled.</label>
                    </div>
                </div>
            </div>
            <div class="btn-group float-right m-2" role="group">
                <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                    <span wire:loading wire:target="submitMember">Saving...</span>
                    <span wire:loading.remove wire:target="submitMember"><i class="fas fa-save"></i> Save </span>
                </button>
            </div>
        </form>
        @endcan
        @endif
    </div>
</div>
