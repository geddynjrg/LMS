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
                    Account Recovery <br>
                    <span class="text-navy font-weight-bold">We'll help you get back in.</span>
                </p>
            </div>
        </div>

        <div class="col-md-6 d-flex align-items-center justify-content-center bg-light p-4">
            <div class="col-12 col-md-10 col-lg-8 col-xl-7">
                <div class="card card-outline card-primary shadow-xl border-0 rounded-xl overflow-hidden animate__animated animate__fadeInRight">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h3 class="font-weight-bold text-dark">Password Recovery</h3>
                            <p class="text-muted small">Enter your email to receive a secure reset link.</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success border-0 shadow-sm animate__animated animate__backInDown mb-4"
                                 style="background-color: #f0fff4; border-left: 4px solid #28a745 !important;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success mr-3" style="font-size: 1.2rem;"></i>
                                    <span class="text-success small font-weight-bold">{{ session('status') }}</span>
                                </div>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger border-0 shadow-sm animate__animated animate__shakeX mb-4"
                                 style="background-color: #fff5f5; border-left: 4px solid #dc3545 !important;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-circle text-danger mr-3"></i>
                                    <span class="text-danger small">{{ $errors->first() }}</span>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <div class="form-group mb-4">
                                <label class="text-xs font-weight-bold text-uppercase text-muted ml-1">Official Email</label>
                                <div class="input-group premium-input-wrapper">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-paper-plane text-primary"></i></span>
                                    </div>
                                    <input type="email" name="email" value="{{ old('email') }}"
                                           class="form-control border-0 shadow-none bg-transparent"
                                           placeholder="name@uwezo.go.ke" required autofocus>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg font-weight-bold py-3 transform transition hover:translate-y-px">
                                Send Reset Link <i class="fas fa-envelope-open-text ml-2"></i>
                            </button>
                        </form>
                    </div>

                    <div class="card-footer bg-white border-0 text-center pb-4">
                        <a href="{{ route('login') }}" class="text-sm font-weight-bold text-primary">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
