<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'NIMR Travel Permit') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('NIMR.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/flag-icons@7.2.3/css/flag-icons.min.css" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">

<div class="min-h-screen flex">

    {{-- ── Left panel (NIMR brand) ─────────────────────────────────────── --}}
    <div class="hidden lg:flex lg:w-[42%] xl:w-[38%] flex-col" style="background-color: #05499c;">

        {{-- Logo + institute name — top left --}}
        <div class="px-10 xl:px-12 pt-10">
            <img src="{{ asset('NIMR.png') }}" alt="NIMR" class="h-36 w-auto object-contain">
        </div>

        {{-- System name — centred between logo and footer --}}
        <div class="flex-1 flex flex-col justify-center px-10 xl:px-12 pb-20">
            <h1 class="text-white text-5xl xl:text-6xl font-extrabold leading-tight whitespace-pre-line">
                {{ __('auth.system_name') }}
            </h1>
        </div>

        {{-- Footer --}}
        <div class="px-10 xl:px-12 pb-9">
            <p class="text-white text-xs">
                &copy; {{ date('Y') }} NIMR Tanzania &mdash; {{ __('auth.rights_reserved') }}
            </p>
        </div>

    </div>

    {{-- ── Right panel ─────────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col items-center justify-center bg-slate-50 p-6 sm:p-10 relative">

        {{-- Language toggle --}}
        <div class="absolute top-5 right-5 flex items-center rounded-lg border border-slate-200 bg-white overflow-hidden text-xs font-semibold shadow-sm">
            <a href="{{ route('locale.switch', 'sw') }}" title="Kiswahili"
               class="flex items-center gap-1.5 px-3 py-1.5 transition {{ app()->getLocale() === 'sw' ? 'text-white' : 'text-slate-500 hover:bg-slate-50' }}"
               style="{{ app()->getLocale() === 'sw' ? 'background-color:#16a34a;' : '' }}">
                <span class="fi fi-tz fis rounded-sm" style="font-size:14px;"></span>
                <span>SW</span>
            </a>
            <div class="w-px h-5 bg-slate-200"></div>
            <a href="{{ route('locale.switch', 'en') }}" title="English"
               class="flex items-center gap-1.5 px-3 py-1.5 transition {{ app()->getLocale() === 'en' ? 'text-white' : 'text-slate-500 hover:bg-slate-50' }}"
               style="{{ app()->getLocale() === 'en' ? 'background-color:#16a34a;' : '' }}">
                <span class="fi fi-gb fis rounded-sm" style="font-size:14px;"></span>
                <span>EN</span>
            </a>
        </div>

        {{-- Mobile logo --}}
        <div class="flex lg:hidden items-center gap-3 mb-8">
            <div class="h-12 w-12 rounded-xl flex items-center justify-center shadow-md bg-white border border-slate-200">
                <img src="{{ asset('NIMR.png') }}" alt="NIMR" class="h-9 w-9 object-contain">
            </div>
            <div>
                <div class="text-sm font-bold text-slate-900">NIMR</div>
                <div class="text-[10px] text-slate-500">{{ __('auth.system_name') }}</div>
            </div>
        </div>

        <div class="w-full max-w-[400px]">
            {{ $slot }}
        </div>

        {{-- Mobile footer --}}
        <p class="lg:hidden mt-8 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} NIMR Tanzania &mdash; {{ __('auth.rights_reserved') }}
        </p>

    </div>

</div>

</body>
</html>
