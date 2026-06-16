{{--
    Sidebar del portal cliente.

    Se actualizo en fase 2 para anadir el enlace a "Proyectos" como
    vista dedicada. Los enlaces a fases siguientes (Calendario,
    Notificaciones) se iran habilitando a medida que existan sus
    rutas.

    El item "Proyectos" muestra un badge con el total de mensajes
    de chat no leidos en todos los proyectos visibles al cliente.
--}}
@php
    $current = request()->route()?->getName() ?? '';

    $unreadTotal = 0;
    if (Route::has('portal.projects.chat') && auth()->check()) {
        $userId = auth()->id();
        $reads = \App\Models\ProjectChatRead::query()
            ->where('user_id', $userId)
            ->pluck('last_read_message_id', 'project_id');

        foreach ($reads as $projectId => $lastRead) {
            // El cliente solo ve proyectos visibles a el; filtramos
            // adicionalmente para no contar mensajes de proyectos
            // que ya no ve.
            $project = \App\Models\Project::find($projectId);
            if ($project === null || ! $project->isVisibleToClient()) {
                continue;
            }
            $unreadTotal += \App\Models\ProjectMessage::query()
                ->where('project_id', $projectId)
                ->where('id', '>', (int) $lastRead)
                ->count();
        }
    }
@endphp

<aside class="hidden w-55 shrink-0 border-r border-[#E7E2D8] bg-white px-4 py-6 lg:flex lg:flex-col" style="width: 220px;">
    <a href="{{ route('home') }}" class="mb-8 px-2 text-xl font-semibold tracking-tight">ClientFlow</a>

    <nav class="flex flex-1 flex-col gap-1 text-sm">
        <a href="{{ route('portal.dashboard') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'portal.dashboard') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
            <span>Inicio</span>
        </a>

        @if (Route::has('portal.projects.index'))
            <a href="{{ route('portal.projects.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'portal.projects') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Proyectos</span>
                @if ($unreadTotal > 0)
                    <span class="ml-auto inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#DC2626] px-1.5 text-[10px] font-semibold text-white">
                        {{ $unreadTotal }}
                    </span>
                @endif
            </a>
        @endif

        <span class="mt-4 px-3 text-xs font-medium uppercase tracking-wider text-[#9CA3AF]">Pronto</span>

        <span class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-[#9CA3AF]">Calendario</span>
    </nav>
</aside>
