<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Error</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{asset('plugins/fontawesome-free/css/all.min.css')}}">
    <link rel="stylesheet" href="{{asset('dist/css/adminlte.min.css')}}">

    <style>
        body { background-color: #f4f6f9; }
        .error-page-centered {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .text-uwezo-primary { color: #b72a9f !important; }
    </style>
</head>
<body class="hold-transition">
    <div class="error-page-centered">
        @yield('content')
    </div>

    <script src="{{asset('plugins/jquery/jquery.min.js')}}"></script>

    <script src="{{asset('plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

    @stack('scripts')
</body>
</html>
