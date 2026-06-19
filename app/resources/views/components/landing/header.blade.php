{{--
    Cabecera de la landing.

    Empieza transparente sobre el hero y se vuelve solida con
    blur al hacer scroll (clase `.is-scrolled` que gestiona
    `resources/js/landing.js`).

    En mobile los enlaces de navegacion se ocultan y aparece un
    boton hamburguesa. Al pulsarlo:
    - Aparece un backdrop con blur detras de la cabecera.
    - El panel desliza hacia abajo con un stagger de los links.
    - El icono hamburguesa se transforma en una X.
    El JS vive en `resources/js/landing.js` (`initMobileMenu`).

    Los links de la nav de desktop reciben la clase `is-active`
    via JS cuando la seccion correspondiente entra en viewport
    (smooth scroll spy).

    El logo es la palabra "ClientFlow" con un cuadrado al lado
    que sirve de marca grafica. Sin imagen externa: todo SVG.
--}}
<header class="cf-header fixed inset-x-0 top-0 z-40" data-cf-header>
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-10">
        <a href="{{ route('home') }}" class="group flex items-center gap-2.5 text-sm font-semibold tracking-tight">
            <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#111827] text-white transition-transform duration-500 group-hover:rotate-[8deg]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 6h6v6H4zM14 6h6v6h-6zM4 16h6v4H4zM14 16h6v4h-6z" />
                </svg>
            </span>
            <span>ClientFlow</span>
        </a>

        <nav class="hidden items-center gap-1 text-sm md:flex" aria-label="Navegacion principal">
            <a href="#features" data-cf-spy="features" class="cf-nav-link rounded-lg px-3 py-2 font-medium text-[#6B7280] transition-colors hover:bg-[#F4F1EA] hover:text-[#111827]">Funciones</a>
            <a href="#dual-view" data-cf-spy="dual-view" class="cf-nav-link rounded-lg px-3 py-2 font-medium text-[#6B7280] transition-colors hover:bg-[#F4F1EA] hover:text-[#111827]">Portal</a>
            <a href="#mcp" data-cf-spy="mcp" class="cf-nav-link rounded-lg px-3 py-2 font-medium text-[#6B7280] transition-colors hover:bg-[#F4F1EA] hover:text-[#111827]">MCP</a>
            <a href="#stack" data-cf-spy="stack" class="cf-nav-link rounded-lg px-3 py-2 font-medium text-[#6B7280] transition-colors hover:bg-[#F4F1EA] hover:text-[#111827]">Stack</a>
            <a href="#faq" data-cf-spy="faq" class="cf-nav-link rounded-lg px-3 py-2 font-medium text-[#6B7280] transition-colors hover:bg-[#F4F1EA] hover:text-[#111827]">FAQ</a>
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-[#6B7280] transition-colors hover:text-[#111827] sm:inline-flex">Acceder</a>
            <a href="{{ route('register') }}" class="cf-magnetic inline-flex items-center gap-1.5 rounded-lg bg-[#111827] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-black">
                Empezar gratis
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 5l7 7-7 7" />
                </svg>
            </a>
            <button
                type="button"
                data-cf-menu-toggle
                aria-controls="cf-mobile-menu"
                aria-expanded="false"
                class="cf-menu-toggle md:hidden relative grid h-9 w-9 place-items-center rounded-lg border border-[#E7E2D8] bg-white text-[#111827] transition-colors hover:bg-[#F4F1EA]"
            >
                <span class="cf-menu-bars" aria-hidden="true">
                    <span class="block"></span>
                    <span class="block"></span>
                    <span class="block"></span>
                </span>
            </button>
        </div>
    </div>

    {{-- Backdrop del menu mobile. Cubre toda la pantalla con
         blur, debajo del header. La opacidad se anima via CSS
         con la clase `is-open` que pone el JS. --}}
    <div
        data-cf-menu-backdrop
        class="cf-menu-backdrop md:hidden fixed inset-0 z-30 bg-[#111827]/0 backdrop-blur-0 transition-all duration-300"
        aria-hidden="true"
    ></div>

    {{-- Panel mobile. Empieza con altura colapsada (max-height: 0)
         y la opacidad en 0. La clase `is-open` la pone el JS al
         pulsar el toggle y dispara la transicion suave. --}}
    <div
        id="cf-mobile-menu"
        data-cf-menu
        class="cf-menu-panel md:hidden relative z-40 overflow-hidden border-t border-[#E7E2D8] bg-white/95 backdrop-blur"
    >
        <nav class="mx-auto flex max-w-7xl flex-col gap-1 px-6 py-4 text-sm" aria-label="Navegacion movil" data-cf-menu-list>
            <a href="#features" data-cf-menu-link data-cf-spy="features" class="cf-menu-link cf-nav-link rounded-lg px-3 py-2 font-medium text-[#111827] transition-colors hover:bg-[#F4F1EA]">Funciones</a>
            <a href="#dual-view" data-cf-menu-link data-cf-spy="dual-view" class="cf-menu-link cf-nav-link rounded-lg px-3 py-2 font-medium text-[#111827] transition-colors hover:bg-[#F4F1EA]">Portal</a>
            <a href="#mcp" data-cf-menu-link data-cf-spy="mcp" class="cf-menu-link cf-nav-link rounded-lg px-3 py-2 font-medium text-[#111827] transition-colors hover:bg-[#F4F1EA]">MCP</a>
            <a href="#stack" data-cf-menu-link data-cf-spy="stack" class="cf-menu-link cf-nav-link rounded-lg px-3 py-2 font-medium text-[#111827] transition-colors hover:bg-[#F4F1EA]">Stack</a>
            <a href="#faq" data-cf-menu-link data-cf-spy="faq" class="cf-menu-link cf-nav-link rounded-lg px-3 py-2 font-medium text-[#111827] transition-colors hover:bg-[#F4F1EA]">FAQ</a>
            <a href="{{ route('login') }}" data-cf-menu-link class="cf-menu-link mt-2 rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-center font-medium text-[#111827]">Acceder</a>
        </nav>
    </div>
</header>
