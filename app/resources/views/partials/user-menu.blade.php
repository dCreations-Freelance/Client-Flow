{{--
    Menu de usuario en la cabecera. Muestra las iniciales en un avatar
    circular y un enlace a logout. En fases siguientes se anadira un
    dropdown con opciones (perfil, configuracion).
--}}
@auth
    <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#2563EB] text-xs font-medium text-white">
            {{ strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]">
                Salir
            </button>
        </form>
    </div>
@endauth
