{{--
    Seccion MCP server (05).

    Es el diferenciador técnico de ClientFlow. Mostramos una
    llamada realista (`mcp.get_project_status(...)`) con un
    typing effect que se dispara al entrar en viewport, seguido
    de la respuesta JSON materializándose línea a línea.

    El `data-cf-typing` lo lee `resources/js/landing.js` y emite
    `cf:typed-done` al terminar; ese evento arranca la cascada
    de las líneas de respuesta (`.cf-code-line`).
--}}
<section id="mcp" class="border-b border-[#E7E2D8] bg-white py-24 sm:py-32" aria-labelledby="cf-mcp-title">
    <div class="mx-auto max-w-7xl px-6 lg:px-10">
        <div class="grid gap-12 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">

            <div>
                <x-landing.section-marker number="05" eyebrow="MCP server" />

                <h2
                    id="cf-mcp-title"
                    class="cf-reveal mt-6 text-3xl font-semibold leading-[1.1] tracking-[-0.02em] text-[#111827] sm:text-4xl lg:text-5xl"
                    data-cf-word-reveal
                >
                    Tu IDE hablando con tus proyectos.
                </h2>

                <p class="cf-reveal mt-5 max-w-xl text-base leading-7 text-[#6B7280] sm:text-lg">
                    ClientFlow expone un servidor MCP con tools de solo lectura. Cursor, Claude Code o cualquier cliente compatible pueden consultar el estado de un proyecto, sus tareas y la documentación privada sin tener que subir nada al repositorio.
                </p>

                <ul class="cf-stagger-in mt-8 space-y-3 text-sm text-[#111827]">
                    <li class="cf-reveal flex items-start gap-3 rounded-xl border border-[#E7E2D8] bg-white p-4">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-[#F5F3FF] text-[#8B5CF6]">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                        </span>
                        <div>
                            <p class="font-semibold">Tools de solo lectura</p>
                            <p class="mt-0.5 text-[#6B7280]">list_projects, get_project, list_tasks, get_documents, search_documents, get_project_status.</p>
                        </div>
                    </li>
                    <li class="cf-reveal flex items-start gap-3 rounded-xl border border-[#E7E2D8] bg-white p-4">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-[#F5F3FF] text-[#8B5CF6]">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <div>
                            <p class="font-semibold">Autenticación por API token</p>
                            <p class="mt-0.5 text-[#6B7280]">Cada developer genera el suyo desde su perfil. Revocable, scope por proyecto.</p>
                        </div>
                    </li>
                    <li class="cf-reveal flex items-start gap-3 rounded-xl border border-[#E7E2D8] bg-white p-4">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-[#F5F3FF] text-[#8B5CF6]">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </span>
                        <div>
                            <p class="font-semibold">Documentos privados consultables</p>
                            <p class="mt-0.5 text-[#6B7280]">El agente del IDE ve el detalle técnico que tu cliente no necesita ver.</p>
                        </div>
                    </li>
                </ul>
            </div>

            {{-- Editor de código simulado --}}
            <div class="cf-reveal">
                <div class="overflow-hidden rounded-2xl border border-[#E7E2D8] bg-[#111827] shadow-[0_20px_60px_rgba(17,24,39,0.15)]">
                    {{-- Chrome del editor --}}
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-2.5">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-[#DC2626]"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-[#D97706]"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-[#16A34A]"></span>
                        </div>
                        <span class="font-mono text-[10px] text-[#9CA3AF]">~/clientflow/mcp-call.md</span>
                        <span class="font-mono text-[10px] text-[#6B7280]">MCP</span>
                    </div>

                    {{-- Codigo --}}
                    <div class="space-y-1 p-5 font-mono text-[12px] leading-6">
                        <p><span class="text-[#6B7280]"># Tu agente en Cursor, Claude Code o el que prefieras</span></p>
                        <p class="mt-2">
                            <span class="text-[#8B5CF6]">mcp</span><span class="text-[#E7E2D8]">.</span><span class="text-[#2563EB]">get_project_status</span><span class="text-[#E7E2D8]">(</span><span class="text-[#16A34A]">project_id</span><span class="text-[#E7E2D8]">=</span><span class="text-[#D97706]">42</span><span class="text-[#E7E2D8]">)</span><span
                                class="cf-typing"
                                data-cf-typing
                                data-cf-text=" → progreso, tareas abiertas, próximo deadline"
                                data-cf-speed="22"
                            ></span>
                        </p>

                        <p class="cf-code-line mt-4 text-[#6B7280]">{</p>
                        <p class="cf-code-line">&nbsp;&nbsp;<span class="text-[#2563EB]">"name"</span>: <span class="text-[#16A34A]">"Web corporativa clínica dental"</span>,</p>
                        <p class="cf-code-line">&nbsp;&nbsp;<span class="text-[#2563EB]">"status"</span>: <span class="text-[#16A34A]">"in_progress"</span>,</p>
                        <p class="cf-code-line">&nbsp;&nbsp;<span class="text-[#2563EB]">"progress"</span>: <span class="text-[#D97706]">68</span>,</p>
                        <p class="cf-code-line">&nbsp;&nbsp;<span class="text-[#2563EB]">"open_tasks"</span>: <span class="text-[#D97706]">5</span>,</p>
                        <p class="cf-code-line">&nbsp;&nbsp;<span class="text-[#2563EB]">"critical_tasks"</span>: <span class="text-[#D97706]">2</span>,</p>
                        <p class="cf-code-line">&nbsp;&nbsp;<span class="text-[#2563EB]">"next_deadline"</span>: <span class="text-[#16A34A]">"2026-06-21T18:00:00Z"</span>,</p>
                        <p class="cf-code-line">&nbsp;&nbsp;<span class="text-[#2563EB]">"client_visible"</span>: <span class="text-[#D97706]">true</span></p>
                        <p class="cf-code-line text-[#6B7280]">}</p>
                    </div>
                </div>

                <p class="mt-4 text-center text-xs text-[#6B7280] sm:text-left">
                    Sin tocar el repo, sin subir documentación a un servicio externo. Tu IDE pregunta, ClientFlow responde.
                </p>
            </div>
        </div>
    </div>
</section>
