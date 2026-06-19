# Landing page — presentacion editorial de ClientFlow

Documento tecnico de la landing publica de ClientFlow, la
pagina que ven los usuarios no autenticados al entrar en `/`.
Sustituye a la `welcome.blade.php` original por una version
editorial, segmentada en 10 secciones, con microanimaciones
refinadas y consistente 100% con la paleta de `docs/DESIGN.md`.

## Alcance

- Reemplazar `welcome.blade.php` por una vista estatica nueva
  `landing.blade.php` servida en `/` para guests.
- Compilar CSS y JS especificos de la landing por separado
  para no contaminar al resto de la app.
- Mantener la paleta warm de `docs/DESIGN.md` sin colores
  extra. El unico color "editorial" es `#8B5CF6` (Info),
  que el propio design system define como color para
  "elementos especiales".
- Sin dependencias npm nuevas. Sin Alpine.js. Sin Livewire.
  Sin Tailwind plugins adicionales. Todo CSS + JS nativo.
- Respetar `prefers-reduced-motion: reduce` desactivando
  reveal, contadores, typing, marquee y borde animado.
- Accesible: navegacion con teclado, focus visible, FAQ con
  `<details>/<summary>` (nativo), textos alternativos.

## Concepto

La pagina se plantea como un "reportaje editorial" en vez de
la tipica landing SaaS. Diferencias concretas respecto al 99%
de landings:

- **Numeracion visible (01-10)**: cada seccion lleva un
  marcador tipo magazine que refuerza el tono de "articulo".
- **Sin fotos stock ni ilustraciones**: todos los mockups
  (kanban, chat, docs, calendario, IA, MCP) son HTML + Tailwind
  con datos ficticios pero realistas. Cero dependencias de
  imagenes externas.
- **Sin tabla de precios**: el pricing de ClientFlow es MIT
  + hosting, asi que la pagina solo tiene un CTA "Crear
  cuenta" y un link a GitHub.
- **Sin grid 3x2 de testimonios**: una sola cita grande en
  vez del clasico carrusel de fotos con 5 estrellas.
- **Sin carrusel de logos de clientes**: al ser un proyecto
  open source sin clientes que publicitar, se sustituye por
  una marquesina con badges de estado que muestra la
  "personalidad" del producto.
- **Sin video en el hero**: el hero usa un mockup vivo
  (kanban con tarea animada) y un contador real que cuenta
  de 0 a 10 al cargar.

## Estructura

```
resources/
├── views/
│   ├── landing.blade.php                      # Vista principal
│   └── components/landing/
│       ├── header.blade.php                   # Cabecera sticky
│       ├── hero.blade.php                     # 01
│       ├── manifesto.blade.php                # 02
│       ├── bento-features.blade.php           # 03
│       ├── dual-view.blade.php                # 04
│       ├── mcp-section.blade.php              # 05
│       ├── stack.blade.php                    # 06
│       ├── numbers.blade.php                  # 07
│       ├── quote.blade.php                    # 08
│       ├── faq.blade.php                      # 09
│       ├── cta-final.blade.php                # 10
│       ├── site-footer.blade.php              # Pie de pagina
│       └── section-marker.blade.php           # Numero + eyebrow
├── css/
│   └── landing.css                            # Animaciones, tokens
└── js/
    └── landing.js                             # Reveal, counters, typing
```

## Secciones

### 01 — Hero editorial (`components/landing/hero.blade.php`)

- Tipografia display (`text-7xl`/`text-8xl` responsive).
- Contador animado `0 -> 10` con `requestAnimationFrame` y
  `easeOutExpo`. Cursor parpadeante al lado del numero
  (`@keyframes cf-cursor-blink`).
- Mockup del proyecto "Web corporativa clinica dental" con
  mini-kanban de 3 columnas. Una tarea tiene la clase
  `cf-kanban-card-live` con `animation: cf-kanban-pop 3.5s`
  infinita para sugerir movimiento.
- Barra de progreso 68%.
- Ultimo avance del equipo en una tarjeta `#F4F1EA`.
- CTAs primario (`#111827` con magnetic hover) y secundario
  (boton "Ver en GitHub" con icono).
- Chip flotante "El cliente ha visto este avance" con borde
  y sombra elevada.
- Spotlight del cursor: capa `radial-gradient` morada que
  sigue al puntero, limitada a la seccion del hero.

### 02 — Manifiesto (`components/landing/manifesto.blade.php`)

Lista de 4 frases que enumeran lo que ClientFlow elimina
("Sin emails", "Sin WhatsApp", "Sin documentos en repos",
"Sin recrear agentes IA"). Cada item aparece escalonado al
hacer scroll (`cf-stagger-in > .cf-reveal` con `transition-delay`
fijo por hijo).

### 03 — Bento de features (`components/landing/bento-features.blade.php`)

Grid asimetrico de 7 cards. Cada card tiene un mini-mockup
representativo:

- **Kanban vitaminado** (4 columnas del bento, la mas
  grande): 3 columnas con tareas reales, prioridad
  `Critical/High`, tipo `Feature`, estimacion en horas.
- **Documentos markdown** (2 columnas): editor con tabs
  "Editor / Preview" y codigo ficticio de "Convenios del
  proyecto".
- **Chat** (2 columnas): burbuja del cliente, burbuja
  propia con doble check, mensaje de sistema.
- **Calendario** (3 columnas): grid 7x5 de dias con dots
  de colores segun tipo (`meeting`, `deadline`,
  `milestone`). El dia "hoy" lleva borde azul.
- **Asistente IA** (3 columnas): chat con pregunta del
  cliente y respuesta larga del asistente con contexto del
  proyecto.
- **MCP server** (3 columnas): con `cf-glow-card` que
  dibuja un borde conico-gradient animado (5 colores del
  design system).
- **PWA** (3 columnas): mockup de "Anadir a pantalla de
  inicio" con icono y microcopy.

Cada card tiene `cf-bento-card` que aplica hover lift
(`translate-y(-4px)` + sombra) y `cf-bento-detail` que
revela una capa extra al hacer hover (preparado para futuro
contenido, hoy vacia).

### 04 — Dual view (`components/landing/dual-view.blade.php`)

Split-screen que muestra admin (con metricas, documentos
privados, tiempo registrado) vs cliente (proyecto, chat,
asistente IA). La linea central vertical con gradiente
de `#8B5CF6` aparece solo en `lg:`. Cada lado entra desde
direcciones opuestas (`cf-reveal-left` / `cf-reveal-right`).

### 05 — MCP server (`components/landing/mcp-section.blade.php`)

Snippet realista con typing effect:

```
mcp.get_project_status(project_id=42) → progreso, tareas abiertas, proximo deadline
```

Tras la escritura (evento `cf:typed-done`) se animan las
lineas de la respuesta JSON una a una con delay de 70ms.
Esto encadena la experiencia sin tiempos fijos hardcoded.

### 06 — Stack abierto (`components/landing/stack.blade.php`)

Lista de 6 tecnologias con version + rol (PHP 8.3+,
Laravel 13, Livewire 4, Tailwind 4, MySQL 8.4, Vite 8).
Debajo, 3 cards que refuerzan el pitch de "hosting
compartido" (sin Redis obligatorio, sin workers
permanentes, hosting compartido OK).

### 07 — En numeros (`components/landing/numbers.blade.php`)

4 contadores animados con `data-cf-target` /
`data-cf-duration` / `data-cf-suffix`. Cada uno con un
color de design system distinto: 10s (`#8B5CF6`),
5s polling (`#2563EB`), 7 tools MCP (`#16A34A`),
13 fases del MVP (`#D97706`).

### 08 — Cita editorial (`components/landing/quote.blade.php`)

Una sola cita en vez del clasico grid de testimonios. Texto
display (`text-2xl`/`text-3xl`/`text-4xl`) con comillas en
`#8B5CF6`. Firma con avatar de iniciales y nombre + proyecto.

### 09 — FAQ (`components/landing/faq.blade.php`)

6 preguntas con `<details>/<summary>` (nativo, accesible
por teclado y screen readers sin JS). El chevron rota
180 grados al abrir via `[open] summary .cf-faq-chevron`.

### 10 — CTA final (`components/landing/cta-final.blade.php`)

Card grande con blobs decorativos, claim en display, dos
CTAs (Crear cuenta + Ver el codigo) y microcopy con
versionado ("MIT licensed · v0.9 · PHP 8.3+ · MySQL 8.4 ·
self-hostable").

### Header (`components/landing/header.blade.php`)

- Sticky top, transparente sobre el hero, solido con
  `backdrop-blur` al hacer scroll (clase `.is-scrolled`).
- Logo: cuadrado `#111827` con icono "4 paneles" + texto
  "ClientFlow".
- 5 links de anclaje a secciones (ocultos en mobile).
- Boton "Acceder" y CTA "Empezar gratis" con magnetic
  hover.
- Menu mobile con toggle (boton hamburguesa). JS minimo:
  `data-cf-menu-toggle` + `data-cf-menu`. Se cierra con
  click en un link o tecla Escape.

### Site footer (`components/landing/site-footer.blade.php`)

4 columnas: marca, producto, recursos, licencia. Footer
inferior con copyright y microcopy "Construido sobre
Laravel 13, Livewire 4 y Tailwind 4" con dot verde de
status.

## Cambios por capa

### Vistas

| Archivo | Proposito |
|---|---|
| `resources/views/landing.blade.php` | Vista principal servida en `/` para guests. |
| `resources/views/components/landing/header.blade.php` | Cabecera sticky con menu mobile. |
| `resources/views/components/landing/hero.blade.php` | 01 — Hero editorial. |
| `resources/views/components/landing/manifesto.blade.php` | 02 — Manifiesto. |
| `resources/views/components/landing/bento-features.blade.php` | 03 — Bento de features. |
| `resources/views/components/landing/dual-view.blade.php` | 04 — Admin vs cliente. |
| `resources/views/components/landing/mcp-section.blade.php` | 05 — MCP server con typing. |
| `resources/views/components/landing/stack.blade.php` | 06 — Stack abierto. |
| `resources/views/components/landing/numbers.blade.php` | 07 — Contadores animados. |
| `resources/views/components/landing/quote.blade.php` | 08 — Cita editorial. |
| `resources/views/components/landing/faq.blade.php` | 09 — FAQ. |
| `resources/views/components/landing/cta-final.blade.php` | 10 — CTA final. |
| `resources/views/components/landing/section-marker.blade.php` | Numero + eyebrow (reutilizable). |
| `resources/views/components/landing/site-footer.blade.php` | Pie de pagina. |

### CSS (`resources/css/landing.css`)

- Tokens locales en `:root` (`--cf-accent`, `--cf-ink`,
  `--cf-line`, `--cf-surface`, etc.) para no repetir
  literales hex.
- `prefers-reduced-motion: reduce` desactiva todas las
  animaciones y transiciones.
- Utilidades:
  - `.cf-reveal` — entrada fade + translate-y via
    IntersectionObserver.
  - `.cf-stagger-in > .cf-reveal` — entradas escalonadas
    por hijo.
  - `.cf-reveal-left` / `.cf-reveal-right` — entradas desde
    los lados para el dual view.
  - `.cf-bento-card` — hover lift + sombra.
  - `.cf-glow-card` — borde conico-gradient animado.
  - `.cf-magnetic` — base para CTAs con magnetic hover.
  - `.cf-hero-spotlight` — capa radial que sigue al cursor.
  - `.cf-marquee` — marquesina horizontal infinita.
  - `.cf-scroll-progress` — barra superior de progreso.
  - `.cf-section-marker` — formato consistente del
    "01 / Eyebrow" de cada seccion.
- Textura de fondo: SVG `feTurbulence` inline en `body::before`
  con opacidad `0.035` para look editorial.

### JS (`resources/js/landing.js`)

- Sin dependencias externas. Encapsulado en una IIFE.
- Modulos:
  - `initReveals()` — IntersectionObserver para `cf-reveal*`.
  - `initCounters()` — count-up con `requestAnimationFrame` y
    `easeOutExpo`.
  - `initTyping()` — typing effect para bloques `data-cf-typing`.
  - `initMagnetic()` — hover magnetico en `.cf-magnetic`.
  - `initScrollDecorations()` — header `.is-scrolled` y
    barra de progreso.
  - `initHeroSpotlight()` — spotlight del cursor en el hero.
  - `initCodeChain()` — encadena la aparicion de las
    lineas de codigo del bloque MCP tras el evento
    `cf:typed-done`.
  - `initMobileMenu()` — toggle del menu mobile.
- Respeta `prefers-reduced-motion`: si esta activo, todo se
  muestra en estado final sin animacion.

### Rutas

- `routes/web.php`: el `Route::get('/', ...)` para guests
  pasa de `view('welcome')` a `view('landing')`.

### Build

- `vite.config.js`: se anyaden `resources/css/landing.css` y
  `resources/js/landing.js` como inputs de Vite para que se
  compilen en chunks separados.

## Paleta usada (verificada)

Todos los colores provienen literalmente de `docs/DESIGN.md`:

| Token | Hex | Uso principal en la landing |
|---|---|---|
| Background | `#FAFAF7` | Fondo general |
| Surface | `#FFFFFF` | Cards, mockups |
| Surface Hover | `#F4F1EA` | Hover en rows, surface secondary |
| Border | `#E7E2D8` | Bordes por defecto |
| Border Strong | `#D8D0C3` | Hover en bordes |
| Text Primary | `#111827` | Titulos, CTAs principales |
| Text Secondary | `#6B7280` | Descripciones, labels |
| Text Muted | `#9CA3AF` | Placeholders, microcopy |
| Primary | `#2563EB` | Links, CTAs, badges primary |
| Success | `#16A34A` | Estados completados, dots |
| Success Light | `#F0FDF4` | Fondos de badges success |
| Warning | `#D97706` | Estados pendientes, badges warning |
| Warning Light | `#FFFBEB` | Fondos de badges warning |
| Danger | `#DC2626` | Errores, badges danger |
| Danger Light | `#FEF2F2` | Fondos de badges danger |
| Info | `#8B5CF6` | Acento editorial, numeros, IA, claims |
| Info Light | `#F5F3FF` | Fondos de badges info |

**Sin colores fuera de DESIGN.md.** El antiguo `#B88746` de
la `welcome.blade.php` original se ha eliminado por completo
de la nueva landing. La unica referencia que queda en el
proyecto esta en `app/app/Console/Commands/GeneratePwaIconsCommand.php`
(genera un acento decorativo en el icono de la PWA) y se
documenta en `docs/tasks/fase-9-pwa.md`. Queda como
decision separada del diseno de la landing.

## Microanimaciones (resumen)

| Animacion | Como se activa | Donde se aplica |
|---|---|---|
| Reveal entrada | `IntersectionObserver` + clase `is-in` | Todos los titulos, cards, listas |
| Stagger hijos | CSS `transition-delay` por `:nth-child` | Bento, manifesto, numbers, stack |
| Count-up | `requestAnimationFrame` con `easeOutExpo` | Hero (`10s`) y seccion 07 |
| Typing effect | `setInterval` por caracter, evento `cf:typed-done` | Bloque MCP |
| Code line cascade | Tras `cf:typed-done`, delays 70ms | Respuesta JSON del MCP |
| Cursor blink | `@keyframes cf-cursor-blink` | Contador del hero, bloque MCP |
| Magnetic hover | `mousemove` + `mouseleave` | Todos los CTAs `cf-magnetic` |
| Header scroll | `scroll` listener (rAF throttling) | Cabecera principal |
| Scroll progress | `scroll` listener (rAF throttling) | Barra superior `cf-scroll-progress` |
| Hero spotlight | `mousemove` limitado a `[data-cf-hero]` | Capa radial detras del cursor |
| Bento lift | CSS hover | Cards de features |
| Glow border | `@keyframes cf-glow-spin` 6s linear infinite | Card de MCP server |
| Marquee | `@keyframes cf-marquee-scroll` 60s linear infinite | Marcas de estado |
| Kanban task pop | `@keyframes cf-kanban-pop` 3.5s infinite | Tarea "Formulario de cita" |
| FAQ chevron | CSS transition + `[open]` | Items del FAQ |
| Mobile menu | Click toggle, click link, Escape | Menu hamburguesa |

**Sin animaciones que dependan de `width/height/top/left`**
para garantizar 60fps. Solo se animan `transform` y
`opacity` (mas `filter` en el spotlight).

## Accesibilidad

- `lang="es"` en el `<html>`.
- `<title>` descriptivo.
- `aria-labelledby` en cada `<section>` apuntando al titulo.
- `aria-label` en la navegacion principal.
- `aria-expanded` y `aria-controls` en el toggle del menu
  mobile.
- `aria-hidden="true"` en decoraciones puramente visuales
  (blobs, iconos decorativos, spotlight, codigo ficticio).
- Focus visible consistente (`outline: 2px solid #2563EB`)
  en links, botones, inputs y summaries.
- FAQ con `<details>/<summary>` nativos: accesible por
  teclado y screen readers sin JavaScript.
- `prefers-reduced-motion: reduce` desactiva todas las
  animaciones.

## Responsive

- Header: enlaces de navegacion se ocultan en mobile y se
  sustituyen por menu hamburguesa. CTA "Empezar gratis" se
  mantiene siempre visible.
- Hero: grid 1 columna en mobile, 2 columnas en `lg:` con
  ratio `1.05fr 0.95fr`.
- Bento: 1 columna en mobile, 2 en `sm:`, 6 en `lg:` con
  `auto-rows-[minmax(0,1fr)]` para igualar alturas.
- Dual view: 1 columna en mobile, 2 en `lg:` con linea
  central animada.
- MCP: 1 columna en mobile, 2 en `lg:` con ratio
  `0.95fr 1.05fr`.
- Numeros: 1 columna en mobile, 2 en `sm:`, 4 en `lg:`.
- Stack: 2 columnas en mobile, 3 en `sm:`, 6 en `lg:`.

## Verificacion

- `npm run build` compila sin errores: chunks separados
  `landing-*.css` (~6 KB) y `landing-*.js` (~4 KB).
- `node -c resources/js/landing.js` valida sintaxis.
- Render Blade en aislamiento: 95 KB de HTML, 63 reveals,
  6 contadores, 59 instancias de `#8B5CF6`, **0 instancias
  de `#B88746`**.
- Las 10 secciones numeradas (01-10) estan presentes.
- Las 16 colores de la paleta DESIGN.md estan representados.

## Decisiones que quedan fuera de scope

- I18n de la landing (solo castellano por ahora).
- Captura de leads / newsletter.
- Analytics / tracking.
- A/B testing.
- Demo en vivo embebida (los mockups son estaticos por
  decision: simular con HTML es mas rapido, ligero y no
  requiere backend).
- SEO avanzado (schema.org, OG tags completas). Solo
  description meta basica.
- Version de la landing en otro idioma.
- Reemplazo del acento dorado del icono PWA (es parte de
  la fase 9, no de esta).
