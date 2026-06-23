<x-layouts.portal :title="'Preferencias de notificaciones'">
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-[#111827]">Preferencias de notificaciones</h1>
            <p class="mt-1 text-sm text-[#6B7280]">
                Decide como quieres recibir cada tipo de aviso. Puedes desactivar la campana
                in-app, los emails, o ambos. Los cambios se aplican a partir del siguiente evento.
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-[#16A34A] bg-[#F0FDF4] px-4 py-3 text-sm text-[#166534]">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('portal.notifications.preferences.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="overflow-hidden rounded-xl border border-[#E7E2D8] bg-white">
                <div class="grid grid-cols-12 gap-4 border-b border-[#E7E2D8] bg-[#FAFAF7] px-6 py-3 text-xs font-medium uppercase tracking-wider text-[#6B7280]">
                    <div class="col-span-6">Evento</div>
                    <div class="col-span-3 text-center">En la app</div>
                    <div class="col-span-3 text-center">Por email</div>
                </div>

                @foreach ($preferences as $key => $row)
                    <div class="grid grid-cols-12 items-start gap-4 border-b border-[#F4F1EA] px-6 py-4 last:border-b-0">
                        <div class="col-span-6">
                            <p class="text-sm font-medium text-[#111827]">{{ $row['event']->label() }}</p>
                            <p class="mt-1 text-xs text-[#6B7280]">{{ $row['event']->description() }}</p>
                        </div>
                        <div class="col-span-3 flex items-center justify-center pt-1">
                            <input
                                type="hidden"
                                name="preferences[{{ $loop->index }}][event]"
                                value="{{ $row['event']->value }}"
                            >
                            <input
                                type="hidden"
                                name="preferences[{{ $loop->index }}][in_app]"
                                value="0"
                            >
                            <label class="inline-flex cursor-pointer items-center">
                                <input
                                    type="checkbox"
                                    name="preferences[{{ $loop->index }}][in_app]"
                                    value="1"
                                    @checked($row['in_app'])
                                    class="h-4 w-4 rounded border-[#D8D0C3] text-[#2563EB] focus:ring-[#2563EB]"
                                >
                            </label>
                        </div>
                        <div class="col-span-3 flex items-center justify-center pt-1">
                            <input
                                type="hidden"
                                name="preferences[{{ $loop->index }}][email]"
                                value="0"
                            >
                            <label class="inline-flex cursor-pointer items-center">
                                <input
                                    type="checkbox"
                                    name="preferences[{{ $loop->index }}][email]"
                                    value="1"
                                    @checked($row['email'])
                                    class="h-4 w-4 rounded border-[#D8D0C3] text-[#2563EB] focus:ring-[#2563EB]"
                                >
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('portal.dashboard') }}" class="text-sm font-medium text-[#6B7280] hover:text-[#111827]">
                    Cancelar
                </a>
                <x-ui.button type="submit">Guardar preferencias</x-ui.button>
            </div>
        </form>

        <div class="rounded-xl border border-[#E7E2D8] bg-white p-6">
            <h2 class="text-base font-semibold">Como funcionan los envios</h2>
            <ul class="mt-3 space-y-2 text-sm text-[#6B7280]">
                <li>Los <strong>mensajes del chat</strong> llegan por in-app y email; desactivarlos evita que te avisen del chat (pero seguiras viendolo al abrir el proyecto).</li>
                <li>Las <strong>invitaciones a eventos del calendario</strong> siempre llegan por email; la campana es opcional.</li>
                <li>El <strong>resumen diario</strong> solo se envia por email una vez al dia a las 09:00. Si lo desactivas, no recibiras el email matutino.</li>
            </ul>
        </div>
    </div>
</x-layouts.portal>
