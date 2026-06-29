{{--
    Hero editorial.

    Composición:
    - A la izquierda: título display con reveal palabra a palabra,
      contador animado "0 -> 10 segundos" y CTAs con magnetic
      hover. El claim va en `#8B5CF6` (Info en DESIGN.md) para
      reforzar la promesa clave.
    - A la derecha: mockup de un proyecto real con kanban
      simulado (3 columnas, una tarea "salta" entre ellas para
      sugerir movimiento real). El mockup tambien muestra el
      estado del proyecto y el último avance.
    - Debajo: marquesina con badges de estado y un microcopy
      "Open source - Self-hostable - MIT".

    Sin imágenes externas, sin stock. Todo HTML + Tailwind.
    El spotlight del cursor lo gestiona `landing.js`.
--}}
<section
    data-cf-hero
    class="relative overflow-hidden pt-32 pb-20 sm:pt-40 sm:pb-28 lg:pt-48 lg:pb-32"
    aria-labelledby="cf-hero-title"
>
    {{-- Decoracion de fondo: blobs suaves muy tenues para dar
         profundidad sin distraer. Las clases `cf-blob-a` y
         `cf-blob-b` les aplican una animacion de movimiento
         lento orgánico (definida en `landing.css`). --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
        <div class="cf-blob-a absolute -left-32 top-20 h-72 w-72 rounded-full bg-[#8B5CF6] opacity-[0.06] blur-3xl"></div>
        <div class="cf-blob-b absolute right-0 top-40 h-96 w-96 rounded-full bg-[#2563EB] opacity-[0.05] blur-3xl"></div>
    </div>

    <div class="mx-auto grid max-w-7xl gap-14 px-6 lg:grid-cols-[1.05fr_0.95fr] lg:gap-20 lg:px-10">

        {{-- Lado izquierdo: claim + CTAs --}}
        <div class="flex flex-col">
            <div class="cf-stagger-in flex flex-col gap-6">

                <div class="cf-reveal flex items-center justify-between gap-4">
                    <span class="cf-section-marker">
                        <span class="font-mono text-[#8B5CF6]">01</span>
                        <span>Hero</span>
                    </span>
                    <span class="inline-flex items-center gap-2 text-xs font-medium text-[#6B7280]">
                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-[#16A34A]"></span>
                        v0.9 lista para producción
                    </span>
                </div>

                <h1
                    id="cf-hero-title"
                    class="cf-reveal text-[2.75rem] font-semibold leading-[1.05] tracking-[-0.03em] text-[#111827] sm:text-6xl lg:text-[5.25rem]"
                >
                    Tus clientes entienden su proyecto en
                    <span class="relative inline-flex items-baseline text-[#8B5CF6]">
                        <span class="cf-counter tabular-nums" data-cf-target="10" data-cf-duration="1400" data-cf-suffix="">0</span>
                        <span class="cf-counter-cursor" aria-hidden="true"></span>
                    </span>
                    segundos.
                </h1>

                <p class="cf-reveal max-w-xl text-lg leading-8 text-[#6B7280] sm:text-xl">
                    ClientFlow es el portal privado donde freelancers y agencias pequeñas muestran a sus clientes el estado real del proyecto: tareas, documentos, chat y aprobaciones. <span class="text-[#111827]">Sin emails, sin WhatsApp, sin capturas de Trello.</span>
                </p>

                <div class="cf-reveal mt-2 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a
                        href="{{ route('register') }}"
                        class="cf-magnetic inline-flex items-center justify-center gap-2 rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-black"
                    >
                        Crear cuenta gratis
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 5l7 7-7 7" />
                        </svg>
                    </a>

                    <a
                        href="https://github.com/dCreations-Freelance/Client-Flow"
                        rel="noopener"
                        class="cf-magnetic inline-flex items-center justify-center gap-2 rounded-xl border border-[#E7E2D8] bg-white px-5 py-3 text-sm font-semibold text-[#111827] transition-colors hover:border-[#D8D0C3] hover:bg-[#F4F1EA]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                            <path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.92.58.1.79-.25.79-.56v-2c-3.2.7-3.87-1.54-3.87-1.54-.52-1.32-1.27-1.67-1.27-1.67-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.76 2.69 1.25 3.34.96.1-.74.4-1.25.73-1.54-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.28 1.18-3.08-.12-.29-.51-1.46.11-3.05 0 0 .96-.31 3.15 1.18.91-.25 1.89-.38 2.86-.39.97.01 1.95.14 2.86.39 2.18-1.49 3.15-1.18 3.15-1.18.62 1.59.23 2.76.11 3.05.74.8 1.18 1.82 1.18 3.08 0 4.42-2.7 5.39-5.27 5.68.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56C20.21 21.38 23.5 17.08 23.5 12 23.5 5.65 18.35.5 12 .5z"/>
                        </svg>
                        Ver en GitHub
                    </a>
                </div>

                <div class="cf-reveal mt-3 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-[#6B7280]">
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5 text-[#16A34A]" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        Instalación en menos de 5 minutos
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5 text-[#16A34A]" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        Sin tarjeta de crédito
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5 text-[#16A34A]" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        MIT licensed
                    </span>
                </div>
            </div>
        </div>

        {{-- Lado derecho: mockup del proyecto --}}
        <div class="cf-reveal relative">
            <div class="absolute -inset-6 -z-10 rounded-[36px] bg-gradient-to-br from-[#8B5CF6]/5 to-[#2563EB]/5 blur-2xl"></div>

            {{-- Tarjeta principal --}}
            <div class="rounded-[28px] border border-[#E7E2D8] bg-white p-6 shadow-[0_20px_60px_rgba(17,24,39,0.08)]">
                {{-- Cabecera del proyecto --}}
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wider text-[#6B7280]">
                            <span class="cf-live-dot inline-flex h-1.5 w-1.5 rounded-full bg-[#16A34A]"></span>
                            Proyecto en vivo
                        </p>
                        <h2 class="mt-1 text-lg font-semibold text-[#111827]">Web corporativa clínica dental</h2>
                        <p class="mt-0.5 text-xs text-[#6B7280]">Cliente: Sonrisa Sana · actualizado hace 2 min</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-[#FFFBEB] px-2.5 py-0.5 text-xs font-medium text-[#D97706]">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#D97706]"></span>
                        En progreso
                    </span>
                </div>

                {{-- Progreso --}}
                <div class="mt-5">
                    <div class="mb-1.5 flex items-center justify-between text-xs">
                        <span class="font-medium text-[#6B7280]">Progreso del proyecto</span>
                        <span class="font-semibold text-[#111827] tabular-nums">68%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-[#F4F1EA]">
                        <div class="cf-bar-live h-full rounded-full bg-[#111827]" style="width: 68%"></div>
                    </div>
                </div>

                {{-- Mini kanban --}}
                <div class="mt-6 grid grid-cols-3 gap-2">
                    <div class="rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">Por hacer · 3</p>
                        <div class="mt-2 space-y-1.5">
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-1.5 text-[10px] text-[#111827]">Hero animado</div>
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-1.5 text-[10px] text-[#111827]">Página de servicios</div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">En curso · 2</p>
                        <div class="mt-2 space-y-1.5">
                            <div class="cf-kanban-card-live rounded-md border border-[#E7E2D8] bg-white p-1.5 text-[10px] text-[#111827] shadow-sm">Formulario de cita</div>
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-1.5 text-[10px] text-[#111827]">Integración maps</div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">Hecho · 5</p>
                        <div class="mt-2 space-y-1.5">
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-1.5 text-[10px] text-[#111827]">Wireframes</div>
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-1.5 text-[10px] text-[#111827]">Diseño desktop</div>
                        </div>
                    </div>
                </div>

                {{-- Último avance --}}
                <div class="mt-5 flex items-start gap-3 rounded-2xl bg-[#F4F1EA] p-4">
                    <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[#8B5CF6] text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-[#111827]">Último avance del equipo</p>
                        <p class="mt-0.5 text-xs leading-5 text-[#6B7280]">Subimos la versión mobile del formulario de cita para tu revisión. Avísanos por el chat del proyecto si quieres ajustes.</p>
                        <p class="mt-1.5 text-[10px] font-medium text-[#8B5CF6]">Maria · hace 12 min</p>
                    </div>
                </div>
            </div>

            {{-- Chip flotante: "Visto por el cliente" --}}
            <div class="cf-reveal absolute -left-3 -bottom-4 hidden items-center gap-2 rounded-full border border-[#E7E2D8] bg-white px-3 py-2 text-xs font-medium text-[#111827] shadow-[0_10px_30px_rgba(17,24,39,0.08)] sm:flex">
                <span class="inline-flex h-2 w-2 rounded-full bg-[#16A34A]"></span>
                El cliente ha visto este avance
            </div>
        </div>
    </div>

    {{-- Marquesina inferior: badges de estado y un microcopy --}}
    <div class="cf-marquee-pause relative mt-16 overflow-hidden border-y border-[#E7E2D8] bg-white/50 py-5">
        <div class="cf-marquee gap-10 px-6">
            @php
                $statusBadges = [
                    ['label' => 'Planificación', 'color' => 'bg-[#EFF6FF] text-[#2563EB]'],
                    ['label' => 'En progreso', 'color' => 'bg-[#FFFBEB] text-[#D97706]'],
                    ['label' => 'En pausa', 'color' => 'bg-[#F4F1EA] text-[#6B7280]'],
                    ['label' => 'Esperando cliente', 'color' => 'bg-[#FFFBEB] text-[#D97706]'],
                    ['label' => 'Completado', 'color' => 'bg-[#F0FDF4] text-[#16A34A]'],
                    ['label' => 'Documento público', 'color' => 'bg-[#EFF6FF] text-[#2563EB]'],
                    ['label' => 'Documento privado', 'color' => 'bg-[#F5F3FF] text-[#8B5CF6]'],
                    ['label' => 'Mensaje no leído', 'color' => 'bg-[#FEF2F2] text-[#DC2626]'],
                    ['label' => 'Tarea crítica', 'color' => 'bg-[#FEF2F2] text-[#DC2626]'],
                    ['label' => 'Bug', 'color' => 'bg-[#FEF2F2] text-[#DC2626]'],
                    ['label' => 'Feature', 'color' => 'bg-[#F5F3FF] text-[#8B5CF6]'],
                    ['label' => 'Deadline 24h', 'color' => 'bg-[#FFFBEB] text-[#D97706]'],
                ];
            @endphp
            @foreach (array_merge($statusBadges, $statusBadges) as $badge)
                <span class="inline-flex shrink-0 items-center gap-2 text-xs font-medium text-[#6B7280]">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 {{ $badge['color'] }}">{{ $badge['label'] }}</span>
                </span>
            @endforeach
        </div>
    </div>
</section>
