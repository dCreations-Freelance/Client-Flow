<x-layouts.auth title="Aceptar invitacion">
    @push('meta')
        {{-- El token de invitacion va en la URL: impedir que se filtre --}}
        {{-- al referer de cualquier recurso externo (CDN, analytics, etc.). --}}
        <meta name="referrer" content="no-referrer">
    @endpush

    <h2 class="mb-1 text-2xl font-semibold">Te han invitado a {{ $invitation->organization->name }}</h2>
    <p class="mb-6 text-sm text-[#6B7280]">Crea tu cuenta para unirte al equipo. La invitacion expira el {{ $invitation->expires_at->format('d/m/Y H:i') }}.</p>

    <form method="POST" action="{{ route('invitation.accept', ['token' => $token]) }}" class="space-y-4">
        @csrf

        <x-ui.input name="email" type="email" label="Email" :value="$invitation->email" disabled />

        <x-ui.input name="name" label="Tu nombre" required autofocus />

        <x-ui.input name="password" type="password" label="Contrasena" required autocomplete="new-password" />

        <x-ui.input name="password_confirmation" type="password" label="Repetir contrasena" required autocomplete="new-password" />

        <x-ui.button type="submit" variant="primary" class="w-full">Aceptar y crear cuenta</x-ui.button>
    </form>
</x-layouts.auth>
