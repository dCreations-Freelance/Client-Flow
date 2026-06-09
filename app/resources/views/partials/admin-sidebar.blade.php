<aside class="border-b border-[#E7E2D8] bg-white lg:sticky lg:top-0 lg:h-screen lg:w-[260px] lg:border-b-0 lg:border-r">
    <div class="flex h-[72px] items-center px-5">
        <a href="{{ route('admin.dashboard') }}" class="text-lg font-semibold tracking-tight">ClientFlow</a>
    </div>
    <nav class="flex gap-2 overflow-x-auto px-4 pb-4 text-sm lg:block lg:space-y-1 lg:overflow-visible">
        @foreach ([
            ['Dashboard', route('admin.dashboard'), request()->routeIs('admin.dashboard')],
            ['Clientes', route('admin.clients.index'), request()->routeIs('admin.clients.*')],
            ['Proyectos', route('admin.projects.index'), request()->routeIs('admin.projects.*')],
            ['Comentarios', '#', false],
            ['Entregables', '#', false],
            ['Diario visual', route('admin.visual-entries.index'), request()->routeIs('admin.visual-entries.*')],
            ['Documentos', '#', false],
            ['Centro IA', '#', false],
            ['Actividad', '#', false],
            ['Ajustes', '#', false],
        ] as [$item, $href, $active])
            <a href="{{ $href }}" class="block whitespace-nowrap rounded-xl px-3 py-2.5 font-medium {{ $active ? 'bg-[#F4F1EA] text-[#111827]' : 'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]' }}">
                {{ $item }}
            </a>
        @endforeach
    </nav>
</aside>
