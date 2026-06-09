<x-layouts.admin title="Nueva entrada visual">
    <div class="mb-8">
        <p class="mb-2 text-sm font-medium text-[#B88746]">{{ $project->name }}</p>
        <h1 class="text-3xl font-semibold tracking-tight">Nueva entrada visual</h1>
        <p class="mt-3 text-[#6B7280]">Sube una imagen, video o audio para documentar el avance del proyecto.</p>
    </div>

    <form method="POST" action="{{ route('admin.projects.visual-entries.store', $project) }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        @csrf
        <section class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <label for="media" class="flex min-h-64 cursor-pointer flex-col items-center justify-center rounded-[20px] border border-dashed border-[#D8D0C3] bg-[#FAFAF7] p-8 text-center">
                <span class="text-lg font-semibold">Subir archivo visual</span>
                <span class="mt-2 max-w-md text-sm leading-6 text-[#6B7280]">Acepta imagen, video o audio hasta 50 MB. El archivo quedará protegido por autorización.</span>
                <input id="media" name="media" type="file" accept="image/*,video/*,audio/*" class="mt-5 text-sm" required>
            </label>
            @error('media') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror

            <div class="mt-6">
                <label for="description" class="text-sm font-medium">Descripción</label>
                <textarea id="description" name="description" rows="6" class="mt-2 w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm leading-6 outline-none focus:border-[#B88746]">{{ old('description') }}</textarea>
                @error('description') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
        </section>

        <aside class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <div>
                <label for="title" class="text-sm font-medium">Título</label>
                <input id="title" name="title" value="{{ old('title') }}" class="mt-2 w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm outline-none focus:border-[#B88746]" required>
                @error('title') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="mt-5">
                <label for="type" class="text-sm font-medium">Tipo</label>
                <select id="type" name="type" class="mt-2 w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <fieldset class="mt-5 space-y-3">
                <legend class="text-sm font-medium">Visibilidad</legend>
                <label class="flex items-center gap-3 rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm"><input type="radio" name="visibility" value="public" @checked(old('visibility', 'public') === 'public')> Cliente</label>
                <label class="flex items-center gap-3 rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm"><input type="radio" name="visibility" value="internal" @checked(old('visibility') === 'internal')> Interna</label>
                @error('visibility') <p class="text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
            </fieldset>

            <div class="mt-6 flex flex-col gap-3">
                <button class="rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">Publicar entrada</button>
                <a href="{{ route('admin.projects.show', $project) }}" class="rounded-xl border border-[#E7E2D8] bg-white px-5 py-3 text-center text-sm font-semibold hover:bg-[#FAFAF7]">Cancelar</a>
            </div>
        </aside>
    </form>
</x-layouts.admin>
