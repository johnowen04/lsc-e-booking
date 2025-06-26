@props(['title' => 'Sport Booking'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-gray-100 text-gray-900 min-h-screen antialiased">

    <div class="max-w-2xl mx-auto p-6">
        {{ $slot }}
    </div>

    @livewireScripts
</body>

</html>
