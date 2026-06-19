<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FAFAF7">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} — ClientFlow</title>

    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pwa.js'])
</head>
<body class="is-authenticated min-h-screen bg-[#FAFAF7] font-sans text-[#111827] antialiased">
    <div class="flex min-h-screen">
        @include('partials.portal-sidebar')

        <div class="flex flex-1 flex-col">
            <header class="sticky top-0 z-10 flex items-center justify-between border-b border-[#E7E2D8] bg-white/80 px-6 py-4 backdrop-blur lg:px-8">
                <h1 class="text-lg font-semibold">{{ $title }}</h1>
                @include('partials.user-menu')
            </header>

            <main class="flex-1 px-6 py-6 lg:px-8 lg:py-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @include('partials.pwa-install-prompt')

    @livewireScripts
</body>
</html>
