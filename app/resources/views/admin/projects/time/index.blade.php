<x-layouts.admin :title="'Tiempo: '.$project->name">
    <div class="space-y-6">
        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-2 text-sm text-[#6B7280]">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-[#111827]">Inicio</a>
            <span>/</span>
            <a href="{{ route('admin.projects.index') }}" class="hover:text-[#111827]">Proyectos</a>
            <span>/</span>
            <a href="{{ route('admin.projects.show', $project) }}" class="hover:text-[#111827]">{{ $project->name }}</a>
            <span>/</span>
            <span class="text-[#111827]">Registro de tiempo</span>
        </nav>

        {{-- Cabecera con titulo y descripcion --}}
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold text-[#111827]">Registro de tiempo</h1>
            <p class="text-sm text-[#6B7280]">
                Filtra, revisa y exporta las horas dedicadas al proyecto
                <a href="{{ route('admin.projects.show', $project) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">
                    {{ $project->name }}
                </a>.
            </p>
        </div>

        <livewire:admin.time-tracking.project-time-dashboard :project="$project" />
    </div>
</x-layouts.admin>
