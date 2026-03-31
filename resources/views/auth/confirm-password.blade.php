<x-guest-layout>
    <div class="row no-gutters min-vh-100">
        <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-white border-right">
            <div class="text-center p-5">
                <div class="d-inline-block p-4 mb-4 rounded-circle shadow-lg border-0 bg-white" style="width: 160px; height: 160px;">
                    <img src="{{ asset('images/uwezo_fund_logo.png') }}" class="img-fluid" alt="Uwezo Logo">
                </div>
                <h1 class="display-4 font-weight-bold text-navy mb-0">Security <span class="text-primary">Check</span></h1>
                <div class="mx-auto my-3" style="width: 60px; height: 4px; background: var(--uwezo-gold); border-radius: 2px;"></div>
                <p class="text-muted lead px-lg-5">
                    Identity Verification <br>
                    <span class="text-navy font-weight-bold">Please confirm your credentials.</span>
                </p>
            </div>
        </div>

        <div class="col-md-6 d-flex align-items-center justify-content-center bg-light p-4">
            <div class="col-12 col-md-10 col-lg-8 col-xl-7">
                <div class="card card-outline card-primary shadow-xl border-0 rounded-xl overflow-hidden animate__animated animate__fadeInRight">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="mb-3 text-warning">
                                <i class="fas fa-shield-alt fa-3x"></i>
                            </div>
                            <h3 class="font-weight-bold text-dark">Secure Area</h3>
                            <p class="text-muted small">Please confirm your password to access this sensitive section.</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger border-0 shadow-sm animate__animated animate__shakeX mb-4"
                                 style="background-color: #fff5f5; border-left: 4px solid #dc3545 !important;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-circle text-danger mr-3"></i>
                                    <span class="text-danger small">{{ $errors->first() }}</span>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.confirm') }}">
                            @csrf

                            <div class="form-group mb-4">
                                <label class="text-xs font-weight-bold text-uppercase text-muted ml-1">Current Password</label>
                                <div class="input-group premium-input-wrapper" x-data="{ show: false }">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-lock text-primary"></i></span>
                                    </div>
                                    <input :type="show ? 'text' : 'password'" name="password"
                                           class="form-control border-0 shadow-none bg-transparent"
                                           placeholder="Type your password" required autofocus>
                                    <div class="input-group-append">
                                        <button type="button" @click="show = !show" class="btn btn-transparent text-muted px-3 border-0">
                                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg font-weight-bold py-3 transform transition hover:translate-y-px">
                                Verify & Continue <i class="fas fa-user-shield ml-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
