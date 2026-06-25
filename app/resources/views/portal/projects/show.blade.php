<x-layouts.portal :title="$summary->project->name">
    <div class="space-y-6">
        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        @php
            $project = $summary->project;
            $crumbs = [
                ['label' => 'Mis organizaciones', 'href' => route('portal.dashboard')],
                ['label' => $project->organization->name, 'href' => route('portal.organizations.show', $project->organization)],
                ['label' => $project->name],
            ];
        @endphp

        {{--
            Hero limpio: mismo patron que el admin pero sin
            menu kebab (el portal cliente no edita ni archiva
            proyectos). El CTA principal "Ver tablero kanban"
            sigue siendo la accion mas util para un cliente
            cuando entra al detalle del proyecto.
        --}}
        <x-partials.project-hero
            :project="$project"
            :crumbs="$crumbs"
            :unreadMessages="$summary->unreadMessages"
            :showArchived="false"
            :primaryAction="['label' => 'Ver tablero kanban', 'href' => route('portal.projects.board', $project)]"
        />

        {{--
            Nav strip con las 7 secciones que el cliente puede
            usar del proyecto. Sin "Agentes" (admin-only) y sin
            "Editar/Archivar" (no aplica).
        --}}
        <x-partials.project-nav
            :project="$project"
            area="portal"
            :unreadMessages="$summary->unreadMessages"
        />

        {{-- Stat tiles: 4 KPIs adaptados al portal. --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-partials.project-stat-tile
                title="Progreso"
                :value="$project->tasks_progress_percent.'%'"
                :sub="$project->completed_tasks_count.' de '.$project->total_tasks_count.' tareas completadas'"
                :href="Route::has('portal.projects.board') ? route('portal.projects.board', $project) : null"
                tone="primary"
            />

            <x-partials.project-stat-tile
                title="Proxima entrega"
                :value="$summary->nextDelivery?->format('d/m/Y') ?? '—'"
                :sub="$summary->nextDeliveryLabel"
                :tone="$summary->nextDeliveryTone"
            />

            <x-partials.project-stat-tile
                title="Mensajes"
                :value="$summary->unreadMessages"
                :sub="$summary->unreadMessages > 0 ? 'Tienes mensajes sin leer' : 'Al dia con la conversacion'"
                :href="Route::has('portal.projects.chat') ? route('portal.projects.chat', $project) : null"
                :tone="$summary->unreadMessages > 0 ? 'danger' : 'neutral'"
            />

            <x-partials.project-stat-tile
                title="Tu equipo"
                :value="$summary->totalMembers"
                :sub="$summary->totalMembers === 1 ? 'persona trabajando' : 'personas trabajando'"
            />
        </div>

        @php
            $totalHours = intdiv($summary->totalLoggedMinutes, 60);
            $totalMins = $summary->totalLoggedMinutes % 60;
            $totalDisplay = $totalHours > 0
                ? $totalHours.'h '.str_pad((string) $totalMins, 2, '0', STR_PAD_LEFT).'m'
                : $totalMins.'m';
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-partials.project-stat-tile
                title="Horas dedicadas"
                :value="$totalDisplay"
                :sub="$summary->totalLoggedMinutes > 0 ? 'Total invertido en este proyecto' : 'Aun no hay tiempo registrado'"
                :href="Route::has('portal.projects.time.index') ? route('portal.projects.time.index', $project) : null"
                tone="primary"
            />
        </div>

        <x-partials.project-previews :summary="$summary" area="portal" />
    </div>
</x-layouts.portal>
