<x-layouts.admin :title="$summary->project->name">
    <div class="space-y-6">
        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif
        @if ($errors->any())
            <x-ui.alert variant="error">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @php
            $project = $summary->project;
            $crumbs = [
                ['label' => 'Organizaciones', 'href' => route('admin.organizations.index')],
                ['label' => $project->organization->name, 'href' => route('admin.organizations.show', $project->organization)],
                ['label' => $project->name],
            ];

            // Kebab menu del hero: Editar (link normal) +
            // Archivar/Desarchivar (POST con confirmacion,
            // tono danger). Se construyen como array para
            // pasarlos al partial `project-hero::kebabActions`,
            // que se encarga del render del `<details>/<summary>`
            // y de los forms con CSRF.
            $kebabActions = [
                ['label' => 'Editar', 'href' => route('admin.projects.edit', $project), 'method' => 'get', 'tone' => 'normal'],
            ];

            if ($project->isArchived()) {
                $kebabActions[] = [
                    'label' => 'Desarchivar',
                    'href' => route('admin.projects.unarchive', $project),
                    'method' => 'post',
                    'tone' => 'normal',
                ];
            } else {
                $kebabActions[] = [
                    'label' => 'Archivar',
                    'href' => route('admin.projects.archive', $project),
                    'method' => 'post',
                    'tone' => 'danger',
                ];
            }
        @endphp

        {{-- Hero limpio: titulo + CTA principal + menu kebab. --}}
        <x-partials.project-hero
            :project="$project"
            :crumbs="$crumbs"
            :primaryAction="['label' => 'Abrir tablero', 'href' => route('admin.projects.board', $project)]"
            :kebabActions="$kebabActions"
        />

        {{--
            Nav strip con las 8 secciones del proyecto. Reemplaza
            a la antigua "wall of buttons" del hero. Sticky bajo
            el header del layout, con el tab activo resaltado.
        --}}
        <x-partials.project-nav
            :project="$project"
            area="admin"
            :unreadMessages="$summary->unreadMessages"
        />

        {{-- Stat tiles: 4 KPIs en una fila. --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-partials.project-stat-tile
                title="Progreso"
                :value="$project->tasks_progress_percent.'%'"
                :sub="$project->completed_tasks_count.' de '.$project->total_tasks_count.' tareas'"
                :href="Route::has('admin.projects.board') ? route('admin.projects.board', $project) : null"
                tone="primary"
            />

            <x-partials.project-stat-tile
                title="Proxima entrega"
                :value="$summary->nextDelivery?->format('d/m/Y') ?? '—'"
                :sub="$summary->nextDeliveryLabel"
                :href="Route::has('admin.projects.edit') ? route('admin.projects.edit', $project) : null"
                :tone="$summary->nextDeliveryTone"
            />

            <x-partials.project-stat-tile
                title="Mensajes sin leer"
                :value="$summary->unreadMessages"
                :sub="$summary->totalMessages.' mensajes en total'"
                :href="Route::has('admin.projects.chat') ? route('admin.projects.chat', $project) : null"
                tone="danger"
            />

            <x-partials.project-stat-tile
                title="Miembros"
                :value="$summary->totalMembers"
                :sub="$summary->totalMembers === 1 ? 'persona en el equipo' : 'personas en el equipo'"
                :href="Route::has('admin.organizations.show') ? route('admin.organizations.show', $project->organization).'#members' : null"
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
                title="Horas registradas"
                :value="$totalDisplay"
                :sub="$summary->totalLoggedMinutes > 0 ? 'Total dedicado al proyecto' : 'Aun no se ha registrado tiempo'"
                :href="Route::has('admin.projects.time.index') ? route('admin.projects.time.index', $project) : null"
                tone="primary"
            />
        </div>

        <x-partials.project-previews :summary="$summary" area="admin" />

        <div id="miembros">
            <livewire:admin.project.project-members :project="$project" :availableMembers="$availableMembers" />
        </div>
    </div>
</x-layouts.admin>
