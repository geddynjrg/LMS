<div class="row" wire:key="settings-account-info-{{ $user->id }}">
    <div class="col-md-5">
        <h5 class="text-info"><i class="fas fa-user-circle mr-2"></i> Account Details</h5>
        <p class="text-muted small">Update the user's primary contact information and login credentials.</p>
    </div>
    <div class="col-md-7">
        <div class="card shadow-none border" style="border-radius: 15px;">
            <div class="card-body p-4">
                <div class="row">
                    {{-- Name Field --}}
                    <div class="col-md-12 mb-4">
                        <x-premium-input
                            model="state.name"
                            label="Full Name"
                            placeholder="John Doe"
                            icon="fas fa-user"
                            required="true"
                        />
                    </div>
                    {{-- Email Field --}}
                    <div class="col-md-12 mb-4">
                        <x-premium-input
                            model="state.email"
                            type="email"
                            label="Email Address"
                            placeholder="example@uwezo.go.ke"
                            icon="fas fa-envelope"
                            required="true"
                        />
                    </div>
                    {{-- ID Field --}}
                    <div class="col-md-12 mb-4">
                        <x-premium-input
                            model="state.idNumber"
                            type="number"
                            label="National ID"
                            placeholder="1234567"
                            icon="fas fa-fingerprint"
                            required="true"
                        />
                    </div>
                    {{-- Phone Number Field --}}
                    <div class="col-md-12 mb-4">
                        <x-premium-input
                            model="state.phoneNumber"
                            type="number"
                            label="Phone Number"
                            placeholder="0721756098"
                            icon="fas fa-phone"
                            required="true"
                        />
                    </div>

                    {{-- Password Field with Generate Button --}}
                    <div class="col-md-12">
                        <div class="position-relative">
                            <x-premium-input
                                model="state.password"
                                type="password"
                                label="New Password"
                                placeholder="Leave blank to keep current"
                                icon="fas fa-lock"
                            />

                            {{-- Floating Generate Button --}}
                            <x-pill-button
                                    target="generatePassword"
                                    color="info"
                                    class="position-absolute"
                                    style="top: 0; right: 0; font-size: 0.7rem; border-radius: 8px;"
                                    icon="fas fa-sync-alt"
                                    label="Generate"
                                    size="btn-xs"/>
                        </div>
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle mr-1"></i> Passwords must be at least 8 characters.
                        </small>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light text-right">
                <x-pill-button
                    label="Update User Details"
                    color="info"
                    target="updateUserInfo"
                    icon="fas fa-sync-alt"
                    class="btn-sm shadow-sm"
                />
            </div>
        </div>
    </div>
</div>
