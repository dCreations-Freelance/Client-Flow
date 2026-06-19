{{--
    Dual view (seccion 04).

    Split-screen que muestra la diferencia entre lo que ve el
    admin y lo que ve el cliente para el MISMO proyecto. La
    linea central se anima al entrar en viewport. Ambos lados
    aparecen desde direcciones opuestas.

    La idea es vender el pitch central: "tu cliente solo ve lo
    que necesita ver, tu mantienes control total".
--}}
<section id="dual-view" class="border-b border-[#E7E2D8] bg-[#FAFAF7] py-24 sm:py-32" aria-labelledby="cf-dual-title">
    <div class="mx-auto max-w-7xl px-6 lg:px-10">
        <x-landing.section-marker number="04" eyebrow="Dos vistas, un mismo proyecto" />

        <h2
            id="cf-dual-title"
            class="cf-reveal mt-6 max-w-3xl text-3xl font-semibold leading-[1.1] tracking-[-0.02em] text-[#111827] sm:text-4xl lg:text-5xl"
            data-cf-word-reveal
        >
            Tu tienes el control. Tu cliente, la tranquilidad.
        </h2>

        <p class="cf-reveal mt-5 max-w-2xl text-base leading-7 text-[#6B7280] sm:text-lg">
            El portal detecta el rol y muestra solo lo que corresponde. Documentos privados, configuracion y metricas se quedan en tu panel.
        </p>

        <div class="relative mt-16 grid gap-10 lg:grid-cols-2 lg:gap-16">
            {{-- Linea central animada: solo visible en desktop --}}
            <div aria-hidden="true" class="pointer-events-none absolute left-1/2 top-12 hidden h-[calc(100%-6rem)] w-px -translate-x-1/2 lg:block">
                <div class="h-full w-full bg-gradient-to-b from-transparent via-[#8B5CF6] to-transparent opacity-30"></div>
                <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full border border-[#E7E2D8] bg-white px-2.5 py-0.5 text-[10px] font-mono font-semibold text-[#8B5CF6]">VS</span>
            </div>

            {{-- Lado admin --}}
            <div class="cf-reveal-left">
                <div class="mb-4 flex items-center justify-between">
                    <span class="cf-section-marker"><span class="text-[#8B5CF6]">A</span><span>Admin</span></span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-[#F5F3FF] px-2.5 py-0.5 text-[10px] font-medium text-[#8B5CF6]">Control total</span>
                </div>

                <div class="overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
                    {{-- Sidebar --}}
                    <div class="flex">
                        <div class="hidden w-16 shrink-0 border-r border-[#E7E2D8] bg-[#FAFAF7] p-2 sm:block">
                            <div class="grid h-7 w-7 place-items-center rounded-md bg-[#111827] text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 6h6v6H4zM14 6h6v6h-6zM4 16h6v4H4zM14 16h6v4h-6z"/></svg>
                            </div>
                            <div class="mt-4 space-y-1.5">
                                <div class="h-1.5 w-8 rounded-full bg-[#E7E2D8]"></div>
                                <div class="h-1.5 w-6 rounded-full bg-[#E7E2D8]"></div>
                                <div class="h-1.5 w-7 rounded-full bg-[#2563EB]"></div>
                                <div class="h-1.5 w-5 rounded-full bg-[#E7E2D8]"></div>
                            </div>
                        </div>

                        {{-- Contenido --}}
                        <div class="flex-1 p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">Tu panel</p>
                                    <p class="mt-0.5 text-sm font-semibold text-[#111827]">Sonrisa Sana</p>
                                </div>
                                <span class="rounded-full bg-[#F0FDF4] px-2 py-0.5 text-[10px] font-medium text-[#16A34A]">68%</span>
                            </div>

                            <div class="mt-4 space-y-2">
                                <div class="rounded-md border border-[#E7E2D8] bg-[#FAFAF7] p-2.5 text-[11px]">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-[#111827]">Documento privado</span>
                                        <span class="rounded bg-[#F5F3FF] px-1.5 py-0.5 text-[9px] font-medium text-[#8B5CF6]">PRIVADO</span>
                                    </div>
                                    <p class="mt-1 text-[#6B7280]">Arquitectura backend v3</p>
                                </div>
                                <div class="rounded-md border border-[#E7E2D8] bg-[#FAFAF7] p-2.5 text-[11px]">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-[#111827]">Documento publico</span>
                                        <span class="rounded bg-[#EFF6FF] px-1.5 py-0.5 text-[9px] font-medium text-[#2563EB]">PUBLICO</span>
                                    </div>
                                    <p class="mt-1 text-[#6B7280]">Convenios del proyecto</p>
                                </div>
                                <div class="rounded-md border border-[#E7E2D8] bg-[#FAFAF7] p-2.5 text-[11px]">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-[#111827]">Tarea asignada</span>
                                        <span class="rounded bg-[#FEF2F2] px-1.5 py-0.5 text-[9px] font-medium text-[#DC2626]">CRITICA</span>
                                    </div>
                                    <p class="mt-1 text-[#6B7280]">Validar formulario de cita</p>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between rounded-lg bg-[#FAFAF7] p-2.5 text-[10px]">
                                <span class="text-[#6B7280]">Tiempo registrado hoy</span>
                                <span class="font-mono font-semibold text-[#111827]">02:43:18</span>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="mt-6 space-y-2 text-sm text-[#6B7280]">
                    <li class="flex items-start gap-2"><span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-[#8B5CF6]"></span> Documentos privados y metricas de tiempo.</li>
                    <li class="flex items-start gap-2"><span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-[#8B5CF6]"></span> Drag &amp; drop entre columnas, edicion rapida.</li>
                    <li class="flex items-start gap-2"><span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-[#8B5CF6]"></span> Configuracion de IA, agentes y miembros.</li>
                </ul>
            </div>

            {{-- Lado cliente --}}
            <div class="cf-reveal-right">
                <div class="mb-4 flex items-center justify-between">
                    <span class="cf-section-marker"><span class="text-[#2563EB]">C</span><span>Cliente</span></span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EFF6FF] px-2.5 py-0.5 text-[10px] font-medium text-[#2563EB]">Solo lo que importa</span>
                </div>

                <div class="overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
                    <div class="p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">Tu proyecto</p>
                                <p class="mt-0.5 text-sm font-semibold text-[#111827]">Web corporativa</p>
                            </div>
                            <span class="rounded-full bg-[#F0FDF4] px-2 py-0.5 text-[10px] font-medium text-[#16A34A]">68%</span>
                        </div>

                        <div class="mt-4 space-y-2">
                            <div class="rounded-md border border-[#E7E2D8] bg-[#FAFAF7] p-2.5 text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-[#111827]">Documento</span>
                                    <span class="rounded bg-[#EFF6FF] px-1.5 py-0.5 text-[9px] font-medium text-[#2563EB]">PUBLICO</span>
                                </div>
                                <p class="mt-1 text-[#6B7280]">Convenios del proyecto</p>
                            </div>
                            <div class="rounded-md border border-[#E7E2D8] bg-[#FAFAF7] p-2.5 text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-[#111827]">Tus tareas</span>
                                    <span class="rounded bg-[#FEF2F2] px-1.5 py-0.5 text-[9px] font-medium text-[#DC2626]">PENDIENTE</span>
                                </div>
                                <p class="mt-1 text-[#6B7280]">Validar formulario de cita</p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between rounded-lg bg-[#F5F3FF] p-2.5 text-[10px]">
                            <span class="font-medium text-[#8B5CF6]">Preguntale a la IA</span>
                            <span class="rounded bg-[#8B5CF6] px-1.5 py-0.5 text-[9px] font-medium text-white">CHAT</span>
                        </div>

                        <div class="mt-3 flex items-center gap-2 rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-2 text-[10px]">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#2563EB] text-[8px] font-semibold text-white">DR</span>
                            <span class="text-[#6B7280]">Daniel respondio: "Listo, lo reviso manana"</span>
                        </div>
                    </div>
                </div>

                <ul class="mt-6 space-y-2 text-sm text-[#6B7280]">
                    <li class="flex items-start gap-2"><span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-[#2563EB]"></span> Kanban en solo lectura, documentos publicos.</li>
                    <li class="flex items-start gap-2"><span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-[#2563EB]"></span> Chat directo, adjuntos y asistente IA.</li>
                    <li class="flex items-start gap-2"><span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-[#2563EB]"></span> Cero configuracion tecnica, instalable como PWA.</li>
                </ul>
            </div>
        </div>
    </div>
</section>
