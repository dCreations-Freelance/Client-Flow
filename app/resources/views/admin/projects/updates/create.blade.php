<x-layouts.admin title="Nueva actualización">
    <div class="mb-8">
        <p class="mb-2 text-sm font-medium text-[#B88746]">{{ $project->name }}</p>
        <h1 class="text-3xl font-semibold tracking-tight">Nueva actualización</h1>
        <p class="mt-3 text-[#6B7280]">Publica un avance claro para el cliente o una nota interna para tu equipo.</p>
    </div>

    <form method="POST" action="{{ route('admin.projects.updates.store', $project) }}" class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        @csrf
        <section class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <div>
                <label for="title" class="text-sm font-medium">Título</label>
                <input id="title" name="title" value="{{ old('title') }}" class="mt-2 w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm outline-none focus:border-[#B88746]" required>
                @error('title') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
            <div class="mt-5">
                <label for="content" class="text-sm font-medium">Mensaje</label>
                <textarea id="content" name="content" rows="10" class="mt-2 w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm leading-6 outline-none focus:border-[#B88746]" required>{{ old('content') }}</textarea>
                @error('content') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
        </section>

        <aside class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <h2 class="text-xl font-semibold">Publicación</h2>
            <p class="mt-2 text-sm leading-6 text-[#6B7280]">Las notas internas nunca aparecen en el portal cliente.</p>

            <fieldset class="mt-5 space-y-3">
                <legend class="text-sm font-medium">Visibilidad</legend>
                <label class="flex items-center gap-3 rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm">
                    <input type="radio" name="visibility" value="public" @checked(old('visibility', 'public') === 'public')>
                    Cliente
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm">
                    <input type="radio" name="visibility" value="internal" @checked(old('visibility') === 'internal')>
                    Interna
                </label>
                @error('visibility') <p class="text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
            </fieldset>

            <label class="mt-5 flex items-start gap-3 rounded-xl bg-[#FAFAF7] p-4 text-sm">
                <input type="checkbox" name="notify_client" value="1" @checked(old('notify_client')) class="mt-1">
                <span><span class="font-medium">Notificar por email</span><br><span class="text-[#6B7280]">Solo se enviará si la actualización es visible para cliente.</span></span>
            </label>

            <div class="mt-6 flex flex-col gap-3">
                <button class="rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">Publicar</button>
                <a href="{{ route('admin.projects.show', $project) }}" class="rounded-xl border border-[#E7E2D8] bg-white px-5 py-3 text-center text-sm font-semibold hover:bg-[#FAFAF7]">Cancelar</a>
            </div>
        </aside>
    </form>
</x-layouts.admin>
