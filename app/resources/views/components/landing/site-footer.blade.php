{{--
    Pie de pagina. Minimal, no compite con el CTA final que ya
    esta justo encima. Se divide en tres columnas: marca,
    producto y legal.
--}}
<footer class="border-t border-[#E7E2D8] bg-[#FAFAF7]">
    <div class="mx-auto grid max-w-7xl gap-10 px-6 py-14 lg:grid-cols-[1.4fr_1fr_1fr_1fr] lg:px-10">
        <div>
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-sm font-semibold tracking-tight">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#111827] text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 6h6v6H4zM14 6h6v6h-6zM4 16h6v4H4zM14 16h6v4h-6z" />
                    </svg>
                </span>
                <span>ClientFlow</span>
            </a>
            <p class="mt-4 max-w-xs text-sm leading-6 text-[#6B7280]">El portal privado donde tus clientes entienden su proyecto en menos de 10 segundos. Open source y self-hostable.</p>
        </div>

        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-[#111827]">Producto</h3>
            <ul class="mt-4 space-y-2.5 text-sm text-[#6B7280]">
                <li><a href="#features" class="transition-colors hover:text-[#111827]">Funciones</a></li>
                <li><a href="#mcp" class="transition-colors hover:text-[#111827]">MCP server</a></li>
                <li><a href="#stack" class="transition-colors hover:text-[#111827]">Stack</a></li>
                <li><a href="{{ route('login') }}" class="transition-colors hover:text-[#111827]">Acceder al portal</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-[#111827]">Recursos</h3>
            <ul class="mt-4 space-y-2.5 text-sm text-[#6B7280]">
                <li><a href="https://github.com/anomalyco/opencode" rel="noopener" class="transition-colors hover:text-[#111827]">Codigo en GitHub</a></li>
                <li><a href="#faq" class="transition-colors hover:text-[#111827]">Preguntas frecuentes</a></li>
                <li><a href="{{ route('register') }}" class="transition-colors hover:text-[#111827]">Crear cuenta</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-[#111827]">Licencia</h3>
            <p class="mt-4 text-sm leading-6 text-[#6B7280]">MIT. Instalalo en tu servidor, modificalo, hostealo donde quieras. Sin dependencias obligatorias fuera de PHP, MySQL y Node.</p>
        </div>
    </div>

    <div class="border-t border-[#E7E2D8]">
        <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-3 px-6 py-6 text-xs text-[#6B7280] sm:flex-row sm:items-center lg:px-10">
            <p>© {{ date('Y') }} ClientFlow. Hecho con calma y con propósito.</p>
            <p class="flex items-center gap-2">
                <span class="inline-flex h-2 w-2 rounded-full bg-[#16A34A]"></span>
                Construido sobre Laravel 13, Livewire 4 y Tailwind 4
            </p>
        </div>
    </div>
</footer>
