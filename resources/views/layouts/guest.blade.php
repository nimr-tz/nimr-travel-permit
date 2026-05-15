<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'NIMR Travel Permit') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">

<div class="min-h-screen flex flex-col items-center justify-center py-12 px-4">

    <div class="mb-6 text-center">
        <img src="{{ asset('NIMR.png') }}" alt="NIMR" class="h-12 w-12 rounded-xl object-contain mb-3">
        <h1 class="text-lg font-bold text-gray-900">Taasisi ya Taifa ya Utafiti wa Magonjwa ya Binadamu</h1>
        <p class="text-sm text-gray-500 mt-1">Mfumo wa Ruhusa ya Kusafiri Ndani ya Nchi</p>
    </div>

    <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        {{ $slot }}
    </div>

</div>

</body>
</html>
