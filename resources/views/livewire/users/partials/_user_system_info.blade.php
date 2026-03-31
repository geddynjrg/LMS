
<div class="row mt-4" wire:key="settings-system-{{ $user->id }}">
    <div class="col-md-5">
        <h5 class="text-info"><i class="fas fa-cogs mr-2"></i> System Context</h5>
        <p class="text-muted small">Manage organizational affiliation, assigned roles, and administrative status.</p>
    </div>
    <div class="col-md-7">
        <div class="card shadow-none border rounded-lg">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Assigned Role <span class="text-danger">*</span></label>
                        <x-dropdown-search
                            model="state.role"
                            :options="$roleOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="select role"
                            icon="fas fa-user-tag"
                        />
                        @error('state.role') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                    </div>


                    @if(in_array($state['role'], ['constituency_officer', 'intern']))
                        <div class="col-md-12 mb-4 animate__animated animate__fadeI">
                            <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Assigned County <span class="text-danger">*</span></label>
                            <x-dropdown-search
                                model="selectedCounty"
                                :options="$countyOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="select county"
                                icon="fas fa-user-tag"
                            />
                            @error('selectedCounty') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                        </div>


                        <div class="col-md-12 mb-4" wire:key="constituency-wrapper-user-{{ $selectedCounty }}">
                            @if(!empty($constituencyOptions) && isset($selectedCounty))
                                <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Assigned Constituency <span class="text-danger">*</span></label>
                                <x-dropdown-multiselect
                                    wire:key="dropdown-user-ds-{{ $state['county'] }}-{{ count($constituencyOptions) }}-{{ md5(json_encode($constituencyOptions)) }}"
                                    model="selectedConstituencies"
                                    :options="$constituencyOptions"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Select Constituencies"
                                    icon="fas fa-map-marked-alt"
                                />
                                @error('selectedConstituencies') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                            @else
                                <label class="text-uppercase font-weight-black small tracking-wider mb-2 text-muted ml-1 opacity-50">Assigned Constituencies</label>
                                <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                    <div wire:loading wire:target="updatedSelectedCounty" class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                    <small class="text-muted" wire:loading.remove wire:target="updatedSelectedCounty">Select a county first...</small>
                                    <small class="text-muted" wire:loading wire:target="updatedSelectedCounty">Loading constituencies...</small>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-light text-right">
                <x-pill-button
                    label="Update User Jurisdiction"
                    color="info"
                    target="updateUserJurisdiction"
                    icon="fas fa-sync-alt"
                    class="btn-sm shadow-sm"
                />
            </div>
        </div>
    </div>
</div>
