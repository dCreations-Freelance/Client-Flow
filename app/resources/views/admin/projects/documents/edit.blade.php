<x-layouts.admin :title="'Editar: '.$document->title">
    <div class="space-y-6">
        <div>
            <a href="{{ route('admin.projects.documents.show', [$project, $document]) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
                &larr; Volver al documento
            </a>
            <h1 class="mt-2 text-2xl font-semibold">Editar documento</h1>
            <p class="text-sm text-[#6B7280]">
                {{ $project->name }} · {{ $project->organization?->name }}
            </p>
        </div>

        @if ($errors->any())
            <x-ui.alert variant="error">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <x-ui.card>
            <livewire:admin.document.document-editor :project="$project" :document="$document" />
        </x-ui.card>
    </div>
</x-layouts.admin>
