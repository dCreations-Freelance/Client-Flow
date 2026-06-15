{{--
    Sidebar del panel de administracion.

    Lista los modulos disponibles. El item activo se detecta
    comparando el prefijo de la ruta actual. Se usa `Route::has(...)`
    para no romper la vista si la ruta aun no existe.
--}}
@php
    $current = request()->route()?->getName() ?? '';
@endphp

<aside class="hidden w-60 shrink-0 border-r border-[#E7E2D8] bg-white px-4 py-6 lg:flex lg:flex-col">
    <a href="{{ route('home') }}" class="mb-8 px-2 text-xl font-semibold tracking-tight">ClientFlow</a>

    <nav class="flex flex-1 flex-col gap-1 text-sm">
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'admin.dashboard') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
            <span>Panel</span>
        </a>

        @if (Route::has('admin.organizations.index'))
            <a href="{{ route('admin.organizations.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'admin.organizations') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Organizaciones</span>
            </a>
        @endif

        @if (Route::has('admin.projects.index'))
            <a href="{{ route('admin.projects.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'admin.projects') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Proyectos</span>
            </a>
        @endif

        <span class="mt-4 px-3 text-xs font-medium uppercase tracking-wider text-[#9CA3AF]">Pronto</span>

        <span class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-[#9CA3AF]">Plantillas IA</span>
    </nav>
</aside>
