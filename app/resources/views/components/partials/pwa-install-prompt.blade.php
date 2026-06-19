{{--
    Banner inferior para sugerir la instalacion de la PWA.

    Aparece solo cuando el navegador emite el evento
    `beforeinstallprompt` (Chrome, Edge, Android WebView). El
    disparador se hace en `resources/js/pwa.js`, que escucha
    el evento y emite `pwa-install-available` para que este
    banner se muestre.

    Comportamiento:
    - Oculto por default (`hidden` attribute). Se muestra
      con JS al recibir el evento.
    - "Instalar" llama a `window.installPwa()` (definido en
      pwa.js), que dispara el prompt nativo del navegador.
    - "Ahora no" llama a `window.dismissPwaInstall()`, que
      persiste el rechazo en `localStorage` para no volver
      a molestar al usuario.
    - Tras instalar, pwa.js emite `pwa-install-completed` y
      el banner se oculta.

    Sin frameworks externos: vanilla JS + atributos
    `data-*` + `x-cloak` para evitar parpadeo.
--}}
<div
    id="pwa-install-prompt"
    class="fixed bottom-4 left-1/2 z-50 hidden w-full max-w-md -translate-x-1/2 px-4"
    role="region"
    aria-label="Instalar aplicacion"
>
    <div class="flex items-center gap-3 rounded-xl border border-[#E7E2D8] bg-white p-4 shadow-[0_10px_30px_rgba(17,24,39,0.1)]">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#111827] text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
        </div>

        <div class="flex-1">
            <p class="text-sm font-semibold text-[#111827]">Instala ClientFlow</p>
            <p class="text-xs text-[#6B7280]">Accede mas rapido desde tu escritorio, sin abrir el navegador.</p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <button
                type="button"
                onclick="window.dismissPwaInstall && window.dismissPwaInstall()"
                class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
            >
                Ahora no
            </button>
            <button
                type="button"
                onclick="window.installPwa && window.installPwa()"
                class="rounded-lg bg-[#111827] px-3 py-1.5 text-xs font-medium text-white hover:bg-black"
            >
                Instalar
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const banner = document.getElementById('pwa-install-prompt');
        if (!banner) {
            return;
        }

        function show() {
            banner.classList.remove('hidden');
        }

        function hide() {
            banner.classList.add('hidden');
        }

        window.addEventListener('pwa-install-available', show);
        window.addEventListener('pwa-install-completed', hide);
        window.addEventListener('pwa-install-dismissed', hide);
    })();
</script>
