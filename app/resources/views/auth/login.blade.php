<x-layouts.auth title="Acceder">
    <h2 class="mb-1 text-2xl font-semibold">Bienvenido de vuelta</h2>
    <p class="mb-6 text-sm text-[#6B7280]">Introduce tus credenciales para continuar.</p>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-ui.input name="email" type="email" label="Email" required autocomplete="email" />

        <x-ui.input name="password" type="password" label="Contrasena" required autocomplete="current-password" />

        <label class="flex items-center gap-2 text-sm text-[#6B7280]">
            <input type="checkbox" name="remember" value="1" class="rounded border-[#E7E2D8] text-[#2563EB] focus:ring-[#2563EB]">
            Recuerdame
        </label>

        <div class="flex items-center justify-between text-sm">
            <a href="{{ route('password.request') }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Olvide mi contrasena</a>
        </div>

        <x-ui.button type="submit" variant="primary" class="w-full">Acceder</x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-[#6B7280]">
        Aun no tienes cuenta?
        <a href="{{ route('register') }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Crear cuenta</a>
    </p>
</x-layouts.auth>
