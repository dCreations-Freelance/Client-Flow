<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Portal' }} · ClientFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FAFAF7] font-sans text-[#111827] antialiased">
    <div class="min-h-screen lg:flex">
        @include('partials.portal-sidebar')

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-10 border-b border-[#E7E2D8]/80 bg-[#FAFAF7]/90 backdrop-blur">
                <div class="flex h-[72px] items-center justify-between gap-4 px-5 lg:px-8">
                    <p class="text-sm text-[#6B7280]">Tu espacio privado de seguimiento</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-xl border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:border-[#D8D0C3]">
                            Salir
                        </button>
                    </form>
                </div>
            </header>

            <main class="mx-auto max-w-[1180px] px-5 py-7 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
