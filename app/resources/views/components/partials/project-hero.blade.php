{{--
    Hero del detalle de proyecto: titulo, badges, breadcrumb y slot
    de acciones.

    Es la cabecera visible en `admin.projects.show` y
    `portal.projects.show`. Se renderiza `sticky` para que el CTA
    principal este siempre a mano al hacer scroll, pero por debajo
    del header del layout (que ya ocupa `top-0` con su propio
    fondo blur). Por eso usamos `top-16`, que es justo la altura
    del header pegado.

    Slots:
        - actions: grupo de botones contextuales. La vista padre
          decide que acciones mostrar segun el area (admin/portal)
          y los permisos del usuario.

    Props:
        - project: el modelo Project (con `organization` cargado).
        - crumbs: array para `<x-partials.project-breadcrumbs>`.

    Uso:
        <x-partials.project-hero :project="$project" :crumbs="[...]">
            <x-slot:actions>
                <a href="..." class="...">Abrir tablero</a>
            </x-slot:actions>
        </x-partials.project-hero>
--}}
@props([
    'project',
    'crumbs' => [],
    'unreadMessages' => 0,
    'showStatus' => true,
    'showArchived' => true,
])

<header
    {{ $attributes->merge(['class' => 'sticky top-16 z-20 -mx-6 mb-6 border-b border-[#E7E2D8] bg-white/90 px-6 py-5 backdrop-blur lg:-mx-8 lg:px-8']) }}
>
    <div class="space-y-3">
        <x-partials.project-breadcrumbs :crumbs="$crumbs" />

        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 flex-1 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold text-[#111827]">
                        {{ $project->name }}
                    </h1>

                    @if ($showStatus)
                        <x-partials.status-badge :status="$project->status" />
                    @endif

                    @if ($showArchived && $project->isArchived())
                        <span class="inline-flex items-center rounded-full bg-[#F4F1EA] px-2.5 py-0.5 text-xs font-medium text-[#6B7280]">
                            Archivado
                        </span>
                    @endif
                </div>

                @if ($project->organization)
                    <p class="text-sm text-[#6B7280]">
                        @if ($project->is_visible_to_client || auth()->user()?->isAdmin())
                            <span class="text-[#9CA3AF]">Organizacion:</span>
                            <span class="font-medium text-[#111827]">{{ $project->organization->name }}</span>
                        @endif
                    </p>
                @endif
            </div>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>
</header>
