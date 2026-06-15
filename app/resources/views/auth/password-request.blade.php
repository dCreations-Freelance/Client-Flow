<x-layouts.auth title="Recuperar contrasena">
    <h2 class="mb-1 text-2xl font-semibold">Recuperar contrasena</h2>
    <p class="mb-6 text-sm text-[#6B7280]">Te enviaremos un enlace para restablecerla.</p>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-ui.input name="email" type="email" label="Email" required autocomplete="email" />

        <x-ui.button type="submit" variant="primary" class="w-full">Enviar enlace</x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-[#6B7280]">
        <a href="{{ route('login') }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Volver al acceso</a>
    </p>
</x-layouts.auth>
