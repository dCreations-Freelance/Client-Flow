{{--
    Cita editorial (sección 08).

    Un solo testimonio grande, con tipografía display, en vez
    del clasico grid 3x de testimonios con foto y estrellas.
    La cita aparece palabra a palabra y se firma con un nombre
    y un proyecto ficticio pero realista.

    La eleccion editorial (una sola cita, en grande) refuerza
    el tono "reportaje" de la pagina y evita el recurso
    gastado de los testimonios de 5 estrellas.
--}}
<section class="border-b border-[#E7E2D8] bg-[#FAFAF7] py-24 sm:py-32" aria-labelledby="cf-quote-title">
    <div class="mx-auto max-w-4xl px-6 lg:px-10">
        <x-landing.section-marker number="08" eyebrow="En su voz" />

        <blockquote class="cf-reveal mt-10">
            <p class="text-2xl font-medium leading-[1.3] tracking-[-0.01em] text-[#111827] sm:text-3xl lg:text-[2.5rem]">
                <span class="text-[#8B5CF6]">"</span>Mi cliente dejó de mandarme whatsapps a las 23h. La web está al 68%, lo ve sin que yo le cuente, y cuando me escribe es porque tiene una decisión real que tomar, no para preguntar <span class="text-[#111827]">"cómo va eso"</span>.<span class="text-[#8B5CF6]">"</span>
            </p>
            <footer class="mt-10 flex items-center gap-4">
                <div class="grid h-12 w-12 place-items-center rounded-full bg-[#2563EB] text-sm font-semibold text-white">MR</div>
                <div>
                    <p class="text-sm font-semibold text-[#111827]">Marina R.</p>
                    <p class="text-sm text-[#6B7280]">Diseñadora web freelance · proyecto de clínica dental</p>
                </div>
                <div class="ml-auto hidden items-center gap-1 text-[#8B5CF6] sm:flex">
                    <span>★★★★★</span>
                </div>
            </footer>
        </blockquote>
    </div>
</section>
