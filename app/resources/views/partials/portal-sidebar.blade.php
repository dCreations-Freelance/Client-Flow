{{--
Sidebar del portal cliente.

Se actualizo en la fase 2 para anadir el enlace a "Proyectos"
como vista dedicada. En esta fase (transversal de notificaciones)
se ha eliminado el badge de "mensajes no leidos" del sidebar:
la campana del header (`NotificationBell`) lo absorbe y un
segundo contador resultaba redundante.

Se anade un enlace directo a "Notificaciones" para que el
cliente pueda gestionar sus preferencias sin pasar por el
dropdown de la campana.
--}}
@php
    $current = request()->route()?->getName() ?? '';
@endphp

<aside class="hidden w-55 shrink-0 border-r border-[#E7E2D8] bg-white px-4 py-6 lg:flex lg:flex-col"
    style="width: 220px;">
    <a href="{{ route('home') }}" class="mb-8 px-2 text-xl font-semibold tracking-tight">ClientFlow</a>

    <nav class="flex flex-1 flex-col gap-1 text-sm">
        <a href="{{ route('portal.dashboard') }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'portal.dashboard') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
            <span>Inicio</span>
        </a>

        @if (Route::has('portal.projects.index'))
            <a href="{{ route('portal.projects.index') }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'portal.projects') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Proyectos</span>
            </a>
        @endif

        @if (Route::has('portal.notifications.preferences'))
            <a href="{{ route('portal.notifications.preferences') }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'portal.notifications') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Notificaciones</span>
            </a>
        @endif

        <span class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-[#9CA3AF]">Calendario</span>
    </nav>
</aside>