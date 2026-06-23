{{--
    Migas de pan para la pagina de detalle de un proyecto.

    Cada crumb es un array con `label` y, opcionalmente, `href`.
    El ultimo crumb se renderiza como texto plano (sin enlace) y
    con tono primary para identificar donde estamos.

    Uso:
        <x-partials.project-breadcrumbs :crumbs="[
            ['label' => 'Organizaciones', 'href' => route('admin.organizations.index')],
            ['label' => $project->organization->name, 'href' => route('admin.organizations.show', $project->organization)],
            ['label' => $project->name],
        ]" />
--}}
@props([
    'crumbs' => [],
])

@if (count($crumbs) > 0)
    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1 text-xs text-[#6B7280]">
        @foreach ($crumbs as $index => $crumb)
            @if ($index > 0)
                <span aria-hidden="true" class="text-[#9CA3AF]">›</span>
            @endif

            @if ($loop->last)
                <span class="font-medium text-[#111827]">{{ $crumb['label'] }}</span>
            @else
                <a
                    href="{{ $crumb['href'] ?? '#' }}"
                    class="hover:text-[#111827] hover:underline"
                >
                    {{ $crumb['label'] }}
                </a>
            @endif
        @endforeach
    </nav>
@endif
