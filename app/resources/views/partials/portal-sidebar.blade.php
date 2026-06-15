{{--
    Sidebar del portal cliente. En fase 1 solo muestra el enlace a
    Dashboard. En fases siguientes se anaden proyectos, calendario y
    notificaciones. Se usa el mismo patron visual que admin pero con
    anchura 220px para diferenciarse sutilmente.
--}}
@php
    $current = request()->route()?->getName() ?? '';
@endphp

<aside class="hidden w-55 shrink-0 border-r border-[#E7E2D8] bg-white px-4 py-6 lg:flex lg:flex-col" style="width: 220px;">
    <a href="{{ route('home') }}" class="mb-8 px-2 text-xl font-semibold tracking-tight">ClientFlow</a>

    <nav class="flex flex-1 flex-col gap-1 text-sm">
        <a href="{{ route('portal.dashboard') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'portal.dashboard') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
            <span>Inicio</span>
        </a>

        <span class="mt-4 px-3 text-xs font-medium uppercase tracking-wider text-[#9CA3AF]">Pronto</span>

        <span class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-[#9CA3AF]">Proyectos</span>
        <span class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-[#9CA3AF]">Calendario</span>
    </nav>
</aside>
