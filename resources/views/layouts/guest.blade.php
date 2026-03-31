<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} | Login</title>

    <link rel="icon" type="image/png" href="{{ asset('images/uwezo_fund_logo.png') }}">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <link rel="stylesheet" href="{{asset('plugins/fontawesome-free/css/all.min.css')}}">

    <link rel="stylesheet" href="{{asset('dist/css/adminlte.min.css')}}">
    <link rel="stylesheet" href="{{asset('dist/css/custom.css')}}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    @vite(['resources/css/app.css','resources/js/app.js'])

    @livewireStyles

    <style>
        :root {
            --uwezo-blue: #003366;
            --uwezo-gold: #FFCC00;
        }

        body { font-family: 'Source Sans Pro', sans-serif; }

        /* Custom Split Screen Styles */

        /* Re-using your premium input group logic */
        .premium-input-wrapper {
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .premium-input-wrapper:focus-within {
            border-color: var(--uwezo-blue);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 51, 102, 0.1);
        }

        .login-card-entrance {
            animation: fadeInUp 0.6s ease-out;
        }

        .card-outline-uwezo:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 30px -5px rgba(0, 0, 0, 0.15) !important;
        }


        /* Essential fix for AdminLTE content wrapper on guest pages */
        .content-wrapper { margin-left: 0 !important; }

        /* Smooth shadow for the premium look */
        .shadow-xl {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }

        .rounded-xl { border-radius: 1rem !important; }

        /* Button Hover
        .btn-primary { background-color: var(--uwezo-blue); border-color: var(--uwezo-blue); }
        .btn-primary:hover { background-color: #002244; transform: translateY(-1px); }

        .btn-primary:active {
            transform: scale(0.98);
        } */

        .btn, .form-control, .premium-input-wrapper, .card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
    </style>
</head>
<body class="hold-transition">
    <div class="content-wrapper m-0 p-0 bg-white" style="min-height: 100vh !important;">
        <div class="animate__animated animate__fadeIn animate__faster">
            {{ $slot }}
        </div>
    </div>

    <script src="{{asset('plugins/jquery/jquery.min.js')}}"></script>
    <script src="{{asset('plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

    @livewireScripts
</body>
</html>
