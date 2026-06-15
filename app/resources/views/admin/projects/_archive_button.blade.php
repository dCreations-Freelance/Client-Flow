{{--
    Boton contextual para archivar / desarchivar un proyecto. Se
    incluye como partial desde `admin.projects.show` y se reutilizara
    en `admin.projects.index` si fuera necesario.
--}}
@if ($project->isArchived())
    <form method="POST" action="{{ route('admin.projects.unarchive', $project) }}">
        @csrf
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium hover:bg-[#F4F1EA]"
        >
            Desarchivar
        </button>
    </form>
@else
    <form method="POST" action="{{ route('admin.projects.archive', $project) }}">
        @csrf
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg border border-[#D97706] bg-white px-4 py-2 text-sm font-medium text-[#D97706] hover:bg-[#FFFBEB]"
        >
            Archivar
        </button>
    </form>
@endif
