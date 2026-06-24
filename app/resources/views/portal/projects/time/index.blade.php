<x-layouts.portal :title="'Tiempo: '.$project->name">
    <div class="space-y-6">
        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-2 text-sm text-[#6B7280]">
            <a href="{{ route('portal.dashboard') }}" class="hover:text-[#111827]">Inicio</a>
            <span>/</span>
            <a href="{{ route('portal.organizations.show', $project->organization) }}" class="hover:text-[#111827]">{{ $project->organization->name }}</a>
            <span>/</span>
            <a href="{{ route('portal.projects.show', $project) }}" class="hover:text-[#111827]">{{ $project->name }}</a>
            <span>/</span>
            <span class="text-[#111827]">Tiempo dedicado</span>
        </nav>

        {{-- Cabecera --}}
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold text-[#111827]">Tiempo dedicado</h1>
            <p class="text-sm text-[#6B7280]">
                Resumen de las horas que tu equipo ha invertido en
                <span class="font-medium text-[#111827]">{{ $project->name }}</span>.
            </p>
        </div>

        <livewire:portal.time-tracking.project-time-summary :project="$project" />
    </div>
</x-layouts.portal>
