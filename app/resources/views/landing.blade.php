{{--
    Landing page de ClientFlow para usuarios no autenticados.

    Se ha construido desde cero para presentar el producto como un
    "reportaje editorial" en lugar de la tipica landing SaaS. Las
    10 secciones viven en `components/landing/*` y la interactividad
    (reveal, contadores, typing, magnetic hover, etc.) se delega
    a `resources/js/landing.js`.

    Decisiones de diseno:
    - Header transparente que se vuelve solido al hacer scroll.
    - Numeracion explicita (01, 02, ...) para reforzar el tono
      editorial.
    - Sin parallax agresivo: solo fade + translate-y al entrar
      en viewport.
    - Toda la paleta sale de `docs/DESIGN.md`. El unico color
      "editorial" es `#8B5CF6` (Info) que DESIGN.md define
      explicitamente como color para "elementos especiales".
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FAFAF7">
    <meta name="description" content="ClientFlow es el portal privado donde tus clientes ven, entienden y aprueban el avance del proyecto en menos de 10 segundos. Open source y self-hostable.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>ClientFlow — El portal donde tus clientes entienden su proyecto</title>

    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-[#FAFAF7] font-sans text-[#111827] antialiased">

    {{-- Cabecera transparente que se vuelve solida al hacer scroll. La clase
         `cf-header` la controla `landing.js` para anadir `is-scrolled`. --}}
    <x-landing.header />

    <main>
        <x-landing.hero />
        <x-landing.manifesto />
        <x-landing.bento-features />
        <x-landing.dual-view />
        <x-landing.mcp-section />
        <x-landing.stack />
        <x-landing.numbers />
        <x-landing.quote />
        <x-landing.faq />
        <x-landing.cta-final />
    </main>

    <x-landing.site-footer />

    {{-- Barra superior de progreso de scroll. Se actualiza desde `landing.js`. --}}
    <div id="cf-scroll-progress" aria-hidden="true" class="cf-scroll-progress"></div>

    {{-- Spotlight decorativo que sigue al cursor solo en el hero. --}}
    <div id="cf-hero-spotlight" aria-hidden="true" class="cf-hero-spotlight"></div>

</body>
</html>
