{{--
    Editor de documentos markdown en vivo.

    Diseno segun `docs/DESIGN.md`:
    - Tabs: Editor | Preview.
    - Toolbar con acciones de insercion de markdown.
    - Textarea sincronizado con `wire:model.live="content"`.
    - Radio de visibilidad abajo, junto a los botones Guardar / Cancelar.

    La interaccion con el cursor/seleccion del textarea se hace
    mediante un pequeno script inline (sin librerias, sin Alpine).
    El script envia eventos a Livewire con la posicion y el texto
    seleccionado, y luego Livewire reemite `editor-content-updated`
    para que el script reposicione el cursor tras la insercion.
--}}

<div x-data="{}" class="space-y-4">
    {{-- Titulo del documento --}}
    <div>
        <label for="doc-title" class="mb-1 block text-sm font-medium text-[#111827]">Titulo</label>
        <input
            id="doc-title"
            type="text"
            wire:model.live="title"
            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
            placeholder="Titulo del documento"
            maxlength="200"
        >
        @error('title')
            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
        @enderror
    </div>

    {{-- Card con tabs y toolbar --}}
    <div class="overflow-hidden rounded-xl border border-[#E7E2D8] bg-white">
        {{-- Tabs --}}
        <div class="flex border-b border-[#E7E2D8] bg-[#FAFAF7]">
            <button
                type="button"
                wire:click="setTab('editor')"
                class="px-4 py-2 text-sm font-medium transition-colors {{ $activeTab === 'editor' ? 'border-b-2 border-[#2563EB] text-[#2563EB]' : 'text-[#6B7280] hover:text-[#111827]' }}"
            >
                Editor
            </button>
            <button
                type="button"
                wire:click="setTab('preview')"
                class="px-4 py-2 text-sm font-medium transition-colors {{ $activeTab === 'preview' ? 'border-b-2 border-[#2563EB] text-[#2563EB]' : 'text-[#6B7280] hover:text-[#111827]' }}"
            >
                Vista previa
            </button>
            <span class="ml-auto self-center pr-3 text-xs text-[#9CA3AF]">
                Markdown
            </span>
        </div>

        {{-- Toolbar (visible solo en tab editor) --}}
        @if ($activeTab === 'editor')
            <div class="flex flex-wrap items-center gap-1 border-b border-[#E7E2D8] bg-white px-2 py-1.5">
                <button
                    type="button"
                    data-md-action="bold"
                    class="rounded px-2 py-1 text-xs font-semibold text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                    title="Negrita (selecciona texto y pulsa)"
                >B</button>
                <button
                    type="button"
                    data-md-action="italic"
                    class="rounded px-2 py-1 text-xs italic text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                    title="Cursiva"
                >I</button>
                <span class="mx-1 h-4 w-px bg-[#E7E2D8]"></span>
                <button
                    type="button"
                    data-md-action="h2"
                    class="rounded px-2 py-1 text-xs font-semibold text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                    title="Encabezado"
                >H2</button>
                <button
                    type="button"
                    data-md-action="link"
                    class="rounded px-2 py-1 text-xs text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                    title="Enlace"
                >Enlace</button>
                <button
                    type="button"
                    data-md-action="list"
                    class="rounded px-2 py-1 text-xs text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                    title="Lista"
                >Lista</button>
                <button
                    type="button"
                    data-md-action="code"
                    class="rounded px-2 py-1 text-xs font-mono text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                    title="Codigo"
                >Codigo</button>
            </div>
        @endif

        {{-- Editor / Preview --}}
        @if ($activeTab === 'editor')
            <textarea
                id="doc-textarea"
                wire:model.live="content"
                class="block w-full resize-y border-0 bg-white px-4 py-3 font-mono text-sm focus:outline-none focus:ring-0"
                style="min-height: 360px;"
                placeholder="Escribe en markdown. Ejemplos:&#10;# Titulo&#10;## Subtitulo&#10;**negrita**, *cursiva*, [link](https://...)&#10;- item de lista"
            ></textarea>
        @else
            <div
                id="doc-preview"
                class="prose prose-sm max-w-none px-4 py-4"
                style="color: #111827;"
            >
                @if (trim($content) === '')
                    <p class="text-sm italic text-[#9CA3AF]">La vista previa aparecera aqui en cuanto escribas algo.</p>
                @else
                    {!! \Illuminate\Support\Str::markdown($content) !!}
                @endif
            </div>
        @endif
    </div>

    @error('content')
        <p class="text-xs text-[#DC2626]">{{ $message }}</p>
    @enderror

    {{-- Visibilidad + acciones --}}
    <div class="flex flex-col gap-4 border-t border-[#E7E2D8] pt-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <span class="mb-2 block text-sm font-medium text-[#111827]">Visibilidad</span>
            <div class="flex flex-col gap-2 sm:flex-row">
                @foreach ($visibilities as $option)
                    <label class="inline-flex items-center gap-2 text-sm text-[#111827]">
                        <input
                            type="radio"
                            wire:model.live="visibility"
                            value="{{ $option->value }}"
                            class="h-4 w-4 border-[#E7E2D8] text-[#2563EB] focus:ring-[#2563EB]"
                        >
                        <span>
                            <span class="font-medium">{{ $option->label() }}</span>
                            <span class="ml-1 text-xs text-[#6B7280]">
                                @if ($option->isPublic())
                                    Visible para clientes del proyecto.
                                @else
                                    Solo administradores.
                                @endif
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('visibility')
                <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
            <button
                type="button"
                wire:click="cancel"
                class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]"
            >
                Cancelar
            </button>
            <button
                type="button"
                wire:click="save"
                class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
            >
                {{ $mode === 'create' ? 'Crear documento' : 'Guardar cambios' }}
            </button>
        </div>
    </div>
</div>

@script
<script>
    (function () {
        const textarea = document.getElementById('doc-textarea');
        if (!textarea) {
            return;
        }

        // Reposiciona el cursor tras una actualizacion del componente.
        // Se ejecuta cuando Livewire reemite `editor-content-updated`.
        document.addEventListener('livewire:init', () => {
            Livewire.on('editor-content-updated', (event) => {
                const caret = event.caret ?? textarea.value.length;
                // El DOM puede no estar listo en el siguiente tick si
                // Livewire re-renderizo; usamos requestAnimationFrame.
                requestAnimationFrame(() => {
                    const ta = document.getElementById('doc-textarea');
                    if (ta) {
                        ta.focus();
                        ta.setSelectionRange(caret, caret);
                    }
                });
            });
        });

        // Toolbar: lee la seleccion actual y la envia al backend.
        // Las acciones se identifican con `data-md-action` para que
        // añadir nuevas sea trivial.
        document.addEventListener('click', function (event) {
            const target = event.target.closest('[data-md-action]');
            if (!target) {
                return;
            }
            event.preventDefault();

            const ta = document.getElementById('doc-textarea');
            if (!ta) {
                return;
            }

            const start = ta.selectionStart ?? 0;
            const end = ta.selectionEnd ?? 0;
            const selection = ta.value.substring(start, end);
            const action = target.getAttribute('data-md-action');

            let before = '';
            let after = '';
            let snippet = '';

            switch (action) {
                case 'bold':
                    before = '**';
                    after = '**';
                    snippet = selection || 'texto en negrita';
                    break;
                case 'italic':
                    before = '*';
                    after = '*';
                    snippet = selection || 'texto en cursiva';
                    break;
                case 'h2':
                    snippet = '\n## ' + (selection || 'Encabezado') + '\n';
                    break;
                case 'link':
                    snippet = '[' + (selection || 'texto') + '](https://)';
                    break;
                case 'list':
                    snippet = '\n- ' + (selection || 'item') + '\n- item\n';
                    break;
                case 'code':
                    before = '`';
                    after = '`';
                    snippet = selection || 'codigo';
                    break;
                default:
                    return;
            }

            if (before !== '' || after !== '') {
                // Wrap: hay texto a envolver.
                Livewire.dispatch('editor-wrap', {
                    before: before,
                    after: after,
                    selection: snippet,
                    start: start,
                    end: end,
                });
            } else {
                // Insert: solo se anade el snippet.
                Livewire.dispatch('editor-insert', {
                    snippet: snippet,
                    selection: selection,
                    start: start,
                    end: end,
                });
            }
        });
    })();
</script>
@endscript
