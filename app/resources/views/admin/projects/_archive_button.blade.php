{{--
    Boton contextual para archivar / desarchivar un proyecto.

    Se incluye como partial desde:
    - `admin.projects.show` (hub) — donde se pasa `as="kebab-item"`
      para que se estilice como item de menu dentro del
      `project-hero::kebabActions`.
    - `admin.projects.index` (futuro) u otras vistas donde se
      quiere como boton "standalone" con su propio estilo.

    Props:
        - project: el modelo Project.
        - as: `'kebab-item'` o `'standalone'` (default). Define
          el estilo visual del boton.
--}}
@props([
    'project',
    'as' => 'standalone',
    'confirm' => null,
])

@php
    $isArchived = $project->isArchived();
    $action = $isArchived ? 'unarchive' : 'archive';
    $label = $isArchived ? 'Desarchivar' : 'Archivar';
    $route = route('admin.projects.'.$action, $project);
    $defaultConfirm = $isArchived
        ? 'Deseas desarchivar este proyecto?'
        : 'Archivar el proyecto lo ocultara del portal del cliente. Continuar?';
    $confirmText = $confirm ?? $defaultConfirm;
@endphp

<form method="POST" action="{{ $route }}" onsubmit="return confirm('{{ $confirmText }}')">
    @csrf
    <button
        type="submit"
        @if ($as === 'kebab-item')
            class="block w-full px-4 py-2 text-left text-sm font-medium text-[#DC2626] transition-colors hover:bg-[#FEF2F2]"
        @else
            class="inline-flex items-center justify-center rounded-lg border border-[#D97706] bg-white px-4 py-2 text-sm font-medium text-[#D97706] transition-colors hover:bg-[#FFFBEB] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#D97706] focus-visible:ring-offset-2"
        @endif
    >
        {{ $label }}
    </button>
</form>
