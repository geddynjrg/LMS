<x-guest-layout>
    <div class="row no-gutters min-vh-100">
        <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-white border-right">
            <div class="text-center p-5">
                <div class="d-inline-block p-4 mb-4 rounded-circle shadow-lg border-0 bg-white" style="width: 160px; height: 160px;">
                    <img src="{{ asset('images/uwezo_fund_logo.png') }}" class="img-fluid" alt="Uwezo Logo">
                </div>
                <h1 class="display-4 font-weight-bold text-navy mb-0">Uwezo <span class="text-primary">Loan Portal</span></h1>
                <div class="mx-auto my-3" style="width: 60px; height: 4px; background: var(--uwezo-gold); border-radius: 2px;"></div>
                <p class="text-muted lead px-lg-5">
                    Strategic financial inclusion for <br>
                    <span class="text-navy font-weight-bold">Women, Youth & PWDs.</span>
                </p>
                <div class="mt-5 d-flex justify-content-center flex-wrap">
                    <span class="badge badge-pill badge-light border px-3 py-2 mx-1 shadow-sm"><i class="fas fa-shield-alt text-primary mr-1"></i> Secure</span>
                    <span class="badge badge-pill badge-light border px-3 py-2 mx-1 shadow-sm"><i class="fas fa-check-double text-success mr-1"></i> Verified</span>
                    <span class="badge badge-pill badge-light border px-3 py-2 mx-1 shadow-sm"><i class="fas fa-university text-warning mr-1"></i> Government</span>
                </div>
            </div>
        </div>

        <div class="col-md-6 d-flex align-items-center justify-content-center bg-light p-4">
            <div class="col-12 col-md-10 col-lg-8 col-xl-7">
                <div class="card card-outline card-primary shadow-xl border-0 rounded-xl overflow-hidden3 animate__animated animate__fadeInRight">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-5">
                            <h3 class="font-weight-bold text-dark">Portal Login</h3>
                            <p class="text-muted small uppercase tracking-widest">Authorized Personnel Only</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger border-0 shadow-sm animate__animated animate__shakeX mb-4"
                                style="background-color: #fff5f5; border-left: 4px solid #dc3545 !important;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-circle text-danger mr-3" style="font-size: 1.2rem;"></i>
                                    <div>
                                        <strong class="text-danger d-block">Authentication Failed</strong>
                                        <span class="text-muted small">{{ $errors->first() }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="form-group mb-4">
                                <label class="text-xs font-weight-bold text-uppercase text-muted ml-1">Official Email</label>
                                <div class="input-group premium-input-wrapper {{ $errors->has('email') ? 'border-danger' : '' }}">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-transparent border-0">
                                            <i class="fas fa-envelope {{ $errors->has('email') ? 'text-danger' : 'text-primary' }}"></i>
                                        </span>
                                    </div>
                                    <input type="email" name="email" value="{{ old('email') }}"
                                        class="form-control border-0 shadow-none bg-transparent"
                                        placeholder="name@uwezo.go.ke" required autofocus>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-xs font-weight-bold text-uppercase text-muted ml-1">Secret Key</label>
                                <div class="input-group premium-input-wrapper {{ $errors->has('email') ? 'border-danger' : '' }}" x-data="{ show: false }">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-transparent border-0">
                                            <i class="fas fa-lock {{ $errors->has('email') ? 'text-danger' : 'text-primary' }}"></i>
                                        </span>
                                    </div>
                                    <input :type="show ? 'text' : 'password'" name="password"
                                        class="form-control border-0 shadow-none bg-transparent"
                                        placeholder="••••••••" required>
                                    <div class="input-group-append">
                                        <button type="button" @click="show = !show" class="btn btn-transparent text-muted px-3 border-0">
                                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-4 mt-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="remember_me" name="remember">
                                    <label class="custom-control-label text-sm text-muted cursor-pointer" for="remember_me">Keep me logged in</label>
                                </div>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-sm font-weight-bold text-primary">Forgot Password?</a>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-outline-primary btn-block btn-lg shadow-lg font-weight-bold py-3 transform transition hover:translate-y-px">
                                Enter Portal <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </form>
                    </div>

                    <div class="card-footer bg-white border-0 text-center pb-4">
                        <span class="text-xs text-muted">Protected by 256-bit encryption</span>
                    </div>
                </div>

                <p class="mt-4 text-center text-xs text-muted">
                    © {{ date('Y') }} Uwezo Fund | <strong>v4.0.2</strong>
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
