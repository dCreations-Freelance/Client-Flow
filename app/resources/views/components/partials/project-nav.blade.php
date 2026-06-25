{{--
    Nav strip del hub de proyecto. Una franja horizontal con los
    destinos secundarios del proyecto (chat, documentos, etc.) que
    vive **debajo** del `project-hero`. Sustituye a la antigua
    "wall of buttons" que comprimia el titulo y los CTAs en el
    hero.

    Patron visual: tabs tipo SaaS moderno (GitHub, Linear, Vercel).
    El tab activo se resalta con fondo azul + borde inferior; los
    inactivos son texto gris con hover sutil.

    Props:
        - project: el modelo Project.
        - area: `'admin'` o `'portal'`. Controla que tabs se
          renderizan y a que rutas apunta. Admin tiene 8 tabs
          (incluye Agentes + Resumen); portal tiene 7 (sin Agentes,
          sin Resumen porque el portal no tiene un hub resumen
          propio, llega al detalle via listado de proyectos).
        - unreadMessages: contador de mensajes sin leer del chat.
          Si > 0, el tab "Chat" muestra un badge rojo.
        - activeRoute: nombre de la ruta a marcar como activa
          (ej: `'admin.projects.chat'`). Por defecto usa
          `request()->route()?->getName()`.

    Comportamiento:
        - En `lg` (>= 1024px) los tabs caben en una fila sin
          scroll.
        - En pantallas mas pequenas: scroll horizontal con
          `overflow-x-auto` y `whitespace-nowrap` por tab. Sin
          hamburguesa, patron conocido y predecible.
        - El tab activo se detecta con `request()->routeIs(...)`,
          no con un match exacto, para tolerar parametros.
        - Es sticky bajo el header del layout: queda visible al
          hacer scroll. El hero deja de ser sticky (esa
          responsabilidad pasa al nav strip).

    Uso:
        <x-partials.project-nav
            :project="$project"
            area="admin"
            :unreadMessages="$summary->unreadMessages"
        />
--}}
@props([
    'project',
    'area' => 'admin',
    'unreadMessages' => 0,
])

@php
    $current = request()->route()?->getName() ?? '';
    $isActive = fn (string $routeName) => request()->routeIs($routeName)
        || str_starts_with($current, $routeName);

    // Definicion de tabs por area. Cada tab tiene:
    //   - label: texto visible
    //   - route: nombre de la ruta (la prop `activeRoute` se
    //     compara con esto)
    //   - href: callable que recibe el project y devuelve la URL
    //     final. Usamos callable para que `route()` se evalue
    //     en tiempo de render y no en tiempo de definicion.
    //   - badge: contador opcional (ej: mensajes sin leer)
    $tabs = match ($area) {
        'portal' => [
            ['label' => 'Chat', 'route' => 'portal.projects.chat', 'href' => fn ($p) => route('portal.projects.chat', $p), 'badge' => $unreadMessages],
            ['label' => 'Documentos', 'route' => 'portal.projects.documents.*', 'href' => fn ($p) => route('portal.projects.documents.index', $p), 'badge' => null],
            ['label' => 'Calendario', 'route' => 'portal.projects.calendar', 'href' => fn ($p) => route('portal.projects.calendar', $p), 'badge' => null],
            ['label' => 'Asistente IA', 'route' => 'portal.projects.ai*', 'href' => fn ($p) => route('portal.projects.ai', $p), 'badge' => null],
            ['label' => 'Tiempo', 'route' => 'portal.projects.time.*', 'href' => fn ($p) => route('portal.projects.time.index', $p), 'badge' => null],
            ['label' => 'Actividad', 'route' => 'portal.projects.activity', 'href' => fn ($p) => route('portal.projects.activity', $p), 'badge' => null],
        ],
        default => [
            ['label' => 'Resumen', 'route' => 'admin.projects.show', 'href' => fn ($p) => route('admin.projects.show', $p), 'badge' => null],
            ['label' => 'Chat', 'route' => 'admin.projects.chat', 'href' => fn ($p) => route('admin.projects.chat', $p), 'badge' => $unreadMessages],
            ['label' => 'Documentos', 'route' => 'admin.projects.documents.*', 'href' => fn ($p) => route('admin.projects.documents.index', $p), 'badge' => null],
            ['label' => 'Calendario', 'route' => 'admin.projects.calendar', 'href' => fn ($p) => route('admin.projects.calendar', $p), 'badge' => null],
            ['label' => 'Asistente IA', 'route' => 'admin.projects.ai*', 'href' => fn ($p) => route('admin.projects.ai', $p), 'badge' => null],
            ['label' => 'Agentes', 'route' => 'admin.projects.agents.*', 'href' => fn ($p) => route('admin.projects.agents.index', $p), 'badge' => null],
            ['label' => 'Tiempo', 'route' => 'admin.projects.time.*', 'href' => fn ($p) => route('admin.projects.time.index', $p), 'badge' => null],
            ['label' => 'Actividad', 'route' => 'admin.projects.activity', 'href' => fn ($p) => route('admin.projects.activity', $p), 'badge' => null],
        ],
    };
@endphp

<nav
    aria-label="Secciones del proyecto"
    class="sticky top-16 z-20 -mx-6 border-b border-[#E7E2D8] bg-white/95 backdrop-blur lg:-mx-8"
>
    {{--
        Contenedor con scroll horizontal en pantallas pequenas.
        Ocultamos la barra de scroll con `[&::-webkit-scrollbar]:hidden`
        para que no aparezca y ensucie la UI; sigue siendo
        funcional (scroll con dos dedos / trackpad).
    --}}
    <div class="overflow-x-auto px-6 lg:px-8 [&::-webkit-scrollbar]:hidden">
        <ul class="flex items-center gap-1 whitespace-nowrap">
            @foreach ($tabs as $tab)
                @php
                    $isTabActive = $isActive($tab['route']);
                    $href = $tab['href']($project);
                    $badge = $tab['badge'] ?? null;
                    $hasBadge = $badge !== null && (int) $badge > 0;
                @endphp
                <li>
                    <a
                        href="{{ $href }}"
                        aria-current="{{ $isTabActive ? 'page' : 'false' }}"
                        class="relative inline-flex items-center gap-1.5 border-b-2 px-3 py-3 text-sm font-medium transition-colors {{ $isTabActive ? 'border-[#2563EB] text-[#2563EB]' : 'border-transparent text-[#6B7280] hover:border-[#E7E2D8] hover:text-[#111827]' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2563EB] focus-visible:ring-offset-2"
                    >
                        <span>{{ $tab['label'] }}</span>

                        {{-- Badge opcional (ej: mensajes sin leer en el chat) --}}
                        @if ($hasBadge)
                            <span
                                class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-[10px] font-semibold {{ $isTabActive ? 'bg-[#2563EB] text-white' : 'bg-[#DC2626] text-white' }}"
                                aria-label="{{ $badge }} sin leer"
                            >
                                {{ $badge }}
                            </span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>
