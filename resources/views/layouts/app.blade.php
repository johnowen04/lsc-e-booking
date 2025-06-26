<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Sport Booking' }}</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    @livewireStyles
</head>

<body class="bg-gray-100 text-gray-900 min-h-screen antialiased">
    <div class="max-w-2xl mx-auto p-6">
        @yield('content')
    </div>

    @livewireScripts
</body>

</html>
