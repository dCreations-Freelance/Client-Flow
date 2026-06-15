<x-layouts.admin :title="'Kanban: '.$project->name">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
                    &larr; Volver al detalle
                </a>
                <h1 class="mt-2 truncate text-2xl font-semibold">{{ $project->name }}</h1>
                <p class="flex flex-wrap items-center gap-2 text-sm text-[#6B7280]">
                    <span class="truncate">{{ $project->organization?->name }}</span>
                    <span aria-hidden="true">·</span>
                    <x-partials.status-badge :status="$project->status" />
                </p>
            </div>
        </div>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <livewire:admin.kanban.kanban-board :project="$project" />
    </div>
</x-layouts.admin>
