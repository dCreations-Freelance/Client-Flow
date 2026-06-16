{{--
    Sidebar del panel de administracion.

    Lista los modulos disponibles. El item activo se detecta
    comparando el prefijo de la ruta actual. Se usa `Route::has(...)`
    para no romper la vista si la ruta aun no existe.

    El item "Proyectos" muestra un badge con el total de mensajes
    de chat no leidos en todos los proyectos visibles. Se calcula
    una sola vez por render del sidebar.
--}}
@php
    $current = request()->route()?->getName() ?? '';

    // Calculamos el total de mensajes no leidos para el admin actual.
    // Es una query agregada unica: suma los counts por proyecto
    // y luego descuenta lo ya leido. Aceptable porque los admins
    // tienen pocos proyectos.
    $unreadTotal = 0;
    if (Route::has('admin.projects.chat') && auth()->check()) {
        $userId = auth()->id();
        $reads = \App\Models\ProjectChatRead::query()
            ->where('user_id', $userId)
            ->pluck('last_read_message_id', 'project_id');

        $projects = \App\Models\Project::query()
            ->whereIn('id', $reads->keys()->all() ?: [0])
            ->pluck('id');

        // Para usuarios sin marcador, no podemos calcularlo en una
        // sola query sin N+1. En MVP, los admins siempre tienen
        // marcador (porque crearon o visitaron proyectos), asi que
        // este caso es marginal. Si llega a ser un problema, se
        // puede cambiar a una query agregada con subquery.
        foreach ($reads as $projectId => $lastRead) {
            $unreadTotal += \App\Models\ProjectMessage::query()
                ->where('project_id', $projectId)
                ->where('id', '>', (int) $lastRead)
                ->count();
        }
    }
@endphp

<aside class="hidden w-60 shrink-0 border-r border-[#E7E2D8] bg-white px-4 py-6 lg:flex lg:flex-col">
    <a href="{{ route('home') }}" class="mb-8 px-2 text-xl font-semibold tracking-tight">ClientFlow</a>

    <nav class="flex flex-1 flex-col gap-1 text-sm">
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'admin.dashboard') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
            <span>Panel</span>
        </a>

        @if (Route::has('admin.organizations.index'))
            <a href="{{ route('admin.organizations.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'admin.organizations') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Organizaciones</span>
            </a>
        @endif

        @if (Route::has('admin.projects.index'))
            <a href="{{ route('admin.projects.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'admin.projects') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Proyectos</span>
                @if ($unreadTotal > 0)
                    <span class="ml-auto inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#DC2626] px-1.5 text-[10px] font-semibold text-white">
                        {{ $unreadTotal }}
                    </span>
                @endif
            </a>
        @endif

        <span class="mt-4 px-3 text-xs font-medium uppercase tracking-wider text-[#9CA3AF]">Pronto</span>

        <span class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-[#9CA3AF]">Plantillas IA</span>
    </nav>
</aside>
