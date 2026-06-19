<x-layouts.portal :title="'Calendario · '.$project->name">
    <div class="space-y-6">
        <a href="{{ route('portal.projects.show', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al proyecto
        </a>

        <header>
            <h1 class="text-2xl font-semibold">Calendario</h1>
            <p class="mt-1 text-sm text-[#6B7280]">
                Aqui ves las reuniones, hitos y fechas limite del proyecto
                <span class="font-medium text-[#111827]">{{ $project->name }}</span>.
            </p>
        </header>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <livewire:shared.calendar-view :project="$project" :readOnly="true" />
    </div>
</x-layouts.portal>
