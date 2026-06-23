{{--
    Campana de notificaciones in-app.

    Renderiza el icono de campana con badge rojo y un dropdown
    con las ultimas 20 notificaciones del usuario. La lista
    la materializa el componente Livewire `Shared\NotificationBell`
    y la pasa ya serializada al template.

    Comportamiento clave:
    - El polling `wire:poll.30s` mantiene el contador fresco sin
      WebSockets.
    - `wire:click.away` cierra el dropdown al hacer click fuera.
    - Cada notificacion es un enlace con `wire:click.prevent`
      para que la marcamos como leida antes de navegar.
--}}
<div class="relative" x-data="{ open: @entangle('open') }">
    <button
        type="button"
        wire:click="toggleOpen"
        wire:poll.30s
        class="relative flex h-9 w-9 items-center justify-center rounded-full text-[#6B7280] transition-colors hover:bg-[#F4F1EA] hover:text-[#111827]"
        aria-label="Notificaciones"
    >
        {{-- Icono de campana (SVG inline, sin dependencia externa). --}}
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-[#DC2626] px-1 text-[10px] font-semibold text-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    @if ($open)
        <div
            class="absolute right-0 z-20 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-[#E7E2D8] bg-white shadow-lg"
            wire:click.away="close"
        >
            <div class="flex items-center justify-between border-b border-[#E7E2D8] px-4 py-3">
                <h3 class="text-sm font-semibold">Notificaciones</h3>
                @if ($unreadCount > 0)
                    <button
                        type="button"
                        wire:click="markAllAsRead"
                        class="text-xs font-medium text-[#2563EB] hover:text-[#1D4ED8]"
                    >
                        Marcar todas como leidas
                    </button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                @forelse ($items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        @if ($item['url'])
                            wire:click.prevent="$dispatch('open-notification', { id: '{{ $item['id'] }}' })"
                        @endif
                        @class([
                            'flex flex-col gap-1 border-b border-[#F4F1EA] px-4 py-3 text-sm transition-colors hover:bg-[#FAFAF7]',
                            'bg-[#F0F7FF]' => $item['is_unread'],
                        ])
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span @class([
                                'font-medium',
                                'text-[#111827]' => $item['is_unread'],
                                'text-[#6B7280]' => ! $item['is_unread'],
                            ])>
                                {{ $item['title'] }}
                            </span>
                            @if ($item['is_unread'])
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#2563EB]"></span>
                            @endif
                        </div>
                        @if (! empty($item['body']))
                            <p class="text-xs text-[#6B7280]">{{ $item['body'] }}</p>
                        @endif
                        <div class="mt-1 flex items-center justify-between text-[11px] text-[#9CA3AF]">
                            @if (! empty($item['project_name']))
                                <span>{{ $item['project_name'] }}</span>
                            @endif
                            <span>{{ $item['created_at'] }}</span>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-[#6B7280]">
                        Aun no tienes notificaciones.
                    </div>
                @endforelse
            </div>

            <div class="border-t border-[#E7E2D8] bg-[#FAFAF7] px-4 py-2 text-center">
                <a
                    href="{{ $preferencesUrl }}"
                    class="text-xs font-medium text-[#6B7280] hover:text-[#111827]"
                >
                    Gestionar preferencias
                </a>
            </div>
        </div>
    @endif
</div>

{{--
    Listener para el evento custom `open-notification` que la
    campana dispara al hacer click en una entrada. Asi marcamos
    la notificacion como leida via el backend y luego navegamos
    a la URL del payload. Usamos un handler inline en lugar de
    un metodo del componente para que la navegacion sea
    inmediata (Livewire haria un round-trip extra si lo
    hicieramos por ahi).
--}}
<script>
    document.addEventListener('open-notification', (event) => {
        const id = event.detail?.id;
        if (!id) return;

        // Llamamos al backend para marcar como leida. Usamos
        // `fetch` con `credentials: same-origin` para que la
        // cookie de sesion viaje. El backend devuelve un redirect
        // 302 que no nos interesa; solo queremos el efecto
        // secundario de marcar como leida.
        fetch(`/${window.location.pathname.split('/')[1]}/notifications/${id}/read`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        }).catch(() => null);

        // Navegamos a la URL del payload. La obtenemos del
        // `href` del elemento que disparo el click.
        const link = event.target?.closest('a');
        const href = link?.getAttribute('href');
        if (href && href !== '#') {
            window.location.href = href;
        }
    });
</script>
