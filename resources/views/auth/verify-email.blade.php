<x-guest-layout>
    <div class="row no-gutters min-vh-100">
        <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-white border-right">
            <div class="text-center p-5">
                <div class="d-inline-block p-4 mb-4 rounded-circle shadow-lg border-0 bg-white" style="width: 160px; height: 160px;">
                    <img src="{{ asset('images/uwezo_fund_logo.png') }}" class="img-fluid" alt="Uwezo Logo">
                </div>
                <h1 class="display-4 font-weight-bold text-navy mb-0">Verify <span class="text-primary">Email</span></h1>
                <div class="mx-auto my-3" style="width: 60px; height: 4px; background: var(--uwezo-gold); border-radius: 2px;"></div>
                <p class="text-muted lead px-lg-5">
                    One last step... <br>
                    <span class="text-navy font-weight-bold">Confirm your official email address.</span>
                </p>
            </div>
        </div>

        <div class="col-md-6 d-flex align-items-center justify-content-center bg-light p-4">
            <div class="col-12 col-md-10 col-lg-8 col-xl-7">
                <div class="card card-outline card-primary shadow-xl border-0 rounded-xl overflow-hidden animate__animated animate__fadeInRight">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="mb-3 text-primary animate__animated animate__envelope">
                                <i class="fas fa-envelope-open-text fa-3x"></i>
                            </div>
                            <h3 class="font-weight-bold text-dark">Check Your Inbox</h3>
                            <p class="text-muted small">
                                Before continuing, please verify your email by clicking the link we just sent you.
                                If you didn't receive it, we can send another.
                            </p>
                        </div>

                        @if (session('status') == 'verification-link-sent')
                            <div class="alert alert-success border-0 shadow-sm animate__animated animate__fadeInDown mb-4"
                                 style="background-color: #f0fff4; border-left: 4px solid #28a745 !important;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-paper-plane text-success mr-3"></i>
                                    <span class="text-success small font-weight-bold">A new link has been sent to your email. {{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg font-weight-bold py-3 transform transition hover:translate-y-px">
                                Resend Verification Email <i class="fas fa-sync-alt ml-2"></i>
                            </button>
                        </form>
                    </div>

                    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center pb-4 px-4">
                        {{-- <a href="{{ route('profile.show') }}" class="text-sm font-weight-bold text-muted text-decoration-none">
                            <i class="fas fa-user-edit mr-1"></i> Edit Profile
                        </a> --}}

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-link text-sm font-weight-bold text-danger text-decoration-none">
                                <i class="fas fa-sign-out-alt mr-1"></i> Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
