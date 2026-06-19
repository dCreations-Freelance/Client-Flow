{{--
    CTA final (seccion 10).

    Cierra la pagina con un mensaje simple y dos botones:
    crear cuenta + ver en GitHub. Mantiene la paleta warm
    con el acento `#8B5CF6` y un fondo ligeramente tintado
    para distinguirse visualmente de las secciones anteriores.
--}}
<section class="bg-[#FAFAF7] py-24 sm:py-32" aria-labelledby="cf-cta-title">
    <div class="mx-auto max-w-5xl px-6 lg:px-10">
        <div class="cf-reveal relative overflow-hidden rounded-[32px] border border-[#E7E2D8] bg-white px-8 py-16 text-center shadow-[0_20px_60px_rgba(17,24,39,0.06)] sm:px-16 sm:py-20">
            {{-- Decoracion: blob suave detras --}}
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -left-12 -top-12 h-48 w-48 rounded-full bg-[#8B5CF6] opacity-[0.06] blur-3xl"></div>
                <div class="absolute -bottom-12 -right-12 h-48 w-48 rounded-full bg-[#2563EB] opacity-[0.06] blur-3xl"></div>
            </div>

            <span class="cf-section-marker justify-center"><span>10</span><span>Empieza hoy</span></span>

            <h2
                id="cf-cta-title"
                class="mt-6 text-3xl font-semibold leading-[1.1] tracking-[-0.02em] text-[#111827] sm:text-4xl lg:text-5xl"
            >
                Tu proximo proyecto merece un portal. <span class="text-[#8B5CF6]">No otra captura de Trello.</span>
            </h2>

            <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-[#6B7280] sm:text-lg">
                Crea una cuenta gratis, instala el docker-compose y tendras a tu cliente conectado en menos de 5 minutos. Sin tarjeta, sin permanencia, sin telemarketing.
            </p>

            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a
                    href="{{ route('register') }}"
                    class="cf-magnetic inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#111827] px-6 py-3.5 text-sm font-semibold text-white transition-colors hover:bg-black sm:w-auto"
                >
                    Crear cuenta gratis
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 5l7 7-7 7" />
                    </svg>
                </a>
                <a
                    href="https://github.com/anomalyco/opencode"
                    rel="noopener"
                    class="cf-magnetic inline-flex w-full items-center justify-center gap-2 rounded-xl border border-[#E7E2D8] bg-white px-6 py-3.5 text-sm font-semibold text-[#111827] transition-colors hover:border-[#D8D0C3] hover:bg-[#F4F1EA] sm:w-auto"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                        <path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.92.58.1.79-.25.79-.56v-2c-3.2.7-3.87-1.54-3.87-1.54-.52-1.32-1.27-1.67-1.27-1.67-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.76 2.69 1.25 3.34.96.1-.74.4-1.25.73-1.54-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.28 1.18-3.08-.12-.29-.51-1.46.11-3.05 0 0 .96-.31 3.15 1.18.91-.25 1.89-.38 2.86-.39.97.01 1.95.14 2.86.39 2.18-1.49 3.15-1.18 3.15-1.18.62 1.59.23 2.76.11 3.05.74.8 1.18 1.82 1.18 3.08 0 4.42-2.7 5.39-5.27 5.68.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56C20.21 21.38 23.5 17.08 23.5 12 23.5 5.65 18.35.5 12 .5z"/>
                    </svg>
                    Ver el codigo
                </a>
            </div>

            <p class="mt-6 text-xs text-[#6B7280]">
                MIT licensed · v0.9 · PHP 8.3+ · MySQL 8.4 · self-hostable
            </p>
        </div>
    </div>
</section>
