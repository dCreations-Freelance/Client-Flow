<x-layouts.auth title="Iniciar sesión">
    <x-slot:nav>
        ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-medium text-[#111827]">Crear cuenta</a>
    </x-slot:nav>

    <div class="mb-8">
        <p class="mb-2 text-sm font-medium text-[#B88746]">Portal privado</p>
        <h1 class="text-3xl font-semibold tracking-tight">Accede a tu portal</h1>
        <p class="mt-3 text-sm leading-6 text-[#6B7280]">Consulta avances, documentos, entregables y aprobaciones en un único espacio profesional.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full rounded-xl border border-[#E7E2D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#B88746]">
            @error('email') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-2 block text-sm font-medium">Contraseña</label>
            <input id="password" name="password" type="password" required class="w-full rounded-xl border border-[#E7E2D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#B88746]">
            @error('password') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-[#6B7280]">
            <input type="checkbox" name="remember" class="rounded border-[#D8D0C3] text-[#111827]">
            Mantener sesión iniciada
        </label>

        <button class="w-full rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">
            Iniciar sesión
        </button>
    </form>
</x-layouts.auth>
