{{--
    Bento grid con las funciones principales (sección 03).

    Cada card tiene un mini-mockup representativo. El grid es
    asimétrico: la card "Kanban vitaminado" ocupa el doble, y
    "MCP server" tiene borde animado. La idea es que el usuario
    escanee y sienta la variedad del producto sin necesidad de
    descripción larga.
--}}
<section id="features" class="border-b border-[#E7E2D8] bg-white py-24 sm:py-32" aria-labelledby="cf-features-title">
    <div class="mx-auto max-w-7xl px-6 lg:px-10">
        <x-landing.section-marker number="03" eyebrow="Funciones" />

        <h2
            id="cf-features-title"
            class="cf-reveal mt-6 max-w-3xl text-3xl font-semibold leading-[1.1] tracking-[-0.02em] text-[#111827] sm:text-4xl lg:text-5xl"
            data-cf-word-reveal
        >
            Un único espacio para todo lo que pasa en un proyecto.
        </h2>

        <p class="cf-reveal mt-5 max-w-2xl text-base leading-7 text-[#6B7280] sm:text-lg">
            Cada pieza del producto es pequeña, honesta y resolutiva. Esto es lo que vive dentro de tu portal.
        </p>

        <div class="cf-stagger-in mt-14 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 lg:auto-rows-[minmax(0,1fr)]">
            {{-- Card 1: Kanban vitaminado (doble ancho) --}}
            <article class="cf-reveal cf-bento-card group relative col-span-1 overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-6 lg:col-span-4 lg:row-span-2 lg:p-8">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EFF6FF] px-2.5 py-0.5 text-xs font-medium text-[#2563EB]">
                            Kanban
                        </span>
                        <h3 class="mt-3 text-2xl font-semibold tracking-[-0.01em] text-[#111827]">Vitaminado, no genérico.</h3>
                        <p class="mt-2 max-w-md text-sm leading-6 text-[#6B7280]">Columnas configurables, prioridades, tipo de tarea, estimación, fechas límite, subtareas y filtros. Arrastrar y soltar, sin configuraciones ocultas.</p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-3 gap-2">
                    <div class="rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">Por hacer</p>
                        <div class="mt-2 space-y-1.5">
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Login con magic link</p>
                                <div class="mt-1.5 flex items-center justify-between text-[10px]">
                                    <span class="rounded bg-[#FEF2F2] px-1.5 py-0.5 font-medium text-[#DC2626]">Crítica</span>
                                    <span class="text-[#9CA3AF]">4h</span>
                                </div>
                            </div>
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Filtros de búsqueda</p>
                                <div class="mt-1.5 flex items-center justify-between text-[10px]">
                                    <span class="rounded bg-[#FFFBEB] px-1.5 py-0.5 font-medium text-[#D97706]">Alta</span>
                                    <span class="rounded bg-[#F5F3FF] px-1.5 py-0.5 font-medium text-[#8B5CF6]">Feature</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">En curso</p>
                        <div class="mt-2 space-y-1.5">
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Checkout Stripe</p>
                                <div class="mt-1.5 flex items-center justify-between text-[10px]">
                                    <span class="rounded bg-[#FFFBEB] px-1.5 py-0.5 font-medium text-[#D97706]">Alta</span>
                                    <span class="text-[#9CA3AF]">8h</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">Hecho</p>
                        <div class="mt-2 space-y-1.5">
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Wireframes v2</p>
                            </div>
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Setup analytics</p>
                            </div>
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Deploy staging</p>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            {{-- Card 2: Documentos --}}
            <article class="cf-reveal cf-bento-card col-span-1 overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-6 lg:col-span-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#F5F3FF] px-2.5 py-0.5 text-xs font-medium text-[#8B5CF6]">Documentos</span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Markdown, privados o públicos.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">Editor con preview. Lo que es para ti, queda para ti.</p>

                <div class="mt-5 overflow-hidden rounded-lg border border-[#E7E2D8]">
                    <div class="flex items-center gap-1 border-b border-[#E7E2D8] bg-[#FAFAF7] px-3 py-1.5 text-[10px] font-medium text-[#6B7280]">
                        <span class="rounded bg-white px-2 py-0.5 text-[#111827] shadow-sm">Editor</span>
                        <span class="px-2 py-0.5">Preview</span>
                    </div>
                    <div class="bg-white p-3 font-mono text-[11px] leading-5 text-[#6B7280]">
                        <p><span class="text-[#8B5CF6]">#</span> Convenios del proyecto</p>
                        <p class="mt-1">- Horario: 9:00 - 18:00 CET</p>
                        <p>- Canal único: el portal</p>
                        <p>- Status semanal automático</p>
                    </div>
                </div>
            </article>

            {{-- Card 3: Chat --}}
            <article class="cf-reveal cf-bento-card col-span-1 overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-6 lg:col-span-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EFF6FF] px-2.5 py-0.5 text-xs font-medium text-[#2563EB]">Chat</span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Por proyecto, con visto.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">Mensajes, adjuntos, indicador de leído. Polling cada 5s.</p>

                <div class="mt-5 space-y-2">
                    <div class="flex justify-start">
                        <div class="max-w-[80%] rounded-2xl rounded-bl-sm border border-[#E7E2D8] bg-white px-3 py-2 text-xs text-[#111827]">
                            Te paso el mockup de la home para revisión.
                            <div class="mt-1 text-[10px] text-[#9CA3AF]">10:28</div>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <div class="max-w-[80%] rounded-2xl rounded-br-sm bg-[#2563EB] px-3 py-2 text-xs text-white">
                            Perfecto, lo miro esta tarde.
                            <div class="mt-1 flex items-center justify-end gap-1 text-[10px] text-blue-100">
                                <span>10:30</span>
                                <span class="text-[#2563EB]" style="color: white;">✓✓</span>
                            </div>
                        </div>
                    </div>
                    <div class="mx-auto w-fit rounded-full bg-[#F4F1EA] px-3 py-0.5 text-[10px] text-[#6B7280]">
                        Carlos completó "Setup analytics"
                    </div>
                </div>
            </article>

            {{-- Card 4: Calendario --}}
            <article class="cf-reveal cf-bento-card col-span-1 overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-6 lg:col-span-3">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#F0FDF4] px-2.5 py-0.5 text-xs font-medium text-[#16A34A]">Calendario</span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Reuniones, deadlines y milestones.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">Vista mensual o semanal. Los deadlines de las tareas se pintan solos.</p>

                <div class="mt-5 grid grid-cols-7 gap-1 text-center text-[10px]">
                    @php
                        $today = 14;
                        $events = [4 => 'milestone', 9 => 'meeting', 12 => 'deadline', 14 => 'meeting', 22 => 'meeting', 27 => 'deadline'];
                    @endphp
                    @foreach (range(1, 35) as $cell)
                        @php
                            $isCurrentMonth = $cell <= 31;
                            $hasEvent = $isCurrentMonth && array_key_exists($cell, $events);
                            $isToday = $isCurrentMonth && $cell === $today;
                            $eventColor = match($events[$cell] ?? null) {
                                'meeting' => 'bg-[#2563EB]',
                                'deadline' => 'bg-[#D97706]',
                                'milestone' => 'bg-[#16A34A]',
                                default => null,
                            };
                        @endphp
                        <div class="relative aspect-square rounded-md border {{ $isToday ? 'border-[#2563EB]' : 'border-transparent' }} {{ $isCurrentMonth ? 'bg-[#FAFAF7]' : 'bg-transparent' }} {{ $isCurrentMonth ? 'text-[#111827]' : 'text-[#9CA3AF]' }} p-1 text-[10px]">
                            @if ($isCurrentMonth) {{ $cell }} @endif
                            @if ($hasEvent)
                                <span class="absolute bottom-1 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full {{ $eventColor }}"></span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </article>

            {{-- Card 5: IA --}}
            <article class="cf-reveal cf-bento-card col-span-1 overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-6 lg:col-span-3">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#F5F3FF] px-2.5 py-0.5 text-xs font-medium text-[#8B5CF6]">Asistente IA</span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Tu cliente pregunta, la IA responde con contexto.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">El system prompt recibe el estado del proyecto, las tareas y los documentos públicos. OpenAI, Anthropic o el provider que prefieras.</p>

                <div class="mt-5 space-y-2">
                    <div class="flex justify-start">
                        <div class="max-w-[85%] rounded-2xl rounded-bl-sm border border-[#E7E2D8] bg-[#FAFAF7] px-3 py-2 text-xs text-[#111827]">
                            ¿Cómo va el módulo de citas?
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-br-sm bg-[#8B5CF6] px-3 py-2 text-xs text-white">
                            Está al 68%. La integración con Google Maps la cierran mañana y luego toca testing del flujo de confirmación. Tienes 2 tareas críticas pendientes de tu lado: validar el formulario final y confirmar el copy del email de confirmación.
                        </div>
                    </div>
                </div>
            </article>

            {{-- Card 6: MCP server con borde animado --}}
            <article class="cf-reveal cf-bento-card cf-glow-card group col-span-1 overflow-hidden rounded-2xl bg-white p-6 lg:col-span-3">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#111827] px-2.5 py-0.5 text-xs font-medium text-white">MCP server</span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Conecta tu IDE a tus proyectos.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">Tools de solo lectura para Cursor, Claude Code o cualquier cliente MCP. Tus documentos privados son consultables sin subirlos al repo.</p>

                <div class="mt-5 overflow-hidden rounded-lg bg-[#111827] p-4 font-mono text-[11px] leading-5 text-[#E7E2D8]">
                    <p><span class="text-[#9CA3AF]">// desde tu IDE</span></p>
                    <p class="mt-1"><span class="text-[#8B5CF6]">mcp</span>.<span class="text-[#2563EB]">get_project_status</span>(<span class="text-[#16A34A]">project_id</span>=42)</p>
                    <p class="mt-2 text-[#9CA3AF]">→ progreso, tareas abiertas, próximo deadline</p>
                </div>
            </article>

            {{-- Card 7: PWA --}}
            <article class="cf-reveal cf-bento-card col-span-1 overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-6 lg:col-span-3">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#F4F1EA] px-2.5 py-0.5 text-xs font-medium text-[#6B7280]">PWA</span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Instalable, con push.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">Tu cliente puede añadirla a la pantalla de inicio. Notificaciones push para mensajes y deadlines.</p>

                <div class="mt-5 flex items-center gap-3 rounded-xl border border-[#E7E2D8] bg-[#FAFAF7] p-3">
                    <div class="grid h-9 w-9 place-items-center rounded-lg bg-[#111827] text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <path d="M12 18h.01"/>
                        </svg>
                    </div>
                    <div class="text-xs">
                        <p class="font-semibold text-[#111827]">Añadir ClientFlow a tu pantalla</p>
                        <p class="text-[#6B7280]">Acceso directo, sin abrir el navegador</p>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>
