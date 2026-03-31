<x-guest-layout>
    <div class="row no-gutters min-vh-100" x-data="{ recovery: false }">
        <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-white border-right">
            <div class="text-center p-5">
                <div class="d-inline-block p-4 mb-4 rounded-circle shadow-lg border-0 bg-white" style="width: 160px; height: 160px;">
                    <img src="{{ asset('images/uwezo_fund_logo.png') }}" class="img-fluid" alt="Uwezo Logo">
                </div>
                <h1 class="display-4 font-weight-bold text-navy mb-0">Two-Factor</h1>
                <h2 class="h3 text-primary">Authentication</h2>
                <div class="mx-auto my-3" style="width: 60px; height: 4px; background: var(--uwezo-gold); border-radius: 2px;"></div>
                <p class="text-muted lead px-lg-5">
                    Extra layer of protection <br>
                    <span class="text-navy font-weight-bold">Securing your official access.</span>
                </p>
            </div>
        </div>

        <div class="col-md-6 d-flex align-items-center justify-content-center bg-light p-4">
            <div class="col-12 col-md-10 col-lg-8 col-xl-7">
                <div class="card card-outline card-primary shadow-xl border-0 rounded-xl overflow-hidden animate__animated animate__fadeInRight">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="mb-3 text-primary">
                                <i class="fas fa-mobile-alt fa-3x" x-show="!recovery"></i>
                                <i class="fas fa-file-invoice fa-3x" x-show="recovery" x-cloak></i>
                            </div>
                            <h3 class="font-weight-bold text-dark" x-show="!recovery">Verify Access</h3>
                            <h3 class="font-weight-bold text-dark" x-show="recovery" x-cloak>Recovery Mode</h3>

                            <p class="text-muted small" x-show="!recovery">
                                Enter the 6-digit code from your authenticator app.
                            </p>
                            <p class="text-muted small" x-show="recovery" x-cloak>
                                Enter one of your emergency recovery codes.
                            </p>
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

                        <form method="POST" action="{{ route('two-factor.login') }}">
                            @csrf

                            <div class="form-group mb-4" x-show="!recovery">
                                <label class="text-xs font-weight-bold text-uppercase text-muted ml-1">Authentication Code</label>
                                <div class="input-group premium-input-wrapper">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-key text-primary"></i></span>
                                    </div>
                                    <input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code"
                                           class="form-control border-0 shadow-none bg-transparent" placeholder="000000">
                                </div>
                            </div>

                            <div class="form-group mb-4" x-show="recovery" x-cloak>
                                <label class="text-xs font-weight-bold text-uppercase text-muted ml-1">Recovery Code</label>
                                <div class="input-group premium-input-wrapper">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-shield-alt text-primary"></i></span>
                                    </div>
                                    <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code"
                                           class="form-control border-0 shadow-none bg-transparent" placeholder="xxxxx-xxxxx">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg font-weight-bold py-3 transform transition hover:translate-y-px">
                                Authenticate <i class="fas fa-check-double ml-2"></i>
                            </button>
                        </form>
                    </div>

                    <div class="card-footer bg-white border-0 text-center pb-4">
                        <button type="button" class="btn btn-link text-sm font-weight-bold text-primary text-decoration-none"
                                x-show="!recovery"
                                x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                            <i class="fas fa-life-ring mr-1"></i> Use a recovery code
                        </button>

                        <button type="button" class="btn btn-link text-sm font-weight-bold text-primary text-decoration-none"
                                x-show="recovery" x-cloak
                                x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                            <i class="fas fa-mobile-alt mr-1"></i> Use an authentication code
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
