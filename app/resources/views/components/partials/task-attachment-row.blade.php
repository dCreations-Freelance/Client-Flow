{{--
    Fila de un adjunto de tarea. Se usa en la lista de
    adjuntos de la vista de detalle de tarea (admin y portal).

    Variables:
        $attachment: App\Models\TaskAttachment
        $canDelete: bool (solo admin)
        $downloadRoute: string (nombre de la ruta; se resuelve
            en runtime segun el contexto admin/portal)

    Muestra: icono segun tipo, nombre, autor, tamano,
    fecha relativa, boton Descargar y, si canDelete, boton
    Eliminar.
--}}
@props([
    'attachment',
    'canDelete' => false,
    'downloadRoute' => 'admin.projects.tasks.attachments.download',
])

<li class="flex items-center gap-3 rounded-lg border border-[#E7E2D8] bg-white px-3 py-2.5">
    {{-- Icono segun tipo --}}
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#F4F1EA]">
        @if (str_starts_with($attachment->mime_type, 'image/'))
            <svg class="h-5 w-5 text-[#2563EB]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
        @elseif (str_starts_with($attachment->mime_type, 'application/pdf'))
            <svg class="h-5 w-5 text-[#DC2626]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        @else
            <svg class="h-5 w-5 text-[#6B7280]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        @endif
    </div>

    {{-- Info --}}
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-[#111827]">{{ $attachment->original_name }}</p>
        <p class="text-xs text-[#6B7280]">
            Subido por {{ $attachment->user?->name ?? 'desconocido' }}
            <span class="mx-1">·</span>
            {{ $attachment->human_size }}
            <span class="mx-1">·</span>
            {{ $attachment->created_at?->diffForHumans() }}
        </p>
    </div>

    {{-- Acciones --}}
    <div class="flex shrink-0 items-center gap-1">
        <a
            href="{{ route($downloadRoute, ['project' => $attachment->task->project_id, 'task' => $attachment->task_id, 'attachment' => $attachment->id]) }}"
            class="rounded-md p-1.5 text-[#2563EB] hover:bg-[#EFF6FF]"
            title="Descargar"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
        </a>

        @if ($canDelete)
            <button
                type="button"
                wire:click="delete({{ $attachment->id }})"
                wire:confirm="¿Eliminar este adjunto? Esta accion no se puede deshacer."
                class="rounded-md p-1.5 text-[#DC2626] hover:bg-[#FEF2F2]"
                title="Eliminar"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </button>
        @endif
    </div>
</li>
