<x-layouts.admin title="Diario visual">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-[#B88746]">Avances visuales</p>
            <h1 class="text-3xl font-semibold tracking-tight">Diario visual</h1>
            <p class="mt-3 text-[#6B7280]">Capturas, videos y audios que documentan el progreso real de tus proyectos.</p>
        </div>
    </div>

    <form method="GET" class="mb-6 flex flex-col gap-3 rounded-[20px] border border-[#E7E2D8] bg-white p-4 sm:flex-row">
        <select name="type" class="rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm">
            <option value="">Todos los tipos</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="visibility" class="rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm">
            <option value="">Toda visibilidad</option>
            <option value="public" @selected(request('visibility') === 'public')>Cliente</option>
            <option value="internal" @selected(request('visibility') === 'internal')>Interna</option>
        </select>
        <button class="rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white">Filtrar</button>
    </form>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($entries as $entry)
            <article class="overflow-hidden rounded-[24px] border border-[#E7E2D8] bg-white shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
                <a href="{{ route('admin.visual-entries.show', $entry) }}" class="block">
                    <div class="flex aspect-video items-center justify-center bg-[#F4F1EA]">
                        @if ($entry->isImage())
                            <img src="{{ route('visual-entries.media', $entry) }}" alt="{{ $entry->title }}" class="h-full w-full object-cover">
                        @elseif ($entry->isVideo())
                            <span class="rounded-full bg-white px-4 py-2 text-sm font-semibold">Video</span>
                        @else
                            <span class="rounded-full bg-white px-4 py-2 text-sm font-semibold">Audio</span>
                        @endif
                    </div>
                    <div class="p-5">
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-[#F4F1EA] px-3 py-1 text-xs font-medium">{{ $types[$entry->type] ?? $entry->type }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $entry->visibility === 'public' ? 'bg-[#DCFCE7] text-[#15803D]' : 'bg-[#FEF3C7] text-[#B45309]' }}">{{ $entry->visibility === 'public' ? 'Cliente' : 'Interna' }}</span>
                        </div>
                        <h2 class="mt-4 text-lg font-semibold">{{ $entry->title }}</h2>
                        <p class="mt-2 text-sm text-[#6B7280]">{{ $entry->project->name }} · {{ $entry->project->client->name }}</p>
                    </div>
                </a>
            </article>
        @empty
            <div class="rounded-[20px] border border-dashed border-[#D8D0C3] bg-white p-8 text-center text-[#6B7280] md:col-span-2 xl:col-span-3">Aún no hay entradas visuales.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $entries->links() }}</div>
</x-layouts.admin>
