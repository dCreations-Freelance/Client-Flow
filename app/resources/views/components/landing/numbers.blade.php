{{--
    Numeros (seccion 07).

    Tres (o cuatro) contadores que se animan al entrar en
    viewport. `landing.js` lee `data-cf-target`, `data-cf-suffix`
    y `data-cf-duration` para hacer el count-up con easeOutExpo.

    Los numeros no son metricas de marketing vacias: describen
    decisiones tecnicas reales (setup, polling, latencia de
    generacion de IA).
--}}
<section class="border-b border-[#E7E2D8] bg-white py-24 sm:py-32" aria-labelledby="cf-numbers-title">
    <div class="mx-auto max-w-7xl px-6 lg:px-10">
        <x-landing.section-marker number="07" eyebrow="En numeros" />

        <h2
            id="cf-numbers-title"
            class="cf-reveal mt-6 max-w-3xl text-3xl font-semibold leading-[1.1] tracking-[-0.02em] text-[#111827] sm:text-4xl lg:text-5xl"
            data-cf-word-reveal
        >
            Decisiones de diseno que se sienten, no que se anuncian.
        </h2>

        <div class="cf-stagger-in mt-14 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="cf-reveal rounded-2xl border border-[#E7E2D8] bg-[#FAFAF7] p-7">
                <p class="font-mono text-5xl font-semibold tracking-tight text-[#111827] sm:text-6xl">
                    <span class="cf-counter text-[#8B5CF6]" data-cf-target="10" data-cf-duration="1400">0</span>
                </p>
                <p class="mt-3 text-sm font-medium text-[#111827]">Segundos en entender el proyecto</p>
                <p class="mt-1 text-xs leading-5 text-[#6B7280]">Tu cliente abre el portal, ve el estado, entiende donde esta.</p>
            </div>

            <div class="cf-reveal rounded-2xl border border-[#E7E2D8] bg-[#FAFAF7] p-7">
                <p class="font-mono text-5xl font-semibold tracking-tight text-[#111827] sm:text-6xl">
                    <span class="cf-counter text-[#2563EB]" data-cf-target="5" data-cf-suffix="s" data-cf-duration="1200">0</span>
                </p>
                <p class="mt-3 text-sm font-medium text-[#111827]">Polling del chat</p>
                <p class="mt-1 text-xs leading-5 text-[#6B7280]">Mensajes nuevos aparecen sin recargar. Sin WebSocket, sin infraestructura extra.</p>
            </div>

            <div class="cf-reveal rounded-2xl border border-[#E7E2D8] bg-[#FAFAF7] p-7">
                <p class="font-mono text-5xl font-semibold tracking-tight text-[#111827] sm:text-6xl">
                    <span class="cf-counter text-[#16A34A]" data-cf-target="7" data-cf-duration="1200">0</span>
                </p>
                <p class="mt-3 text-sm font-medium text-[#111827]">Tools MCP listas</p>
                <p class="mt-1 text-xs leading-5 text-[#6B7280]">list_projects, get_project, list_tasks, get_task, get_documents, search_documents, get_project_status.</p>
            </div>

            <div class="cf-reveal rounded-2xl border border-[#E7E2D8] bg-[#FAFAF7] p-7">
                <p class="font-mono text-5xl font-semibold tracking-tight text-[#111827] sm:text-6xl">
                    <span class="cf-counter text-[#D97706]" data-cf-target="13" data-cf-duration="1500">0</span>
                </p>
                <p class="mt-3 text-sm font-medium text-[#111827]">Fases del MVP</p>
                <p class="mt-1 text-xs leading-5 text-[#6B7280]">Auth, proyectos, kanban, docs, chat, MCP, IA, calendario, PWA, adjuntos, tiempo, plantillas, feed.</p>
            </div>
        </div>
    </div>
</section>
