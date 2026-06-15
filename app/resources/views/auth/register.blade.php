<x-layouts.auth title="Crear cuenta">
    <h2 class="mb-1 text-2xl font-semibold">Crea tu cuenta</h2>
    <p class="mb-6 text-sm text-[#6B7280]">Empieza a seguir tus proyectos en ClientFlow.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <x-ui.input name="name" label="Nombre" required autocomplete="name" />

        <x-ui.input name="email" type="email" label="Email" required autocomplete="email" />

        <x-ui.input name="password" type="password" label="Contrasena" required autocomplete="new-password" />

        <x-ui.input name="password_confirmation" type="password" label="Repetir contrasena" required autocomplete="new-password" />

        <x-ui.button type="submit" variant="primary" class="w-full">Crear cuenta</x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-[#6B7280]">
        Ya tienes cuenta?
        <a href="{{ route('login') }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Acceder</a>
    </p>
</x-layouts.auth>
