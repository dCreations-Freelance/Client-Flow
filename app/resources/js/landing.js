/**
 * Interactividad de la landing de ClientFlow.
 *
 * - Reveal por IntersectionObserver (fade + translate-y).
 * - Reveal palabra a palabra para titulos grandes.
 * - Contador animado con requestAnimationFrame + flash al terminar.
 * - Typing effect en el bloque MCP con cascada de lineas JSON.
 * - Magnetic hover en CTAs.
 * - Header solido al hacer scroll + nav activo por seccion (scroll spy).
 * - Barra de progreso de scroll con dot al final.
 * - Spotlight del cursor limitado al hero.
 * - Menu mobile con backdrop, slide-down, stagger y morph de icono.
 * - Section markers: la linea y el numero aparecen al entrar.
 *
 * Sin dependencias externas. Respeta prefers-reduced-motion.
 */

(function () {
    'use strict';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /**
     * Marca un elemento como visible cuando entra en el viewport.
     * Se desregistra tras la primera aparicion para no repetir.
     *
     * @param {Element} el
     * @returns {void}
     */
    function bindReveal(el) {
        if (prefersReducedMotion) {
            el.classList.add('is-in');
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        observer.unobserve(entry.target);
                    }
                }
            },
            { threshold: 0.15, rootMargin: '0px 0px -40px 0px' },
        );

        observer.observe(el);
    }

    /**
     * Recorre el DOM y activa el reveal en todo lo que lo tenga
     * pendiente. Se ejecuta en DOMContentLoaded y tambien despues
     * de cualquier insercion dinamica (no usada aun, pero la
     * dejamos preparada).
     *
     * @returns {void}
     */
    function initReveals() {
        document.querySelectorAll('.cf-reveal:not(.is-in), .cf-reveal-left:not(.is-in), .cf-reveal-right:not(.is-in)').forEach(bindReveal);
    }

    /**
     * Activa el `is-in` en los section-markers cuando su padre
     * entra en viewport. La animacion de la linea y del numero
     * la gestiona el CSS.
     *
     * @returns {void}
     */
    function initSectionMarkers() {
        const markers = document.querySelectorAll('.cf-section-marker:not(.is-in)');
        if (markers.length === 0) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        observer.unobserve(entry.target);
                    }
                }
            },
            { threshold: 0.3, rootMargin: '0px 0px -60px 0px' },
        );

        markers.forEach((el) => observer.observe(el));
    }

    /**
     * Para titulos con `data-cf-word-reveal`, parte el texto en
     * palabras, envuelve cada una en un `<span class="cf-word">`
     * y dispara el reveal escalonado cuando el titulo entra en
     * viewport. Solo se aplica una vez.
     *
     * @returns {void}
     */
    function initWordReveals() {
        const elements = document.querySelectorAll('[data-cf-word-reveal]');
        if (elements.length === 0) {
            return;
        }

        if (prefersReducedMotion) {
            elements.forEach((el) => {
                el.classList.add('is-in');
                el.querySelectorAll('.cf-word').forEach((w) => w.classList.add('is-in'));
            });
            return;
        }

        elements.forEach((el) => {
            // Envoltura por palabras preservando saltos de linea.
            const text = el.textContent.trim();
            const words = text.split(/(\s+)/);
            el.textContent = '';
            words.forEach((word) => {
                if (/\s+/.test(word)) {
                    el.appendChild(document.createTextNode(word));
                    return;
                }
                const span = document.createElement('span');
                span.className = 'cf-word';
                span.textContent = word;
                el.appendChild(span);
            });

            const observer = new IntersectionObserver(
                (entries) => {
                    for (const entry of entries) {
                        if (entry.isIntersecting) {
                            const wordSpans = entry.target.querySelectorAll('.cf-word');
                            wordSpans.forEach((w, i) => {
                                setTimeout(() => w.classList.add('is-in'), i * 45);
                            });
                            entry.target.classList.add('is-in');
                            observer.unobserve(entry.target);
                        }
                    }
                },
                { threshold: 0.4 },
            );

            observer.observe(el);
        });
    }

    /**
     * Anima un contador desde 0 hasta su valor final (atributo
     * `data-cf-target`) cuando entra en el viewport. Anade la
     * clase `is-done` al terminar para disparar el flash CSS.
     *
     * @param {HTMLElement} el
     * @returns {void}
     */
    function animateCounter(el) {
        const target = parseFloat(el.getAttribute('data-cf-target') || '0');
        const duration = parseInt(el.getAttribute('data-cf-duration') || '1200', 10);
        const decimals = parseInt(el.getAttribute('data-cf-decimals') || '0', 10);
        const prefix = el.getAttribute('data-cf-prefix') || '';
        const suffix = el.getAttribute('data-cf-suffix') || '';

        if (prefersReducedMotion) {
            el.textContent = prefix + target.toFixed(decimals) + suffix;
            el.classList.add('is-done');
            return;
        }

        const start = performance.now();
        const easeOutExpo = (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t));

        function tick(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const value = target * easeOutExpo(progress);
            el.textContent = prefix + value.toFixed(decimals) + suffix;
            if (progress < 1) {
                requestAnimationFrame(tick);
            } else {
                el.textContent = prefix + target.toFixed(decimals) + suffix;
                el.classList.add('is-done');
            }
        }

        requestAnimationFrame(tick);
    }

    /**
     * Liga cada `.cf-counter` a su animacion. La primera vez que
     * entra en viewport se dispara y no se repite.
     *
     * @returns {void}
     */
    function initCounters() {
        const counters = document.querySelectorAll('.cf-counter');
        if (counters.length === 0) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                }
            },
            { threshold: 0.4 },
        );

        counters.forEach((el) => observer.observe(el));
    }

    /**
     * Simula un efecto de "escritura" caracter a caracter. La
     * velocidad se puede ajustar con `data-cf-speed` (ms/char).
     *
     * @param {HTMLElement} el
     * @returns {void}
     */
    function typeWrite(el) {
        if (el.dataset.cfTyped === '1') {
            return;
        }
        el.dataset.cfTyped = '1';

        const text = el.getAttribute('data-cf-text') || el.textContent || '';
        const speed = parseInt(el.getAttribute('data-cf-speed') || '18', 10);

        el.textContent = '';
        el.classList.add('cf-typing');

        if (prefersReducedMotion) {
            el.textContent = text;
            el.classList.remove('cf-typing');
            el.classList.add('cf-code-done');
            el.dispatchEvent(new CustomEvent('cf:typed-done', { bubbles: true }));
            return;
        }

        let i = 0;
        const interval = setInterval(() => {
            el.textContent = text.slice(0, i + 1);
            i += 1;
            if (i >= text.length) {
                clearInterval(interval);
                el.classList.remove('cf-typing');
                el.classList.add('cf-code-done');
                el.dispatchEvent(new CustomEvent('cf:typed-done', { bubbles: true }));
            }
        }, speed);
    }

    /**
     * Dispara el typing solo cuando la seccion entra en viewport.
     *
     * @returns {void}
     */
    function initTyping() {
        const elements = document.querySelectorAll('[data-cf-typing]');
        if (elements.length === 0) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        typeWrite(entry.target);
                        observer.unobserve(entry.target);
                    }
                }
            },
            { threshold: 0.4 },
        );

        elements.forEach((el) => observer.observe(el));
    }

    /**
     * Magnetic hover: el boton se desplaza unos pixels hacia el
     * cursor para una sensacion mas "tactil". Limitado a 6-8px
     * para que no parezca un truco barato.
     *
     * @returns {void}
     */
    function initMagnetic() {
        if (prefersReducedMotion) {
            return;
        }

        const elements = document.querySelectorAll('.cf-magnetic');

        elements.forEach((el) => {
            el.addEventListener('mousemove', (event) => {
                const rect = el.getBoundingClientRect();
                const x = event.clientX - rect.left - rect.width / 2;
                const y = event.clientY - rect.top - rect.height / 2;
                el.style.transform = `translate3d(${x * 0.12}px, ${y * 0.18}px, 0)`;
            });

            el.addEventListener('mouseleave', () => {
                el.style.transform = '';
            });
        });
    }

    /**
     * Cambia la clase del header segun el scroll, actualiza la
     * barra de progreso y gestiona el nav activo (scroll spy).
     *
     * @returns {void}
     */
    function initScrollDecorations() {
        const header = document.querySelector('.cf-header');
        const progress = document.getElementById('cf-scroll-progress');
        const navLinks = Array.from(document.querySelectorAll('[data-cf-spy]'));
        const sectionIds = navLinks
            .map((link) => link.getAttribute('data-cf-spy'))
            .filter(Boolean);
        const sections = sectionIds
            .map((id) => document.getElementById(id))
            .filter(Boolean);

        let ticking = false;

        function update() {
            const y = window.scrollY;
            const max = document.documentElement.scrollHeight - window.innerHeight;
            const percent = max > 0 ? (y / max) * 100 : 0;

            if (header) {
                header.classList.toggle('is-scrolled', y > 24);
            }

            if (progress) {
                progress.style.width = percent + '%';
                progress.classList.toggle('is-active', percent > 1);
            }

            // Scroll spy: determina que seccion es la "activa"
            // segun cual tiene su centro mas cerca del centro
            // del viewport.
            if (sections.length > 0) {
                const viewportCenter = y + window.innerHeight * 0.35;
                let activeId = null;
                for (const section of sections) {
                    if (section.offsetTop <= viewportCenter) {
                        activeId = section.id;
                    }
                }
                navLinks.forEach((link) => {
                    const id = link.getAttribute('data-cf-spy');
                    link.classList.toggle('is-active', id === activeId);
                });
            }

            ticking = false;
        }

        window.addEventListener(
            'scroll',
            () => {
                if (!ticking) {
                    window.requestAnimationFrame(update);
                    ticking = true;
                }
            },
            { passive: true },
        );

        update();
    }

    /**
     * Spotlight del cursor limitado al hero. Solo se activa
     * cuando el puntero esta dentro de la seccion.
     *
     * @returns {void}
     */
    function initHeroSpotlight() {
        if (prefersReducedMotion) {
            return;
        }

        const spotlight = document.getElementById('cf-hero-spotlight');
        const hero = document.querySelector('[data-cf-hero]');
        if (!spotlight || !hero) {
            return;
        }

        let active = false;

        hero.addEventListener('mousemove', (event) => {
            if (!active) {
                spotlight.classList.add('is-active');
                active = true;
            }
            spotlight.style.transform = `translate3d(${event.clientX}px, ${event.clientY}px, 0) translate(-50%, -50%)`;
        });

        hero.addEventListener('mouseleave', () => {
            spotlight.classList.remove('is-active');
            active = false;
        });
    }

    /**
     * Anima la aparicion de las lineas de codigo del bloque MCP
     * una vez que el snippet principal ha terminado de "escribirse".
     * Esto encadena la experiencia sin acoplar a tiempos fijos.
     *
     * @returns {void}
     */
    function initCodeChain() {
        const trigger = document.querySelector('[data-cf-typing]');
        if (!trigger) {
            return;
        }

        trigger.addEventListener('cf:typed-done', () => {
            const lines = document.querySelectorAll('.cf-code-line');
            lines.forEach((line, index) => {
                setTimeout(() => line.classList.add('is-in'), index * 70);
            });
        });
    }

    /**
     * Menu mobile: backdrop, panel deslizante, stagger de los
     * links y morph del icono hamburguesa a X. Tambien cierra
     * el panel al pulsar cualquier link, con Escape o al
     * pulsar el backdrop.
     *
     * @returns {void}
     */
    function initMobileMenu() {
        const toggle = document.querySelector('[data-cf-menu-toggle]');
        const menu = document.querySelector('[data-cf-menu]');
        const backdrop = document.querySelector('[data-cf-menu-backdrop]');
        const links = document.querySelectorAll('[data-cf-menu-link]');
        if (!toggle || !menu) {
            return;
        }

        function setOpen(open) {
            toggle.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            menu.classList.toggle('is-open', open);
            if (backdrop) {
                backdrop.classList.toggle('is-open', open);
            }
            document.body.style.overflow = open ? 'hidden' : '';

            if (open && !prefersReducedMotion) {
                menu.querySelectorAll('.cf-menu-link').forEach((link, i) => {
                    // Forzamos reflow para que la transicion
                    // desde opacity:0 arranque limpia.
                    void link.offsetWidth;
                    setTimeout(() => link.classList.add('is-in'), 80 + i * 50);
                });
            } else {
                menu.querySelectorAll('.cf-menu-link').forEach((link) => {
                    link.classList.remove('is-in');
                });
            }
        }

        toggle.addEventListener('click', () => {
            const isOpen = toggle.classList.contains('is-open');
            setOpen(!isOpen);
        });

        links.forEach((link) => {
            link.addEventListener('click', () => setOpen(false));
        });

        if (backdrop) {
            backdrop.addEventListener('click', () => setOpen(false));
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && toggle.classList.contains('is-open')) {
                setOpen(false);
            }
        });
    }

    /**
     * Punto de entrada.
     *
     * @returns {void}
     */
    function init() {
        initReveals();
        initSectionMarkers();
        initWordReveals();
        initCounters();
        initTyping();
        initMagnetic();
        initScrollDecorations();
        initHeroSpotlight();
        initCodeChain();
        initMobileMenu();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
