{{--
    Layout base para paginas de autenticacion (login, registro, reset).

    Centra una tarjeta blanca sobre fondo warm. Mantiene los margenes y
    esquinas redondeadas segun `docs/DESIGN.md`. Se evita cualquier
    sidebar para reducir ruido visual en formularios cortos.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'ClientFlow' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAFAF7] font-sans text-[#111827] antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <a href="{{ route('home') }}" class="mb-8 text-2xl font-semibold tracking-tight">ClientFlow</a>

        <div class="w-full max-w-md rounded-[28px] border border-[#E7E2D8] bg-white p-8 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
</body>
</html>
