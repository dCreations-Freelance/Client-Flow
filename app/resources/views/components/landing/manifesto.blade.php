{{--
    Manifiesto (sección 02).

    Cuatro líneas que enumeran lo que ClientFlow elimina. Cada
    línea aparece escalonada al hacer scroll. La promesa se
    refuerza con el subtitulo que sigue al bloque.
--}}
<section class="border-b border-[#E7E2D8] bg-[#FAFAF7] py-24 sm:py-32" aria-labelledby="cf-manifesto-title">
    <div class="mx-auto max-w-7xl px-6 lg:px-10">
        <x-landing.section-marker
            number="02"
            eyebrow="El manifiesto"
        />

        <h2
            id="cf-manifesto-title"
            class="cf-reveal mt-6 max-w-3xl text-3xl font-semibold leading-[1.1] tracking-[-0.02em] text-[#111827] sm:text-4xl lg:text-5xl"
            data-cf-word-reveal
        >
            Todo lo que sobra cuando tu cliente tiene un portal.
        </h2>

        <ol class="cf-stagger-in mt-16 grid gap-3 sm:gap-4">
            @php
                $lines = [
                    ['tag' => 'Sin emails', 'desc' => 'Cero hilos cruzados con adjuntos, reenvíos y "te lo mando otra vez".'],
                    ['tag' => 'Sin WhatsApp', 'desc' => 'Tu teléfono deja de ser el centro de soporte del proyecto.'],
                    ['tag' => 'Sin documentos en repos', 'desc' => 'La documentación del proyecto vive en su sitio, accesible vía MCP desde tu IDE.'],
                    ['tag' => 'Sin recrear agentes IA', 'desc' => 'Tu biblioteca de agentes IA se reutiliza proyecto a proyecto, sin reescribir el system prompt.'],
                ];
            @endphp
            @foreach ($lines as $line)
                <li class="cf-reveal group flex items-start gap-5 rounded-2xl border border-transparent bg-white p-5 transition-all hover:border-[#E7E2D8] hover:shadow-sm sm:p-7">
                    <span class="mt-1 grid h-7 w-7 shrink-0 place-items-center rounded-full border border-[#E7E2D8] bg-[#FAFAF7] text-xs font-mono font-semibold text-[#8B5CF6]">
                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                    </span>
                    <div>
                        <p class="text-xl font-semibold text-[#111827] sm:text-2xl">{{ $line['tag'] }}.</p>
                        <p class="mt-1.5 max-w-2xl text-sm leading-6 text-[#6B7280] sm:text-base">{{ $line['desc'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <p class="cf-reveal mt-12 max-w-2xl text-base leading-7 text-[#6B7280]">
            ClientFlow concentra todo eso en un único espacio al que tu cliente accede con un link. Miras el proyecto una vez, lo entiendes, preguntas lo que necesites y vuelves a tu día.
        </p>
    </div>
</section>
