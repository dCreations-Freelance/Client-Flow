{{--
Sidebar del panel de administracion.

Lista los modulos disponibles. El item activo se detecta
comparando el prefijo de la ruta actual. Se usa `Route::has(...)`
para no romper la vista si la ruta aun no existe.

El item "Notificaciones" apunta a la pagina de preferencias
del admin (en fase futura se podra sustituir por el feed
completo de notificaciones). El badge de "no leidos" del
chat se ha eliminado: la campana del header (`NotificationBell`)
muestra el total de in-app y ya no necesitamos un segundo
contador en el sidebar.
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

        @if (Route::has('admin.agent-templates.index'))
            <a href="{{ route('admin.agent-templates.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'admin.agent-templates') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Templates IA</span>
            </a>
        @endif

        @if (Route::has('admin.project-templates.index'))
            <a href="{{ route('admin.project-templates.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'admin.project-templates') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Plantillas</span>
            </a>
        @endif

        @if (Route::has('admin.ai.config.edit'))
            <a href="{{ route('admin.ai.config.edit') }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'admin.ai') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Configuracion IA</span>
            </a>
        @endif

        @if (Route::has('admin.notifications.preferences'))
            <a href="{{ route('admin.notifications.preferences') }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors {{ str_starts_with($current, 'admin.notifications') ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                <span>Notificaciones</span>
            </a>
        @endif
    </nav>
</aside>