<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FAFAF7">
    <title>ClientFlow</title>

    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAFAF7] font-sans text-[#111827] antialiased">
    <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
        <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight">ClientFlow</a>
        <nav class="flex items-center gap-3 text-sm">
            <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 font-medium text-[#6B7280] hover:text-[#111827]">Acceder</a>
            <a href="{{ route('register') }}" class="rounded-xl border border-[#E7E2D8] bg-white px-4 py-2 font-medium hover:border-[#D8D0C3]">Crear cuenta</a>
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-14 lg:py-24">
        <div class="grid items-center gap-10 lg:grid-cols-[1.05fr_0.95fr]">
            <section>
                <p class="mb-4 text-sm font-semibold text-[#B88746]">Portal privado para clientes</p>
                <h1 class="max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">Tus clientes ven, entienden y aprueban el avance del proyecto.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-[#6B7280]">Seguimiento visual, entregables, comentarios, documentos y aprobaciones en un unico espacio profesional.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="rounded-xl bg-[#111827] px-5 py-3 text-center text-sm font-semibold text-white hover:bg-black">Crear cuenta</a>
                    <a href="{{ route('login') }}" class="rounded-xl border border-[#E7E2D8] bg-white px-5 py-3 text-center text-sm font-semibold hover:border-[#D8D0C3]">Acceder al portal</a>
                </div>
            </section>

            <section class="rounded-[28px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-[#6B7280]">Proyecto</p>
                        <h2 class="mt-1 text-xl font-semibold">Web corporativa clinica</h2>
                    </div>
                    <span class="rounded-full bg-[#DBEAFE] px-3 py-1 text-xs font-medium text-[#1D4ED8]">En progreso</span>
                </div>
                <div class="mt-8">
                    <div class="mb-2 flex justify-between text-sm">
                        <span class="text-[#6B7280]">Progreso</span>
                        <span class="font-medium">78%</span>
                    </div>
                    <div class="h-3 rounded-full bg-[#F4F1EA]">
                        <div class="h-3 w-[78%] rounded-full bg-[#111827]"></div>
                    </div>
                </div>
                <div class="mt-8 rounded-[20px] bg-[#F4F1EA] p-5">
                    <p class="text-sm font-medium">Ultimo avance</p>
                    <p class="mt-2 text-sm leading-6 text-[#6B7280]">Demo del formulario de contacto publicada para revision del cliente.</p>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
