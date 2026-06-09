<x-layouts.admin title="Timeline">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-[#B88746]">{{ $project->client->name }}</p>
            <h1 class="text-3xl font-semibold tracking-tight">Timeline de {{ $project->name }}</h1>
            <p class="mt-3 text-[#6B7280]">Historial de actualizaciones públicas e internas del proyecto.</p>
        </div>
        <a href="{{ route('admin.projects.updates.create', $project) }}" class="rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">Nueva actualización</a>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-[#DCFCE7] bg-[#DCFCE7] px-4 py-3 text-sm font-medium text-[#15803D]">{{ session('status') }}</div>
    @endif

    <section class="rounded-[24px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
        <div class="space-y-6">
            @forelse ($updates as $update)
                <article class="relative border-l border-[#E7E2D8] pl-6">
                    <span class="absolute -left-2 top-1 h-4 w-4 rounded-full {{ $update->visibility === 'public' ? 'bg-[#B88746]' : 'bg-[#9CA3AF]' }}"></span>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-[#F4F1EA] px-3 py-1 text-xs font-medium">{{ $update->visibility === 'public' ? 'Cliente' : 'Interna' }}</span>
                        <span class="text-xs text-[#9CA3AF]">{{ $update->published_at?->format('d/m/Y H:i') }} · {{ $update->author->name }}</span>
                    </div>
                    <h2 class="mt-3 text-lg font-semibold">{{ $update->title }}</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#6B7280]">{{ $update->content }}</p>
                </article>
            @empty
                <div class="rounded-[20px] border border-dashed border-[#D8D0C3] bg-[#FAFAF7] p-8 text-center">
                    <p class="text-lg font-semibold">Todavía no hay actualizaciones</p>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-[#6B7280]">Publica el primer avance para que el timeline empiece a contar la historia del proyecto.</p>
                </div>
            @endforelse
        </div>
    </section>
</x-layouts.admin>
