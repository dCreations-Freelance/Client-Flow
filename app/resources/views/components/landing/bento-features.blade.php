{{--
    Bento grid con las funciones principales (sección 03).

    Cada card combina un mini-mockup del módulo con un footer
    `cf-bento-detail` que aparece en hover con un metadato
    extra. El grid es asimétrico: la card "Kanban vitaminado"
    ocupa el doble, y "MCP server" tiene borde animado. La idea
    es que el usuario escanee y sienta la variedad del producto
    sin necesidad de descripción larga.
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
            <article class="cf-reveal cf-bento-card group relative col-span-1 flex flex-col overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-5 lg:col-span-4 lg:row-span-2 lg:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EFF6FF] px-2.5 py-0.5 text-xs font-medium text-[#2563EB]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="4" height="16"/><rect x="10" y="4" width="4" height="10"/><rect x="17" y="4" width="4" height="13"/></svg>
                                Kanban
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-medium text-[#16A34A]">
                                <span class="cf-live-dot inline-block h-1.5 w-1.5 rounded-full bg-[#16A34A]"></span>
                                Live
                            </span>
                        </div>
                        <h3 class="mt-3 text-2xl font-semibold tracking-[-0.01em] text-[#111827]">Vitaminado, no genérico.</h3>
                        <p class="mt-2 max-w-md text-sm leading-6 text-[#6B7280]">Columnas configurables, prioridades, tipo de tarea, estimación, fechas límite, subtareas y filtros. Arrastrar y soltar, sin configuraciones ocultas.</p>
                    </div>
                    <button type="button" class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-[#E7E2D8] bg-white px-3 py-1.5 text-xs font-medium text-[#111827] transition-colors hover:border-[#D8D0C3] hover:bg-[#F4F1EA]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Nueva tarea
                    </button>
                </div>

                <div class="mt-5 grid grid-cols-3 gap-2">
                    <div class="rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-2.5">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">Por hacer</p>
                            <span class="text-[10px] font-medium text-[#9CA3AF]">2</span>
                        </div>
                        <div class="mt-2 space-y-1.5">
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Login con magic link</p>
                                <div class="mt-1.5 flex items-center justify-between gap-1 text-[10px]">
                                    <span class="rounded bg-[#FEF2F2] px-1.5 py-0.5 font-medium text-[#DC2626]">Crítica</span>
                                    <span class="text-[#9CA3AF]">4h</span>
                                    <span class="grid h-4 w-4 place-items-center rounded-full bg-[#2563EB] text-[8px] font-semibold text-white">DR</span>
                                </div>
                            </div>
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Filtros de búsqueda</p>
                                <div class="mt-1.5 flex items-center justify-between gap-1 text-[10px]">
                                    <span class="rounded bg-[#FFFBEB] px-1.5 py-0.5 font-medium text-[#D97706]">Alta</span>
                                    <span class="rounded bg-[#F5F3FF] px-1.5 py-0.5 font-medium text-[#8B5CF6]">Feature</span>
                                    <span class="grid h-4 w-4 place-items-center rounded-full bg-[#16A34A] text-[8px] font-semibold text-white">LM</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-2.5">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">En curso</p>
                            <span class="text-[10px] font-medium text-[#9CA3AF]">1</span>
                        </div>
                        <div class="mt-2 space-y-1.5">
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Checkout Stripe</p>
                                <div class="mt-1.5 flex items-center justify-between gap-1 text-[10px]">
                                    <span class="rounded bg-[#FFFBEB] px-1.5 py-0.5 font-medium text-[#D97706]">Alta</span>
                                    <span class="text-[#9CA3AF]">8h</span>
                                    <span class="grid h-4 w-4 place-items-center rounded-full bg-[#D97706] text-[8px] font-semibold text-white">CR</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-2.5">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">Hecho</p>
                            <span class="text-[10px] font-medium text-[#9CA3AF]">3</span>
                        </div>
                        <div class="mt-2 space-y-1.5">
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Wireframes v2</p>
                                <div class="mt-1.5 flex items-center justify-end text-[10px]">
                                    <span class="grid h-4 w-4 place-items-center rounded-full bg-[#8B5CF6] text-[8px] font-semibold text-white">MP</span>
                                </div>
                            </div>
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Setup analytics</p>
                                <div class="mt-1.5 flex items-center justify-end text-[10px]">
                                    <span class="grid h-4 w-4 place-items-center rounded-full bg-[#2563EB] text-[8px] font-semibold text-white">DR</span>
                                </div>
                            </div>
                            <div class="rounded-md border border-[#E7E2D8] bg-white p-2 text-[11px]">
                                <p class="font-medium text-[#111827]">Deploy staging</p>
                                <div class="mt-1.5 flex items-center justify-end text-[10px]">
                                    <span class="grid h-4 w-4 place-items-center rounded-full bg-[#16A34A] text-[8px] font-semibold text-white">LM</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-4">
                    <div class="flex items-center gap-3">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#F4F1EA]">
                            <div class="h-full rounded-full bg-[#2563EB]" style="width: 67%"></div>
                        </div>
                        <span class="text-[10px] font-medium tabular-nums text-[#6B7280]">12/18 · 67%</span>
                    </div>
                    <div class="cf-bento-detail mt-2 border-t border-dashed border-[#E7E2D8] pt-2 text-[10px] text-[#9CA3AF]">
                        3 miembros · 4 sprints · 12h estimadas
                    </div>
                </div>
            </article>

            {{-- Card 2: Documentos --}}
            <article class="cf-reveal cf-bento-card col-span-1 flex flex-col overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-5 lg:col-span-2">
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[#F5F3FF] px-2.5 py-0.5 text-xs font-medium text-[#8B5CF6]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Documentos
                </span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Markdown, privados o públicos.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">Editor con preview. Lo que es para ti, queda para ti.</p>

                <div class="mt-4 overflow-hidden rounded-lg border border-[#E7E2D8]">
                    <div class="flex items-center gap-1 border-b border-[#E7E2D8] bg-[#FAFAF7] px-3 py-1.5 text-[10px] font-medium text-[#6B7280]">
                        <span class="rounded bg-white px-2 py-0.5 text-[#111827] shadow-sm">Editor</span>
                        <span class="px-2 py-0.5">Preview</span>
                    </div>
                    <div class="bg-white p-3 font-mono text-[11px] leading-5 text-[#6B7280]">
                        <p><span class="text-[#8B5CF6]">#</span> Convenios del proyecto</p>
                        <p class="mt-0.5">- Horario: 9:00 - 18:00 CET</p>
                        <p>- Canal único: el portal</p>
                        <p>- Status semanal automático</p>
                        <p>- Cambios vía pull request</p>
                    </div>
                </div>

                <div class="mt-auto pt-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">Recientes</p>
                    <ul class="mt-2 space-y-1.5 text-[11px]">
                        <li class="flex items-center justify-between gap-2">
                            <span class="truncate font-medium text-[#111827]">Convenios del proyecto</span>
                            <span class="shrink-0 rounded bg-[#F4F1EA] px-1.5 py-0.5 text-[9px] font-medium text-[#6B7280]">priv</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="truncate font-medium text-[#111827]">Onboarding del cliente</span>
                            <span class="shrink-0 rounded bg-[#EFF6FF] px-1.5 py-0.5 text-[9px] font-medium text-[#2563EB]">púb</span>
                        </li>
                    </ul>
                    <div class="cf-bento-detail mt-3 border-t border-dashed border-[#E7E2D8] pt-2 text-[10px] text-[#9CA3AF]">
                        8 documentos · 3 públicos · editor + preview
                    </div>
                </div>
            </article>

            {{-- Card 3: Chat --}}
            <article class="cf-reveal cf-bento-card col-span-1 flex flex-col overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-5 lg:col-span-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[#EFF6FF] px-2.5 py-0.5 text-xs font-medium text-[#2563EB]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Chat
                    </span>
                    <span class="flex items-center gap-1.5 text-[10px] text-[#6B7280]">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#16A34A]"></span>
                        2 en línea
                    </span>
                </div>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Por proyecto, con visto.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">Mensajes, adjuntos, indicador de leído. Polling cada 5s.</p>

                <div class="mt-4 space-y-2">
                    <div class="flex justify-start">
                        <div class="max-w-[80%] rounded-2xl rounded-bl-sm border border-[#E7E2D8] bg-white px-3 py-2 text-xs text-[#111827]">
                            Te paso el mockup de la home para revisión.
                            <div class="mt-1 text-[10px] text-[#9CA3AF]">10:28</div>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <div class="max-w-[80%] rounded-2xl rounded-br-sm bg-[#2563EB] px-3 py-2 text-xs text-white">
                            <p>Perfecto, lo miro esta tarde.</p>
                            <div class="mt-1.5 flex items-center gap-1.5 rounded-md bg-[#1D4ED8] px-2 py-1 text-[10px]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                <span class="truncate">home-v2.pdf</span>
                                <span class="text-blue-100">2.1MB</span>
                            </div>
                            <div class="mt-1 flex items-center justify-end gap-1 text-[10px] text-blue-100">
                                <span>10:30</span>
                                <span>✓✓</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-start">
                        <div class="flex items-center gap-1.5 rounded-2xl rounded-bl-sm border border-[#E7E2D8] bg-white px-3 py-2 text-[10px] text-[#6B7280]">
                            <span>Marta está escribiendo</span>
                            <span class="flex gap-0.5">
                                <span class="h-1 w-1 animate-pulse rounded-full bg-[#6B7280]"></span>
                                <span class="h-1 w-1 animate-pulse rounded-full bg-[#6B7280]" style="animation-delay: 0.2s"></span>
                                <span class="h-1 w-1 animate-pulse rounded-full bg-[#6B7280]" style="animation-delay: 0.4s"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-3">
                    <div class="cf-bento-detail border-t border-dashed border-[#E7E2D8] pt-2 text-[10px] text-[#9CA3AF]">
                        4 conversaciones · adjuntos · polling 5s
                    </div>
                </div>
            </article>

            {{-- Card 4: Calendario --}}
            <article class="cf-reveal cf-bento-card col-span-1 flex flex-col overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-5 lg:col-span-3">
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[#F0FDF4] px-2.5 py-0.5 text-xs font-medium text-[#16A34A]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Calendario
                </span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Reuniones, deadlines y milestones.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">Vista mensual o semanal. Los deadlines de las tareas se pintan solos.</p>

                @php
                    $today = 14;
                    $events = [
                        4 => 'milestone',
                        9 => 'meeting',
                        12 => 'deadline',
                        14 => 'meeting',
                        22 => 'meeting',
                        27 => 'deadline',
                    ];
                @endphp
                <div class="mt-4 grid grid-cols-7 gap-1 text-center text-[10px]">
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
                        <div class="relative aspect-square rounded-md border {{ $isToday ? 'border-[#2563EB]' : 'border-transparent' }} {{ $isCurrentMonth ? 'bg-[#FAFAF7]' : 'bg-transparent' }} p-0.5 text-[10px] {{ $isCurrentMonth ? 'text-[#111827]' : 'text-[#9CA3AF]' }}">
                            @if ($isCurrentMonth) <span class="block text-left leading-none">{{ $cell }}</span> @endif
                            @if ($hasEvent)
                                <span class="absolute bottom-1 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full {{ $eventColor }}"></span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-auto pt-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">Próximos</p>
                    <ul class="mt-2 space-y-1.5 text-[11px]">
                        <li class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#D97706]"></span>
                            <span class="truncate font-medium text-[#111827]">Deadline "Checkout"</span>
                            <span class="ml-auto text-[10px] text-[#9CA3AF]">jue 12</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#2563EB]"></span>
                            <span class="truncate font-medium text-[#111827]">Review sprint 3</span>
                            <span class="ml-auto text-[10px] text-[#9CA3AF]">vie 14</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#16A34A]"></span>
                            <span class="truncate font-medium text-[#111827]">Demo cliente</span>
                            <span class="ml-auto text-[10px] text-[#9CA3AF]">jue 22</span>
                        </li>
                    </ul>
                    <div class="cf-bento-detail mt-3 border-t border-dashed border-[#E7E2D8] pt-2 text-[10px] text-[#9CA3AF]">
                        3 eventos próximos · 2 milestones · vista semanal
                    </div>
                </div>
            </article>

            {{-- Card 5: IA --}}
            <article class="cf-reveal cf-bento-card col-span-1 flex flex-col overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-5 lg:col-span-3">
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[#F5F3FF] px-2.5 py-0.5 text-xs font-medium text-[#8B5CF6]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/><path d="M19 14l.8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8z"/></svg>
                    Asistente IA
                </span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Tu cliente pregunta, la IA responde con contexto.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">El system prompt recibe el estado del proyecto, las tareas y los documentos públicos. OpenAI, Anthropic o el provider que prefieras.</p>

                <div class="mt-4 space-y-2">
                    <div class="flex justify-start">
                        <div class="max-w-[85%] rounded-2xl rounded-bl-sm border border-[#E7E2D8] bg-[#FAFAF7] px-3 py-2 text-xs text-[#111827]">
                            ¿Cómo va el módulo de citas?
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-br-sm bg-[#8B5CF6] px-3 py-2 text-xs text-white">
                            Está al 68%. La integración con Google Maps la cierran mañana y luego testing del flujo de confirmación. Tienes 2 tareas críticas pendientes de tu lado.
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <div class="flex items-center gap-1.5 rounded-2xl rounded-br-sm bg-[#F5F3FF] px-3 py-2 text-[10px] text-[#8B5CF6]">
                            <span>escribiendo</span>
                            <span class="flex gap-0.5">
                                <span class="h-1 w-1 animate-pulse rounded-full bg-[#8B5CF6]"></span>
                                <span class="h-1 w-1 animate-pulse rounded-full bg-[#8B5CF6]" style="animation-delay: 0.2s"></span>
                                <span class="h-1 w-1 animate-pulse rounded-full bg-[#8B5CF6]" style="animation-delay: 0.4s"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">Funciona con</p>
                    <div class="mt-2 flex flex-wrap gap-1.5 text-[10px]">
                        <span class="rounded-md border border-[#E7E2D8] bg-white px-2 py-0.5 font-medium text-[#111827]">OpenAI</span>
                        <span class="rounded-md border border-[#E7E2D8] bg-white px-2 py-0.5 font-medium text-[#111827]">Anthropic</span>
                        <span class="rounded-md border border-[#E7E2D8] bg-white px-2 py-0.5 font-medium text-[#6B7280]">Custom endpoint</span>
                    </div>
                    <div class="cf-bento-detail mt-3 border-t border-dashed border-[#E7E2D8] pt-2 text-[10px] text-[#9CA3AF]">
                        Contexto del proyecto · docs públicos · system prompt editable
                    </div>
                </div>
            </article>

            {{-- Card 6: MCP server con borde animado --}}
            <article class="cf-reveal cf-bento-card cf-glow-card group col-span-1 flex flex-col overflow-hidden rounded-2xl bg-white p-5 lg:col-span-3">
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[#111827] px-2.5 py-0.5 text-xs font-medium text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                    MCP server
                </span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Conecta tu IDE a tus proyectos.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">Tools de solo lectura para Cursor, Claude Code o cualquier cliente MCP. Tus documentos privados son consultables sin subirlos al repo.</p>

                <div class="mt-4 overflow-hidden rounded-lg bg-[#111827] p-3 font-mono text-[11px] leading-5 text-[#E7E2D8]">
                    <p><span class="text-[#9CA3AF]">// desde tu IDE</span></p>
                    <p class="mt-1"><span class="text-[#8B5CF6]">mcp</span>.<span class="text-[#2563EB]">get_project_status</span>(<span class="text-[#16A34A]">project_id</span>=42)</p>
                    <p class="mt-1.5 text-[#9CA3AF]">→ {</p>
                    <p class="pl-3"><span class="text-[#16A34A]">"progress"</span>: <span class="text-[#D97706]">67</span>,</p>
                    <p class="pl-3"><span class="text-[#16A34A]">"open_tasks"</span>: <span class="text-[#D97706]">6</span>,</p>
                    <p class="pl-3"><span class="text-[#16A34A]">"next_deadline"</span>: <span class="text-[#D97706]">"2026-06-28"</span></p>
                    <p>}</p>
                </div>

                <div class="mt-auto pt-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B7280]">Tools disponibles</p>
                    <ul class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-[11px] text-[#6B7280]">
                        <li class="font-mono"><span class="text-[#8B5CF6]">·</span> get_project_status</li>
                        <li class="font-mono"><span class="text-[#8B5CF6]">·</span> list_open_tasks</li>
                        <li class="font-mono"><span class="text-[#8B5CF6]">·</span> get_doc</li>
                        <li class="font-mono"><span class="text-[#8B5CF6]">·</span> search_messages</li>
                    </ul>
                    <div class="cf-bento-detail mt-3 border-t border-dashed border-[#E7E2D8] pt-2 text-[10px] text-[#9CA3AF]">
                        6 tools · solo lectura · auth por API token
                    </div>
                </div>
            </article>

            {{-- Card 7: PWA --}}
            <article class="cf-reveal cf-bento-card col-span-1 flex flex-col overflow-hidden rounded-2xl border border-[#E7E2D8] bg-white p-5 lg:col-span-3">
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[#F4F1EA] px-2.5 py-0.5 text-xs font-medium text-[#6B7280]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    PWA
                </span>
                <h3 class="mt-3 text-lg font-semibold text-[#111827]">Instalable, con push.</h3>
                <p class="mt-1.5 text-sm leading-6 text-[#6B7280]">Tu cliente puede añadirla a la pantalla de inicio. Notificaciones push para mensajes y deadlines.</p>

                <div class="mt-4 space-y-2">
                    <div class="flex items-center gap-3 rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-2.5">
                        <div class="grid h-8 w-8 place-items-center rounded-lg bg-[#111827] text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
                        </div>
                        <div class="min-w-0 flex-1 text-xs">
                            <p class="font-semibold text-[#111827]">Añadir ClientFlow a inicio</p>
                            <p class="text-[10px] text-[#6B7280]">Acceso directo, sin abrir el navegador</p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-[#E7E2D8] bg-white p-2.5">
                        <div class="flex items-start gap-2">
                            <div class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-[#EFF6FF] text-[#2563EB]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            </div>
                            <div class="min-w-0 flex-1 text-[11px]">
                                <div class="flex items-center justify-between">
                                    <p class="font-semibold text-[#111827]">ClientFlow</p>
                                    <span class="text-[9px] text-[#9CA3AF]">ahora</span>
                                </div>
                                <p class="text-[#6B7280]">Carlos: Te paso el mockup de la home</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-[10px] text-[#6B7280]">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#16A34A]"></span>
                        Offline-ready · service worker
                    </div>
                </div>

                <div class="mt-auto pt-3">
                    <div class="cf-bento-detail border-t border-dashed border-[#E7E2D8] pt-2 text-[10px] text-[#9CA3AF]">
                        iOS + Android · push · instalable
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>
