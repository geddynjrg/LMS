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
        <link rel="stylesheet" href="{{asset('dist/css/custom.css')}}">

          <!-- SweetAlert2 -->
        <link rel="stylesheet" href="{{ asset('plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">

        {{-- toastr --}}
        <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">

        <!-- DataTables style -->
        <link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
        <link rel="stylesheet" href="{{asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
        <link rel="stylesheet" href="{{asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css')}}">
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
        </style>
        @livewireStyles
    </head>
<body class="hold-transition sidebar-mini layout-fixed text-sm layout-footer-fixed layout-navbar-fixed ">
    <div class="wrapper">

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
        <footer class="main-footer">
            <!-- To the right -->
            <div class="float-right d-none d-sm-inline">
                Uwezo Fund
            </div>
            <!-- Default to the left -->
            <strong>Copyright &copy; 2023-2024 <a href="#">PSC</a>.</strong> All rights reserved.
        </footer>
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
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    {{-- toastr --}}
    <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
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

    @livewireScripts

</body>
</html>
