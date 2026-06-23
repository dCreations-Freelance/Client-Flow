{{--
    Stack (sección 06).

    Lista de tecnologias con su rol, entrada secuencial al
    hacer scroll. El mensaje clave: "stack que tu hosting
    compartido ya entiende, sin obligarte a Redis ni a un
    cluster de workers".
--}}
<section id="stack" class="border-b border-[#E7E2D8] bg-[#FAFAF7] py-24 sm:py-32" aria-labelledby="cf-stack-title">
    <div class="mx-auto max-w-7xl px-6 lg:px-10">
        <x-landing.section-marker number="06" eyebrow="Stack abierto" />

        <h2
            id="cf-stack-title"
            class="cf-reveal mt-6 max-w-3xl text-3xl font-semibold leading-[1.1] tracking-[-0.02em] text-[#111827] sm:text-4xl lg:text-5xl"
            data-cf-word-reveal
        >
            Tecnologías que ya entiendes. Sin sorpresas.
        </h2>

        <p class="cf-reveal mt-5 max-w-2xl text-base leading-7 text-[#6B7280] sm:text-lg">
            PHP, MySQL, Node. Nada de Redis obligatorio, nada de workers permanentes, nada de WebSockets. Lo puedes instalar en el hosting más básico del mercado.
        </p>

        <ul class="cf-stagger-in mt-14 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @php
                $stack = [
                    ['name' => 'PHP', 'version' => '8.3+', 'role' => 'Backend'],
                    ['name' => 'Laravel', 'version' => '13', 'role' => 'Framework'],
                    ['name' => 'Livewire', 'version' => '4', 'role' => 'UI reactiva'],
                    ['name' => 'Tailwind', 'version' => '4', 'role' => 'Estilos'],
                    ['name' => 'MySQL', 'version' => '8.4', 'role' => 'Base de datos'],
                    ['name' => 'Vite', 'version' => '8', 'role' => 'Build'],
                ];
            @endphp
            @foreach ($stack as $item)
                <li class="cf-reveal group rounded-2xl border border-[#E7E2D8] bg-white p-5 transition-all hover:border-[#D8D0C3] hover:shadow-sm">
                    <p class="font-mono text-xs font-semibold text-[#8B5CF6]">{{ $item['version'] }}</p>
                    <p class="mt-2 text-lg font-semibold tracking-tight text-[#111827]">{{ $item['name'] }}</p>
                    <p class="mt-0.5 text-xs text-[#6B7280]">{{ $item['role'] }}</p>
                </li>
            @endforeach
        </ul>

        <div class="cf-reveal mt-12 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-[#E7E2D8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-[#16A34A]">Sin Redis obligatorio</p>
                <p class="mt-2 text-sm leading-6 text-[#6B7280]">Cache y colas funcionan con el driver `database`. Si en el futuro quieres Redis, lo conectas sin romper nada.</p>
            </div>
            <div class="rounded-2xl border border-[#E7E2D8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-[#16A34A]">Sin workers permanentes</p>
                <p class="mt-2 text-sm leading-6 text-[#6B7280]">Las colas corren en sync dentro del request. No necesitas Horizon, ni systemd, ni un supervisor.</p>
            </div>
            <div class="rounded-2xl border border-[#E7E2D8] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-[#16A34A]">Hosting compartido OK</p>
                <p class="mt-2 text-sm leading-6 text-[#6B7280]">PHP-FPM + Nginx + MySQL es lo que ofrece cualquier plan básico. Nada de Kubernetes, nada de Docker en producción.</p>
            </div>
        </div>
    </div>
</section>
