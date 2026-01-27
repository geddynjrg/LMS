<div>
    @if($isOpen)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);" wire:click.self="$set('isOpen', false)">
            <div class="modal-dialog modal-lg modal-dialog-scrollable shadow-sm">
                <div class="modal-content border-0">
                    <div class="modal-header bg-gradient-cyan text-white">
                        <h4 class="modal-title">
                            <i class="fas fa-user-plus mr-2"></i> Register New Staff
                        </h4>
                        <button type="button" class="close text-white" wire:click="$set('isOpen', false)">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger shadow-sm border-left-danger">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <strong class="text-uppercase small">Please fix the following errors:</strong>
                                </div>
                                <ul class="mb-0 px-3 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form wire:submit.prevent="store">
                            <div class="row">
                                <div class="col-md-12">
                                    <h6 class="text-uppercase text-muted font-weight-bold small mb-3">
                                        <i class="fas fa-id-card mr-1"></i> Staff Personal Details
                                    </h6>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold">Full Name</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="fas fa-user text-muted"></i></span>
                                        </div>
                                        <input type="text" wire:model="name" class="form-control border-left-0" placeholder="Enter full name">
                                    </div>
                                    @error('name') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold">ID / Passport Number</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="fas fa-fingerprint text-muted"></i></span>
                                        </div>
                                        <input type="text" wire:model="id_number" class="form-control border-left-0" placeholder="National ID">
                                    </div>
                                    @error('id_number') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold">Official Email</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="fas fa-envelope text-muted"></i></span>
                                        </div>
                                        <input type="email" wire:model="email" class="form-control border-left-0" placeholder="email@uwezo.go.ke">
                                    </div>
                                    @error('email') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold">Phone Number</label>
                                    <div class="input-group shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light font-weight-bold text-muted">+254</span>
                                        </div>
                                        <input type="text"
                                            id="phone_mask"
                                            wire:model.defer="phone_number"
                                            class="form-control"
                                            placeholder="712345678"
                                            data-inputmask="'mask': '999999999'"
                                            data-mask>
                                    </div>
                                    @error('phone_number') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="row">
                                <div class="col-md-12">
                                    <h6 class="text-uppercase text-muted font-weight-bold small mb-3">
                                        <i class="fas fa-shield-alt mr-1"></i> Access & Security
                                    </h6>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold">Assigned Role</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="fas fa-user-tag text-muted"></i></span>
                                        </div>
                                        <select wire:model.live="selected_role" class="form-control border-left-0">
                                            <option value="">-- Assign Role --</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->name }}">{{ strtoupper(str_replace('_', ' ', $role->name)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('selected_role') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold">Temporary Password</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="fas fa-key text-muted"></i></span>
                                        </div>
                                        <input type="text" wire:model="password" class="form-control border-left-0" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" wire:click="generatePassword" title="Regenerate">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted extra-small">User will be required to change this on first login.</small>
                                </div>
                            </div>

                            @if(auth()->user()->hasRole('constituency_officer'))
                                <div class="col-md-12 form-group mt-3 animate__animated animate__fadeIn">
                                    <label class="small font-weight-bold text-primary">
                                        <i class="fas fa-users-cog mr-1"></i> Assign Intern Jurisdictions
                                    </label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="fas fa-map-marked-alt text-muted"></i></span>
                                        </div>
                                        <select wire:model.live="selected_constituencies"
                                                class="form-control border-left-0 shadow-sm"
                                                multiple>
                                            @foreach($available_constituencies as $con)
                                                <option value="{{ $con->constituency_code }}" class="py-2 px-3 border-bottom">
                                                    {{ strtoupper($con->constituency_name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-2 bg-light p-2 rounded border">
                                        <small class="text-muted italic">
                                            Hold <b>Ctrl / Cmd</b> to assign multiple areas to this intern.
                                        </small>
                                        <span class="badge badge-primary px-2 py-1">
                                            {{ count($selected_constituencies) }} Selected
                                        </span>
                                    </div>
                                    @error('selected_constituencies') <span class="text-danger extra-small d-block mt-1">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if($selected_role == 'constituency_officer')
                                <div class="row mt-3 animate__animated animate__fadeIn">
                                    <div class="col-md-12">
                                        <h6 class="text-uppercase text-muted font-weight-bold small mb-3">
                                            <i class="fas fa-map-marker-alt mr-1"></i> Area of Jurisdiction
                                        </h6>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label class="small font-weight-bold text-primary">Assign Jurisdiction</label>
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white"><i class="fas fa-map-marked text-muted"></i></span>
                                            </div>
                                            <select wire:model.live="selected_county" class="form-control border-left-0">
                                                <option value="">-- Select County --</option>
                                                @foreach($all_counties as $c)
                                                    <option value="{{ $c->county_code }}">{{ $c->county_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('selected_county') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                                    </div>

                                    @if($selected_county)
                                    <div class="col-md-6 form-group animate__animated animate__fadeIn">
                                        <label class="small font-weight-bold text-primary">Assigned Constituencies</label>
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white"><i class="fas fa-city text-muted"></i></span>
                                            </div>
                                            <select wire:model.live="selected_constituencies" class="form-control border-left-0" multiple>
                                                @foreach($available_constituencies as $con)
                                                    <option value="{{ $con->constituency_code }}" class="py-2 px-3 border-bottom">
                                                        {{ strtoupper($con->constituency_name) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('selected_constituencies') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="text-muted italic">Hold <b>Ctrl</b> (or Cmd) to select multiple</small>
                                            <small class="text-primary font-weight-bold">Selected: {{ count($selected_constituencies) }}</small>
                                        </div>
                                    </div>

                                    @endif

                                </div>
                            @endif

                            <div class="modal-footer px-0 pb-0 mt-4 border-top-0">
                                <button type="button" class="btn btn-outline-secondary" wire:click="$set('isOpen', false)">Cancel</button>
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="fas fa-check-double mr-1"></i> Confirm and Create
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
