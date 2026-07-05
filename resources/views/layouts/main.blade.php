<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('images/uwezo_fund_logo.png') }}">

        <!-- Google Font: Source Sans Pro -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="{{asset('plugins/fontawesome-free/css/all.min.css')}}">

        <!-- overlayScrollbars -->
        <link rel="stylesheet" href="{{asset('plugins/overlayScrollbars/css/OverlayScrollbars.min.css')}}">
        @stack('styles')
        <!-- Theme style -->
        <link rel="stylesheet" href="{{asset('dist/css/adminlte.min.css')}}">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

        {{-- toastr --}}
        <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">

        {{-- select 2 --}}
        <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.css') }}">


        <!-- DataTables style -->
        <link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
        <link rel="stylesheet" href="{{asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
        <link rel="stylesheet" href="{{asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css')}}">

        @php
            $viteAssets = ['resources/css/app.css', 'resources/css/hq-dashboard-v2.css', 'resources/js/app.js'];
            if (config('applications.review_v2_enabled')) {
                $viteAssets[] = 'resources/css/application-review-v2.css';
            }
        @endphp
        @vite($viteAssets)

        {{-- livewire styles --}}
        @livewireStyles

        <style>
    /* Modern Pill Breadcrumbs */

    .btn-white {
        background-color: #ffffff;
        color: #4b545c;
        transition: all 0.2s ease;
    }

    .btn-white:hover {
        background-color: #f8f9fa;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
    }

    /* Subtle Navy highlights */
    .text-navy { color: #001f3f; }

    /* Loading Bar for  */
    .reloading-bar {
        height: 3px;
        background: #007bff;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 9999;
        animation: loading-bar 2s infinite;
    }
</style>

        <style>


            /* 1. The Large Display Size (e.g., for Loan Amounts) */
            .text-responsive-xl {
                font-size: clamp(1.5rem, 5vw, 2.5rem);
                line-height: 1.2;
            }

            /* 2. The Header Size (e.g., for Group Name, Section Headers) */
            .text-responsive-lg {
                font-size: clamp(1.1rem, 3vw, 1.5rem);
                line-height: 1.3;
            }

            /* 3. The Standard Data Size (e.g., Registration No, Years of Experience) */
            .text-responsive-md {
                font-size: clamp(0.95rem, 2.5vw, 1.1rem);
            }

            /* 4. The Label Size (e.g., "Monthly Income", "Nature of Group") */
            .text-responsive-sm {
                font-size: clamp(0.75rem, 2vw, 0.9rem);
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            /* Apply these to your existing hover cards */
            .card-custom-hover {
                transition: all 0.3s ease;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
            }
        </style>
        {{-- custom styles --}}
        <style>
            :root {
                --uwezo-blue: #003366;   /* Deep Navy */
                --uwezo-gold: #FFCC00;   /* Gold Accent */
                --uwezo-light: #f8f9fa;
            }

            .uwezo-card { border-top: 3px solid var(--uwezo-blue) !important; }
            .btn-uwezo { background-color: var(--uwezo-blue); color: white; border: none; }
            .btn-uwezo:hover { background-color: #002244; color: #FFCC00; }
            .text-uwezo { color: var(--uwezo-blue) !important; }
            .badge-uwezo-outline {
                border: 1px solid var(--uwezo-blue);
                color: var(--uwezo-blue);
                background: transparent;
            }
            .livewire-progress-bar {
                position: fixed;
                top: 47px;
                left: 0;
                right: 0;
                height: 3px;
                box-shadow: 0 1px 2px rgba(23, 162, 184, 0.3);
                width: 0;
                background-color: #27588a;
                z-index: 1039;
                transition: width 300ms ease-out, opacity 150ms;
                opacity: 0;
            }

            .navbar-search-block-open {
                display: flex !important;
                transform: translate3d(0, 0, 0);
                visibility: visible !important;
            }

            /* Adjust the progress bar if the sidebar is collapsed or on mobile if needed */
            @media (max-width: 767.98px) {
                .livewire-progress-bar {
                    top: 47px; /* AdminLTE mobile navbar height is usually the same */
                }
            }
        </style>
        <style>
            /* Make the content wrapper the boundary for the loader */
            .container-fluid {
                position: relative; /* Ensures the loader has space if content is empty */
            }

            .loading-overlay {
                position: absolute; /* Changed from fixed */
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(1px);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000; /* High enough to cover content but stay under the Navbar */
            }

            .dancing-loader {
                width: 50px;
                height: 50px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #007bff;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            /* Container Styling */
            .premium-toast {
                background: rgba(214, 220, 224, 0.637) !important;
                backdrop-filter: blur(8px);
                border-radius: 16px !important;
                border: 1px solid rgba(0, 0, 0, 0.05) !important;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
                padding: 0.75rem 1rem !important;
            }
  /* Progress Bar Styling */
    .premium-toast-progress {
        height: 3px !important;
        background: rgba(0, 0, 0, 0.05) !important;
    }

    /* Typography Refinements */
    .premium-toast-title {
        color: #1e293b !important; /* Slate 800 */
        font-weight: 700 !important;
        font-size: 1rem !important;
        margin-bottom: 2px !important;
    }

    .premium-toast-text {
        color: #64748b !important; /* Slate 500 */
        font-size: 0.85rem !important;
    }

    /* Icon Polish */
    .swal2-icon {
        animation: pulse-icon 2s infinite;
    }
    @keyframes pulse-icon {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
</style>
        <style>
            /* Modern Styling */

            .bg-light-pink { background-color: #fff1f2; }
            .text-pink { color: #e11d48 !important; }
            .bg-pink { background-color: #e11d48 !important; }

            .bg-light-orange { background-color: #fff7ed; }
            .bg-light-purple { background-color: #f5f3ff; }
            .bg-light-primary { background-color: #eff6ff; }

            .truncate-text {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .text-xs { font-size: 0.7rem; letter-spacing: 0.5px; }
        </style>
    </head>
    <body class="hold-transition sidebar-mini layout-fixed text-sm layout-footer-fixed layout-navbar-fixed sidebar-collapse">

            <div class="wrapper">
                @auth
                    <livewire:constituency-officer-guide-modal />

                    <!-- Navbar -->
                    @include('nav-bar')
                    <!-- /.navbar -->

                    <!-- Main Sidebar Container -->
                    <livewire:side-nav />

                    <!-- Content Wrapper. Contains page content -->
                    <div class="content-wrapper">
                        <!-- Main content -->
                        @yield('main-content')


                    </div>
                    <!-- /.content-wrapper -->

                    <!-- Control Sidebar -->
                    @include('rigt-side-bar')
                    <!-- /.control-sidebar -->

                    <!-- Main Footer -->
                    <footer class="main-footer footer-bg">
                        <!-- To the right -->
                        <div class="float-right d-none d-sm-inline">
                            Uwezo Fund
                        </div>
                        <!-- Default to the left -->
                        <strong>Copyright &copy; 2025-2026 <a class="text-white" href="https://uwezo.go.ke">UWEZO FUND</a>.</strong> All rights reserved.
                    </footer>
                @endauth

            </div>


        <!-- ./wrapper -->

        <!--SCRIPTS -->


        <!-- jQuery -->
        <script src="{{asset('plugins/jquery/jquery.min.js')}}"></script>
        <!-- Bootstrap 4 -->
        <script src="{{asset('plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

        <!-- overlayScrollbars -->
        <script src="{{asset('plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')}}"></script>

        <!-- SweetAlert2 -->
        {{-- <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script> --}}

        {{-- toastr --}}
        <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>

        {{-- select 2 --}}
        <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
        <script>
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true, // Adds a visual countdown
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "2000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
        </script>

        <script src="{{asset('plugins/datatables/jquery.dataTables.min.js')}}"></script>
        <script src="{{asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
        <script src="{{asset('plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
        <script src="{{asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
        <script src="{{asset('plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
        <script src="{{asset('plugins/datatables-buttons/js/buttons.bootstrap4.min.js')}}"></script>



        <!-- AdminLTE App -->
        <script src="{{asset('dist/js/adminlte.min.js')}}"></script>

        @stack('scripts')

        @livewire('add-apps-to-phase-modal')

        @livewireScripts

    </body>
</html>
