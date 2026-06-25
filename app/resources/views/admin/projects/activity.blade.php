<x-layouts.admin :title="'Actividad: '.$project->name">
    <div class="space-y-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
                    &larr; Volver al detalle
                </a>
                <h1 class="mt-2 truncate text-2xl font-semibold">Actividad</h1>
                <p class="flex flex-wrap items-center gap-2 text-sm text-[#6B7280]">
                    <span class="truncate">{{ $project->name }}</span>
                    <span aria-hidden="true">·</span>
                    <span class="truncate">{{ $project->organization?->name }}</span>
                </p>
            </div>
        </div>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="rounded-xl border border-[#E7E2D8] bg-white p-4 sm:p-6">
            <livewire:shared.project-activity-feed
                :project="$project"
                :portalMode="false"
            />
        </div>
    </div>
</x-layouts.admin>
