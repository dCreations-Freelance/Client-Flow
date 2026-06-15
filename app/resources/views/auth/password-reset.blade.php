<x-layouts.auth title="Nueva contrasena">
    <h2 class="mb-1 text-2xl font-semibold">Restablecer contrasena</h2>
    <p class="mb-6 text-sm text-[#6B7280]">Introduce tu nueva contrasena.</p>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <x-ui.input name="email" type="email" label="Email" :value="$email" required autocomplete="email" />

        <x-ui.input name="password" type="password" label="Nueva contrasena" required autocomplete="new-password" />

        <x-ui.input name="password_confirmation" type="password" label="Repetir contrasena" required autocomplete="new-password" />

        <x-ui.button type="submit" variant="primary" class="w-full">Restablecer</x-ui.button>
    </form>
</x-layouts.auth>
