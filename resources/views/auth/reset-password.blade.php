<x-guest-layout>
    <div class="row no-gutters min-vh-100">
        <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-white border-right">
            <div class="text-center p-5">
                <div class="d-inline-block p-4 mb-4 rounded-circle shadow-lg border-0 bg-white" style="width: 160px; height: 160px;">
                    <img src="{{ asset('images/uwezo_fund_logo.png') }}" class="img-fluid" alt="Uwezo Logo">
                </div>
                <h1 class="display-4 font-weight-bold text-navy mb-0">Uwezo <span class="text-primary">Portal</span></h1>
                <div class="mx-auto my-3" style="width: 60px; height: 4px; background: var(--uwezo-gold); border-radius: 2px;"></div>
                <p class="text-muted lead px-lg-5">
                    Secure Account Recovery <br>
                    <span class="text-navy font-weight-bold">Update your credentials.</span>
                </p>
            </div>
        </div>

        <div class="col-md-6 d-flex align-items-center justify-content-center bg-light p-4">
            <div class="col-12 col-md-10 col-lg-8 col-xl-7">
                <div class="card card-outline card-primary shadow-xl border-0 rounded-xl overflow-hidden animate__animated animate__fadeInRight">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-5">
                            <h3 class="font-weight-bold text-dark">Set New Password</h3>
                            <p class="text-muted small uppercase tracking-widest">Ensure your account stays secure</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger border-0 shadow-sm animate__animated animate__shakeX mb-4"
                                 style="background-color: #fff5f5; border-left: 4px solid #dc3545 !important;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle text-danger mr-3"></i>
                                    <div>
                                        <strong class="text-danger d-block">Validation Error</strong>
                                        <span class="text-muted small">{{ $errors->first() }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf

                            <input type="hidden" name="token" value="{{ $request->route('token') }}">

                            <div class="form-group mb-4">
                                <label class="text-xs font-weight-bold text-uppercase text-muted ml-1">Account Email</label>
                                <div class="input-group premium-input-wrapper">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-envelope text-primary"></i></span>
                                    </div>
                                    <input type="email" name="email" value="{{ old('email', $request->email) }}"
                                           class="form-control border-0 shadow-none bg-transparent" required readonly>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-xs font-weight-bold text-uppercase text-muted ml-1">New Password</label>
                                <div class="input-group premium-input-wrapper" x-data="{ show: false }">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-key text-primary"></i></span>
                                    </div>
                                    <input :type="show ? 'text' : 'password'" name="password"
                                           class="form-control border-0 shadow-none bg-transparent" placeholder="Minimum 8 characters" required>
                                    <div class="input-group-append">
                                        <button type="button" @click="show = !show" class="btn btn-transparent text-muted px-3 border-0">
                                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-xs font-weight-bold text-uppercase text-muted ml-1">Confirm New Password</label>
                                <div class="input-group premium-input-wrapper" x-data="{ show: false }">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-check-double text-primary"></i></span>
                                    </div>
                                    <input :type="show ? 'text' : 'password'" name="password_confirmation"
                                           class="form-control border-0 shadow-none bg-transparent" placeholder="Repeat password" required>
                                    <div class="input-group-append">
                                        <button type="button" @click="show = !show" class="btn btn-transparent text-muted px-3 border-0">
                                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg font-weight-bold py-3 mt-2 transform transition hover:translate-y-px">
                                Reset Password <i class="fas fa-sync-alt ml-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
