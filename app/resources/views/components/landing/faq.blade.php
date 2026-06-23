{{--
    FAQ (sección 09).

    Acordeon CSS-only usando `<details>` / `<summary>` para
    accesibilidad nativa (teclado, screen readers, sin
    JavaScript). El estilado mantiene el tono editorial y la
    paleta del design system.
--}}
<section id="faq" class="border-b border-[#E7E2D8] bg-white py-24 sm:py-32" aria-labelledby="cf-faq-title">
    <div class="mx-auto max-w-4xl px-6 lg:px-10">
        <x-landing.section-marker number="09" eyebrow="Preguntas reales" />

        <h2
            id="cf-faq-title"
            class="cf-reveal mt-6 max-w-3xl text-3xl font-semibold leading-[1.1] tracking-[-0.02em] text-[#111827] sm:text-4xl lg:text-5xl"
            data-cf-word-reveal
        >
            Lo que preguntan antes de instalar.
        </h2>

        <div class="cf-stagger-in mt-14 space-y-0 border-t border-[#E7E2D8]">
            @php
                $faqs = [
                    [
                        'q' => '¿Esto es para mí si soy freelance o agencia pequeña?',
                        'a' => 'Sí. ClientFlow está pensado para 1-10 personas que quieren dar un portal profesional a sus clientes sin contratar un SaaS externo ni montar una infraestructura compleja.',
                    ],
                    [
                        'q' => '¿Cuánto cuesta?',
                        'a' => 'El producto es open source MIT: gratis para siempre. Tu único coste es el hosting (un plan compartido básico de PHP+MySQL sobra) y, si activas el asistente IA, la API key del provider que elijas (OpenAI, Anthropic o compatible).',
                    ],
                    [
                        'q' => '¿Cuánto tarda en estar funcionando?',
                        'a' => 'Con Docker local: 5-10 minutos. En un hosting compartido: clonar el repo, composer install, php artisan migrate, y listo. La guía de instalación viene en el README.',
                    ],
                    [
                        'q' => '¿Qué pasa con mis datos?',
                        'a' => 'Son tuyos. Todo vive en tu MySQL y en tu sistema de archivos. Nada se envía a servicios externos salvo lo que tu configures explícitamente (por ejemplo, el provider de IA para el asistente del cliente).',
                    ],
                    [
                        'q' => '¿Mis clientes necesitan cuenta?',
                        'a' => 'Sí, pero no tienen que pasar por un formulario de registro público. Tú les envías una invitación por email, ellos eligen password y entran al portal. El proceso está pensado para que no se sientan en una web técnica.',
                    ],
                    [
                        'q' => '¿Puedo integrarlo con mi IDE?',
                        'a' => 'Sí. ClientFlow expone un MCP server con tools de solo lectura. Cursor, Claude Code, Continue, Cline y cualquier cliente MCP compatible pueden consultar tus proyectos sin tocar el repositorio.',
                    ],
                ];
            @endphp
            @foreach ($faqs as $faq)
                <details class="cf-faq cf-reveal group border-b border-[#E7E2D8]">
                    <summary class="flex cursor-pointer list-none items-start justify-between gap-6 py-5 transition-colors hover:text-[#8B5CF6] [&::-webkit-details-marker]:hidden">
                        <span class="text-base font-semibold text-[#111827] sm:text-lg">{{ $faq['q'] }}</span>
                        <span class="cf-faq-chevron grid h-6 w-6 shrink-0 place-items-center text-[#6B7280] transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </span>
                    </summary>
                    <div class="pb-6 pr-12 text-sm leading-7 text-[#6B7280] sm:text-base">
                        {{ $faq['a'] }}
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</section>
