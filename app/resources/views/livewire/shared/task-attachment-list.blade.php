{{--
    Lista de adjuntos de una tarea, con su formulario de subida.

    Se renderiza dentro de la vista de detalle de tarea (admin o
    portal). El componente `TaskAttachmentList` ya controla la
    visibilidad del formulario segun el rol del usuario.

    Variables:
        $attachments: \Illuminate\Database\Eloquent\Collection<int, TaskAttachment>
        $canUpload: bool (true si el usuario actual puede subir)
        $canDelete: bool (true si el usuario actual puede eliminar)
--}}
<div>
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-[#111827]">Adjuntos</h3>
        @if ($attachments->isNotEmpty())
            <span class="text-xs text-[#6B7280]">{{ $attachments->count() }} archivo{{ $attachments->count() === 1 ? '' : 's' }}</span>
        @endif
    </div>

    {{-- Lista de adjuntos existentes --}}
    @if ($attachments->isEmpty())
        <p class="text-sm text-[#6B7280]">Aun no hay adjuntos en esta tarea.</p>
    @else
        <ul class="space-y-2">
            @foreach ($attachments as $attachment)
                <x-partials.task-attachment-row
                    :attachment="$attachment"
                    :canDelete="$canDelete"
                    :downloadRoute="'admin.projects.tasks.attachments.download'"
                />
            @endforeach
        </ul>
    @endif

    {{-- Formulario de subida (solo admin) --}}
    @if ($canUpload)
        <div class="mt-4 rounded-lg border border-dashed border-[#E7E2D8] bg-[#FAFAF7] p-4">
            @if (count($pendingAttachments) > 0)
                <ul class="mb-3 space-y-2">
                    @foreach ($pendingAttachments as $index => $file)
                        <li class="flex items-center gap-2 rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm">
                            <svg class="h-4 w-4 shrink-0 text-[#6B7280]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                            </svg>
                            <span class="min-w-0 flex-1 truncate text-[#111827]">{{ $file->getClientOriginalName() }}</span>
                            <span class="text-xs text-[#6B7280]">{{ \App\Models\TaskAttachment::formatBytes($file->getSize()) }}</span>
                            <button
                                type="button"
                                wire:click="removePending({{ $index }})"
                                class="text-[#6B7280] hover:text-[#DC2626]"
                                title="Quitar"
                            >&times;</button>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm text-[#111827] hover:bg-[#F4F1EA]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <span>Seleccionar archivos</span>
                    <input type="file" wire:model="pendingAttachments" multiple class="hidden">
                </label>

                <button
                    type="button"
                    wire:click="upload"
                    wire:loading.attr="disabled"
                    wire:target="upload"
                    class="inline-flex items-center gap-1 rounded-lg bg-[#2563EB] px-3 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8] disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="upload">Subir</span>
                    <span wire:loading wire:target="upload">Subiendo...</span>
                </button>
            </div>

            <p class="mt-2 text-[10px] text-[#9CA3AF]">
                Maximo {{ (int) config('clientflow.attachments.max_files_per_upload', 5) }} archivos,
                {{ (int) config('clientflow.attachments.max_size_kb', 10240) / 1024 }} MB cada uno.
            </p>

            @error('pendingAttachments')
                <p class="mt-2 text-xs text-[#DC2626]">{{ $message }}</p>
            @enderror
            @error('pendingAttachments.*')
                <p class="mt-2 text-xs text-[#DC2626]">{{ $message }}</p>
            @enderror
        </div>
    @endif
</div>
