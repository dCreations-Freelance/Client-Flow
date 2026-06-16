# DESIGN.md — Design System de ClientFlow

## Filosofia visual

ClientFlow debe sentirse como un portal premium, un espacio privado y profesional. No como Jira, ni como un CRM complejo, ni como una plantilla generica de Tailwind. La interfaz debe transmitir calma, claridad y confianza.

El cliente debe entender el estado de su proyecto en menos de 10 segundos. El admin debe poder publicar contenido en menos de 2 minutos.

## Paleta de colores

### Fondos

| Nombre | Hex | Uso |
|---|---|---|
| Background | `#FAFAF7` | Fondo principal de la aplicacion |
| Surface | `#FFFFFF` | Cards, modales, paneles sobre fondo |
| Surface Hover | `#F4F1EA` | Hover en filas, items interactivos |
| Surface Active | `#EDE9DE` | Items seleccionados, estados activos |

### Bordes

| Nombre | Hex | Uso |
|---|---|---|
| Border | `#E7E2D8` | Bordes de cards, inputs, separadores |
| Border Strong | `#D8D0C3` | Bordes de secciones, hover en bordes |

### Texto

| Nombre | Hex | Uso |
|---|---|---|
| Text Primary | `#111827` | Titulos, texto principal |
| Text Secondary | `#6B7280` | Subtitulos, descripciones, labels |
| Text Muted | `#9CA3AF` | Placeholders, texto poco importante |
| Text Inverse | `#FFFFFF` | Texto sobre fondos oscuros |

### Colores semanticos

| Nombre | Hex | Uso |
|---|---|---|
| Primary | `#2563EB` | Acciones principales, links, CTA |
| Primary Hover | `#1D4ED8` | Hover en botones primary |
| Primary Light | `#EFF6FF` | Fondo de badges primary, highlights |
| Success | `#16A34A` | Estados completados, aprobaciones |
| Success Light | `#F0FDF4` | Fondo de badges success |
| Warning | `#D97706` | Estados pendientes, deadlines proximos |
| Warning Light | `#FFFBEB` | Fondo de badges warning |
| Danger | `#DC2626` | Errores, eliminar, urgencia |
| Danger Light | `#FEF2F2` | Fondo de badges danger |
| Info | `#8B5CF6` | Informacion, IA, elementos especiales |
| Info Light | `#F5F3FF` | Fondo de badges info |

## Tipografia

### Fuente principal

- **Instrument Sans** (400, 500, 600) via Bunny Fonts.
- Fallback: `ui-sans-serif, system-ui, sans-serif`.

### Escala tipografica

| Elemento | Tamaño | Peso | Clase Tailwind |
|---|---|---|---|
| H1 Pagina | 1.875rem (30px) | 600 | `text-3xl font-semibold` |
| H2 Seccion | 1.5rem (24px) | 600 | `text-2xl font-semibold` |
| H3 Subseccion | 1.25rem (20px) | 500 | `text-xl font-medium` |
| Body | 0.875rem (14px) | 400 | `text-sm` |
| Body Large | 1rem (16px) | 400 | `text-base` |
| Small | 0.75rem (12px) | 400 | `text-xs` |
| Caption | 0.75rem (12px) | 500 | `text-xs font-medium` |

### Line height

- Titulos: `leading-tight` (1.25)
- Body: `leading-normal` (1.5)
- UI densa (tablas, listas): `leading-relaxed` (1.625)

## Layout

### Layout admin

```
┌──────────────────────────────────────────────────┐
│  Header: logo + search bar + user menu          │
├────────┬─────────────────────────────────────────┤
│        │                                         │
│ Side-  │  Content area                           │
│ bar    │  (padding: p-6 lg:p-8)                  │
│ 240px  │                                         │
│        │                                         │
│        │                                         │
│        │                                         │
│        │                                         │
└────────┴─────────────────────────────────────────┘
```

- Sidebar: fija, 240px en desktop, colapsable en mobile.
- Header: sticky top, con search y avatar.
- Content: `flex-1`, scrollable, fondo `#FAFAF7`.

### Layout portal (cliente)

```
┌──────────────────────────────────────────────────┐
│  Header: logo + nombre org + notificaciones +user│
├────────┬─────────────────────────────────────────┤
│        │                                         │
│ Side-  │  Content area                           │
│ bar    │  (padding: p-6 lg:p-8)                  │
│ 220px  │                                         │
│        │                                         │
│        │                                         │
│        │                                         │
└────────┴─────────────────────────────────────────┘
```

- Sidebar: mas estrecha (220px), colores mas suaves.
- Header: incluye nombre de la organizacion.
- Content: mismo patron que admin pero con menos opciones.

### Layout auth

```
┌──────────────────────────────────────────────────┐
│                                                  │
│                                                  │
│           Card centrada (max-w-md)               │
│           Fondo: Surface (#FFFFFF)               │
│           Border radius: rounded-[28px]          │
│           Padding: p-8                            │
│                                                  │
│                                                  │
└──────────────────────────────────────────────────┘
```

- Fondo: `#FAFAF7`.
- Card: blanca, `rounded-[28px]`, `shadow-sm`.
- Logo centrado arriba.

## Componentes

### Card (base)

```html
<div class="bg-white rounded-xl border border-[#E7E2D8] p-6">
  <!-- contenido -->
</div>
```

Variantes:
- **Default**: `bg-white rounded-xl border border-[#E7E2D8] p-6`
- **Hover**: + `hover:border-[#D8D0C3] hover:shadow-sm transition-all`
- **Interactive**: + `cursor-pointer hover:border-[#D8D0C3]` para cards clickeables
- **Highlighted**: + `ring-2 ring-[#2563EB]` para card seleccionada

### Button

**Primary** (accion principal):
```html
<button class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-[#2563EB] rounded-lg hover:bg-[#1D4ED8] transition-colors">
  Accion
</button>
```

**Secondary** (accion alternativa):
```html
<button class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-[#111827] bg-white border border-[#E7E2D8] rounded-lg hover:bg-[#F4F1EA] transition-colors">
  Accion
</button>
```

**Ghost** (accion sutil):
```html
<button class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827] hover:bg-[#F4F1EA] rounded-lg transition-colors">
  Accion
</button>
```

**Danger** (accion destructiva):
```html
<button class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-[#DC2626] rounded-lg hover:bg-[#B91C1C] transition-colors">
  Eliminar
</button>
```

**Sizes**:
- Default: `px-4 py-2 text-sm`
- Small: `px-3 py-1.5 text-xs`
- Large: `px-6 py-3 text-base`

### Badge

```html
<!-- Status badge -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#EFF6FF] text-[#2563EB]">
  Planning
</span>
```

Colores por tipo:
- **Info/Default**: `bg-[#EFF6FF] text-[#2563EB]`
- **Success**: `bg-[#F0FDF4] text-[#16A34A]`
- **Warning**: `bg-[#FFFBEB] text-[#D97706]`
- **Danger**: `bg-[#FEF2F2] text-[#DC2626]`
- **Neutral**: `bg-[#F4F1EA] text-[#6B7280]`

### Input

```html
<div>
  <label class="block text-sm font-medium text-[#111827] mb-1">Label</label>
  <input type="text" class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent" placeholder="Placeholder">
</div>
```

Variantes:
- **Error**: + `border-[#DC2626] focus:ring-[#DC2626]`
- **Disabled**: `bg-[#F4F1EA] opacity-60 cursor-not-allowed`

### Textarea

Igual que input pero con `min-h` y resize vertical:
```html
<textarea class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent resize-y min-h-[120px]"></textarea>
```

### Select

```html
<select class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
  <option>Opcion 1</option>
</select>
```

### Sidebar item

```html
<a href="#" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-[#6B7280] rounded-lg hover:bg-[#F4F1EA] hover:text-[#111827] transition-colors">
  <!-- Icono 20x20 -->
  <span>Menu item</span>
</a>
```

**Activo**:
```html
<a href="#" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-[#2563EB] bg-[#EFF6FF] rounded-lg">
  <span>Menu item</span>
</a>
```

### Tabla

```html
<div class="overflow-x-auto bg-white rounded-xl border border-[#E7E2D8]">
  <table class="w-full">
    <thead>
      <tr class="border-b border-[#E7E2D8] bg-[#FAFAF7]">
        <th class="px-4 py-3 text-left text-xs font-medium text-[#6B7280] uppercase tracking-wider">Columna</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-[#E7E2D8]">
      <tr class="hover:bg-[#F4F1EA] transition-colors">
        <td class="px-4 py-3 text-sm text-[#111827]">Dato</td>
      </tr>
    </tbody>
  </table>
</div>
```

### Avatar

```html
<div class="flex items-center justify-center w-8 h-8 rounded-full bg-[#2563EB] text-white text-xs font-medium">
  DR
</div>
```

Colores por usuario (hash del nombre):
- Azul: `bg-[#2563EB]`
- Verde: `bg-[#16A34A]`
- Naranja: `bg-[#D97706]`
- Morado: `bg-[#8B5CF6]`
- Rosa: `bg-[#DB2777]`

### Empty state

```html
<div class="flex flex-col items-center justify-center py-12 text-center">
  <div class="w-12 h-12 rounded-full bg-[#F4F1EA] flex items-center justify-center mb-4">
    <!-- Icono -->
  </div>
  <h3 class="text-sm font-medium text-[#111827] mb-1">Sin resultados</h3>
  <p class="text-sm text-[#6B7280] mb-4">Descripcion del empty state.</p>
  <button class="...">Accion principal</button>
</div>
```

### Notification badge

```html
<div class="relative">
  <span class="absolute -top-1 -right-1 w-4 h-4 bg-[#DC2626] text-white text-[10px] font-medium rounded-full flex items-center justify-center">3</span>
  <!-- Icono campana -->
</div>
```

### Toast / Flash message

```html
<div class="fixed bottom-4 right-4 z-50 flex items-center gap-2 px-4 py-3 text-sm font-medium text-white bg-[#16A34A] rounded-lg shadow-lg">
  <!-- Icono check -->
  Mensaje de exito
</div>
```

Variantes:
- **Success**: `bg-[#16A34A]`
- **Error**: `bg-[#DC2626]`
- **Info**: `bg-[#2563EB]`
- **Warning**: `bg-[#D97706]`

## Componentes especificos

### Kanban board

```
┌──────────────────────────────────────────────────────────────────┐
│  [Filtros: Prioridad ▼  Asignado ▼  Fecha ▼]   + Nueva tarea  │
├──────────────┬──────────────┬──────────────┬──────────────────────┤
│  To Do (3)   │ In Progress  │   Review     │  Done (5)           │
│              │    (2)       │    (1)       │                      │
│ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐       │
│ │ Card     │ │ │ Card     │ │ │ Card     │ │ │ Card     │       │
│ │ tarea    │ │ │ tarea    │ │ │ tarea    │ │ │ tarea    │       │
│ └──────────┘ │ └──────────┘ │ └──────────┘ │ └──────────┘       │
│ ┌──────────┐ │ ┌──────────┐ │               │ ┌──────────┐       │
│ │ Card     │ │ │ Card     │ │               │ │ Card     │       │
│ └──────────┘ │ └──────────┘ │               │ └──────────┘       │
│ ┌──────────┐ │              │               │                     │
│ │ Card     │ │              │               │                     │
│ └──────────┘ │              │               │                     │
└──────────────┴──────────────┴──────────────┴──────────────────────┘
```

- Columnas: scroll horizontal en mobile, 4-5 columnas en desktop.
- Card de tarea: `bg-white rounded-lg border border-[#E7E2D8] p-3`, drag & drop.
- Card hover: `shadow-md` y `border-[#D8D0C3]`.
- Badges de prioridad en esquina superior derecha de cada card.
- Badge de tipo (feature/bug): icono + color.
- Subtareas: mini progress bar dentro de la card (2/5 subtareas).
- Due date: icono calendario + fecha en texto small si esta cerca.

### Task card (dentro del kanban)

```html
<div class="bg-white rounded-lg border border-[#E7E2D8] p-3 hover:shadow-md hover:border-[#D8D0C3] transition-all cursor-grab">
  <div class="flex items-start justify-between gap-2 mb-2">
    <span class="text-sm font-medium text-[#111827]">Titulo de la tarea</span>
    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-[#FEF2F2] text-[#DC2626]">Critical</span>
  </div>
  <p class="text-xs text-[#6B7280] line-clamp-2 mb-2">Descripcion corta...</p>
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-1.5">
      <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-[#EFF6FF] text-[#2563EB]">Feature</span>
      <span class="text-[10px] text-[#9CA3AF]">⏱ 4h est.</span>
    </div>
    <div class="w-6 h-6 rounded-full bg-[#2563EB] text-white text-[10px] font-medium flex items-center justify-center">DR</div>
  </div>
</div>
```

### Chat bubble

```html
<!-- Mensaje propio (admin) -->
<div class="flex justify-end">
  <div class="max-w-[70%] bg-[#2563EB] text-white rounded-2xl rounded-br-sm px-4 py-2.5">
    <p class="text-sm">Mensaje del admin</p>
    <span class="text-[10px] text-blue-200 mt-1 block">10:30</span>
  </div>
</div>

<!-- Mensaje de otro (cliente) -->
<div class="flex justify-start">
  <div class="max-w-[70%] bg-white border border-[#E7E2D8] rounded-2xl rounded-bl-sm px-4 py-2.5">
    <p class="text-sm text-[#111827]">Mensaje del cliente</p>
    <span class="text-[10px] text-[#9CA3AF] mt-1 block">10:28</span>
  </div>
</div>

<!-- Mensaje de sistema -->
<div class="flex justify-center">
  <div class="px-3 py-1 bg-[#F4F1EA] rounded-full text-xs text-[#6B7280]">
    Se creo la tarea "Implementar login"
  </div>
</div>

<!-- Mensaje con adjunto -->
<div class="flex justify-start">
  <div class="max-w-[70%] bg-white border border-[#E7E2D8] rounded-2xl rounded-bl-sm px-4 py-2.5">
    <p class="text-sm text-[#111827]">Te envio el diseno actualizado</p>
    <div class="flex items-center gap-2 mt-2 px-3 py-2 bg-[#FAFAF7] rounded-lg border border-[#E7E2D8]">
      <span class="text-sm text-[#6B7280]">📎</span>
      <div class="min-w-0 flex-1">
        <span class="block text-xs font-medium text-[#111827] truncate">diseno-final-v2.pdf</span>
        <span class="block text-[10px] text-[#9CA3AF]">2.4 MB</span>
      </div>
      <button class="shrink-0 text-[#2563EB] hover:text-[#1D4ED8]">
        <span class="text-xs font-medium">Descargar</span>
      </button>
    </div>
    <div class="flex items-center justify-between mt-1">
      <span class="text-[10px] text-[#9CA3AF]">10:28</span>
      <!-- Check de leido -->
      <span class="text-[10px] text-[#2563EB]">✓✓</span>
    </div>
  </div>
</div>
```

### Chat bubble - indicador de leido

```html
<!-- Check solitario (enviado, no leido) -->
<span class="text-[10px] text-[#9CA3AF]">✓</span>

<!-- Doble check (leido) -->
<span class="text-[10px] text-[#2563EB]">✓✓</span>
```

### Adjunto en tarea (detalle)

```html
<div class="flex items-center gap-3 px-4 py-3 bg-white rounded-lg border border-[#E7E2D8] hover:border-[#D8D0C3] transition-colors">
  <div class="w-10 h-10 rounded-lg bg-[#F4F1EA] flex items-center justify-center text-sm text-[#6B7280]">
    📄
  </div>
  <div class="min-w-0 flex-1">
    <span class="block text-sm font-medium text-[#111827] truncate">documento-tecnico.pdf</span>
    <span class="block text-xs text-[#6B7280]">Subido por Daniel · 2.4 MB · hace 2h</span>
  </div>
  <div class="flex items-center gap-2 shrink-0">
    <button class="text-[#6B7280] hover:text-[#111827] transition-colors">
      <!-- Icono descargar -->
    </button>
    <button class="text-[#DC2626] hover:text-[#B91C1C] transition-colors">
      <!-- Icono eliminar -->
    </button>
  </div>
</div>
```

### Temporizador (registro de tiempo)

```html
<div class="flex items-center gap-3 px-4 py-3 bg-white rounded-lg border border-[#E7E2D8]">
  <div class="flex items-center gap-1.5">
    <span class="w-2 h-2 rounded-full bg-[#DC2626] animate-pulse"></span>
    <span class="text-lg font-semibold text-[#111827] tabular-nums">01:23:45</span>
  </div>
  <span class="text-xs text-[#6B7280]">Trabajando en: Implementar login</span>
  <button class="ml-auto px-3 py-1.5 text-xs font-medium text-white bg-[#DC2626] rounded-lg hover:bg-[#B91C1C] transition-colors">
    Detener
  </button>
</div>
```

### Feed de actividad

```html
<div class="flow-root">
  <ul class="-mb-8">
    <li>
      <div class="relative pb-8">
        <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-[#E7E2D8]"></span>
        <div class="relative flex items-start gap-4">
          <div class="w-10 h-10 rounded-full bg-[#EFF6FF] flex items-center justify-center shrink-0">
            <span class="text-sm text-[#2563EB]">✓</span>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm text-[#111827]">
              <span class="font-medium">Daniel</span> completo la tarea
              <span class="font-medium">"Implementar login"</span>
            </p>
            <span class="text-xs text-[#9CA3AF]">hace 2 horas</span>
          </div>
        </div>
      </div>
    </li>
  </ul>
</div>
```

### Markdown editor con preview

```html
<div class="border border-[#E7E2D8] rounded-lg overflow-hidden">
  <!-- Tabs: Editor | Preview -->
  <div class="flex border-b border-[#E7E2D8] bg-[#FAFAF7]">
    <button class="px-4 py-2 text-sm font-medium text-[#2563EB] border-b-2 border-[#2563EB]">Editor</button>
    <button class="px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]">Vista previa</button>
  </div>
  <!-- Toolbar -->
  <div class="flex items-center gap-1 px-3 py-2 border-b border-[#E7E2D8] bg-white">
    <!-- Botones: Bold, Italic, Heading, List, Code, Link -->
  </div>
  <!-- Textarea -->
  <textarea class="w-full px-4 py-3 text-sm font-mono bg-white resize-y min-h-[200px] focus:outline-none" placeholder="Escribe en markdown..."></textarea>
</div>
```

### Progress bar (proyecto)

```html
<div class="w-full">
  <div class="flex items-center justify-between mb-1">
    <span class="text-xs font-medium text-[#6B7280]">Progreso</span>
    <span class="text-xs font-medium text-[#111827]">65%</span>
  </div>
  <div class="w-full h-2 bg-[#F4F1EA] rounded-full overflow-hidden">
    <div class="h-full bg-[#2563EB] rounded-full transition-all" style="width: 65%"></div>
  </div>
</div>
```

### Calendar view

- Vista mensual default, semanal como alternativa.
- Dia con evento: dot de color + primer evento visible en el dia.
- Dia hoy: borde `#2563EB`.
- Dia selecconado: fondo `#EFF6FB`.
- Evento card dentro del dia: `text-xs truncate` con color por tipo (meeting=blue, deadline=orange, milestone=green).

## Patrones de UI

### Listado con busqueda

```
┌─────────────────────────────────────────────────────┐
│  [Search input]                    [+ Nuevo item]   │
├─────────────────────────────────────────────────────┤
│  Filtros: Estado ▼  Prioridad ▼  Fecha ▼           │
├─────────────────────────────────────────────────────┤
│  Item 1                                    Accion   │
│  Item 2                                    Accion   │
│  Item 3                                    Accion   │
├─────────────────────────────────────────────────────┤
│  Mostrando 1-15 de 42   < Anterior  Siguiente >    │
└─────────────────────────────────────────────────────┘
```

### Detalle con tabs

```
┌─────────────────────────────────────────────────────┐
│  ← Volver   Titulo del item    [Editar] [Eliminar]  │
│  Subtitulo o descripcion                            │
├─────────────────────────────────────────────────────┤
│  Tab 1 | Tab 2 | Tab 3 | Tab 4                      │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Contenido del tab activo                           │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Confirmacion de accion

Modal centrado:
```html
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
  <div class="bg-white rounded-[28px] p-8 max-w-md w-full shadow-xl">
    <h3 class="text-lg font-semibold text-[#111827] mb-2">Titulo</h3>
    <p class="text-sm text-[#6B7280] mb-6">Descripcion de la accion.</p>
    <div class="flex items-center justify-end gap-3">
      <button class="px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]">Cancelar</button>
      <button class="px-4 py-2 text-sm font-medium text-white bg-[#DC2626] rounded-lg hover:bg-[#B91C1C]">Confirmar</button>
    </div>
  </div>
</div>
```

## Responsive

### Breakpoints

| Nombre | Min-width | Uso |
|---|---|---|
| sm | 640px | Mobile horizontal |
| md | 768px | Tablet |
| lg | 1024px | Desktop |
| xl | 1280px | Desktop amplio |

### Comportamiento responsive

- **Sidebar**: oculta en mobile con hamburger menu, visible en `lg:`.
- **Kanban**: scroll horizontal en mobile, columnas en desktop.
- **Tablas**: scroll horizontal en mobile, completa en desktop.
- **Chat**: full-width en mobile, con sidebar opcional en desktop.
- **Formularios**: full-width en mobile, max-w-2xl en desktop.
- **Cards de listado**: 1 columna en mobile, 2 en `md`, 3 en `lg`.

## Iconos

Usar **Heroicons** (outline, 20px para UI, 24px para headers). Importar via `@heroicons/outline` en Vite.

Iconos clave:
- Home: `HomeIcon`
- Organizaciones: `BuildingOfficeIcon`
- Proyectos: `FolderIcon`
- Kanban: `ViewColumnsIcon`
- Tareas: `CheckCircleIcon`
- Documentos: `DocumentTextIcon`
- Chat: `ChatBubbleLeftRightIcon`
- Calendario: `CalendarIcon`
- Config IA: `Cog6ToothIcon`
- Agentes: `CommandLineIcon`
- Notificaciones: `BellIcon`
- Buscar: `MagnifyingGlassIcon`
- Agregar: `PlusIcon`
- Editar: `PencilIcon`
- Eliminar: `TrashIcon`
- Volver: `ArrowLeftIcon`

## Espaciado

- Pagina: `p-6 lg:p-8`
- Card interna: `p-6`
- Gap entre cards en grid: `gap-4 lg:gap-6`
- Gap entre secciones: `space-y-6`
- Gap entre items en lista: `divide-y divide-[#E7E2D8]`

## Animaciones

- Transiciones: `transition-all duration-200` (default), `duration-150` para micro-interacciones.
- Hover en cards: `shadow-sm` → `shadow-md`.
- Drag en kanban: `opacity-75 scale-95` mientras se arrastra.
- Aparicion de modales: fade in + slight scale up.
- No usar animaciones pesadas ni parpadeos.

## Reglas de accesibilidad

- Contraste minimo WCAG AA en texto (4.5:1).
- Focus visible en todos los elementos interactivos (`focus:ring-2 focus:ring-[#2563EB]`).
- Labels en todos los formularios.
- Alt text en imagenes.
- Navegacion con teclado funcional (tab order logico).
- No depender solo de color para transmitir informacion (usar iconos + texto + color).