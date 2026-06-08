<x-layouts.auth title="Crear cuenta">
    <x-slot:nav>
        ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-medium text-[#111827]">Acceder</a>
    </x-slot:nav>

    <div class="mb-8">
        <p class="mb-2 text-sm font-medium text-[#B88746]">Cuenta de cliente</p>
        <h1 class="text-3xl font-semibold tracking-tight">Crear cuenta</h1>
        <p class="mt-3 text-sm leading-6 text-[#6B7280]">Los proyectos aparecerán cuando el administrador los asocie a tu perfil.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="mb-2 block text-sm font-medium">Nombre completo</label>
            <input id="name" name="name" value="{{ old('name') }}" required autofocus class="w-full rounded-xl border border-[#E7E2D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#B88746]">
            @error('name') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-2 block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-[#E7E2D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#B88746]">
            @error('email') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-2 block text-sm font-medium">Contraseña</label>
            <input id="password" name="password" type="password" required class="w-full rounded-xl border border-[#E7E2D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#B88746]">
            @error('password') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-2 block text-sm font-medium">Confirmar contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-xl border border-[#E7E2D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#B88746]">
        </div>

        <button class="w-full rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">
            Crear cuenta
        </button>
    </form>
</x-layouts.auth>
