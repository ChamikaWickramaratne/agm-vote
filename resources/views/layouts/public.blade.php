<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name','Laravel') }}</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css','resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100">
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <h1 class="text-lg font-semibold">{{ config('app.name') }}</h1>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
