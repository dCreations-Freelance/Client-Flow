<x-layouts.admin :title="'Calendario · '.$project->name">
    <div class="space-y-6">
        <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al proyecto
        </a>

        <header>
            <h1 class="text-2xl font-semibold">Calendario</h1>
            <p class="mt-1 text-sm text-[#6B7280]">
                Proyecto: <span class="font-medium text-[#111827]">{{ $project->name }}</span>
                ·
                Organizacion:
                <a href="{{ route('admin.organizations.show', $project->organization) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">
                    {{ $project->organization->name }}
                </a>
            </p>
        </header>

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

        <livewire:shared.calendar-view :project="$project" />
    </div>
</x-layouts.admin>
