<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Acceso' }} · ClientFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAFAF7] font-sans text-[#111827] antialiased">
    <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
        <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight">ClientFlow</a>
        <nav class="text-sm text-[#6B7280]">
            {{ $nav ?? '' }}
        </nav>
    </header>

    <main class="mx-auto flex min-h-[calc(100vh-96px)] max-w-6xl items-center justify-center px-6 py-10">
        <section class="w-full max-w-md rounded-[28px] border border-[#E7E2D8] bg-white p-8 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            {{ $slot }}
        </section>
    </main>
</body>
</html>
