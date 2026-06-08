# DESIGN.md — Sistema visual de ClientFlow

## Objetivo visual

ClientFlow debe parecer una herramienta premium de comunicación profesional, no un panel administrativo genérico. Debe transmitir claridad, confianza, calma, profesionalidad, seguimiento constante y producto moderno.

Debe evitar apariencia Bootstrap, exceso de colores, exceso de bordes, tablas pesadas, iconografía infantil, estética gamer/friki, modo oscuro como base y sensación de plantilla Tailwind.

## Personalidad visual

Premium, sobrio, minimalista, humano, cálido, editorial, SaaS moderno y profesional sin ser frío.

Referencias: Linear por limpieza, Stripe por acabado premium, Notion por claridad, Arc por suavidad visual y Superhuman por herramienta cuidada.

## Layout base escritorio

Resolución objetivo: 1440x900.

```txt
┌─────────────────────────────────────────────────────────────┐
│ Sidebar │ Topbar                                           │
│         ├───────────────────────────────────────────────────┤
│         │ Contenido principal                              │
└─────────┴───────────────────────────────────────────────────┘
```

Sidebar: 260px admin, 240px cliente. Topbar: 72px. Contenido: máximo 1180px, padding horizontal 32px, vertical 28px.

## Grid

Sistema de 12 columnas. Gutter 24px. Cards KPI 3 columnas, panel principal 8 columnas, panel lateral 4 columnas, formularios máximo 760px.

## Colores

```css
--background: #FAFAF7;
--surface: #FFFFFF;
--surface-soft: #F4F1EA;
--text-primary: #111827;
--text-secondary: #6B7280;
--text-muted: #9CA3AF;
--border: #E7E2D8;
--border-strong: #D8D0C3;
--brand: #111827;
--accent: #B88746;
--success: #15803D;
--success-soft: #DCFCE7;
--warning: #B45309;
--warning-soft: #FEF3C7;
--danger: #B91C1C;
--danger-soft: #FEE2E2;
--info: #1D4ED8;
--info-soft: #DBEAFE;
```

El acento dorado se usa poco: estados destacados, badges premium, links importantes e iconos de hito.

## Tipografía

Inter o system-ui. H1 28px/700, H2 20px/650, texto normal 15-16px con line-height 1.6, auxiliar 13-14px.

## Radios y sombras

```css
--radius-sm: 10px;
--radius-md: 14px;
--radius-lg: 20px;
--radius-xl: 28px;
--shadow-card: 0 10px 30px rgba(17, 24, 39, 0.05);
--shadow-popover: 0 20px 60px rgba(17, 24, 39, 0.12);
```

## Componentes

### Botón primario

Fondo oscuro, texto blanco, radio 12px, padding 12px 18px. Uso: crear, publicar, aprobar, guardar.

### Botón secundario

Fondo blanco, borde suave, texto principal. Uso: cancelar, ver detalle, descargar, editar.

### Project Card

Nombre, cliente, estado, progreso, última actualización, próximo hito y pendientes. Card blanca, radio 20px, padding 22px, borde suave.

### Timeline Card

Icono, fecha, autor, título, resumen, adjuntos y comentarios.

### Visual Diary Card

Thumbnail grande, tipo de contenido, título, duración, fecha y CTA “Ver avance”.

### Deliverable Card

Nombre, estado, fecha envío, fecha aprobación, acciones y comentarios pendientes.

### Empty State Card

Mensaje claro, explicación corta y acción principal.

## Badges

Planificación gris cálido, en progreso azul suave, esperando cliente ámbar suave, en revisión violeta suave, finalizado verde suave, pausado gris y archivado gris desaturado.

## Timeline

```txt
Línea vertical fina
│
● Evento importante
│
○ Evento normal
│
◆ Entregable
│
✓ Aprobación
```

## Diario visual

Debe ser el módulo más diferencial. Vista en grid con cards visuales, filtros por tipo, thumbnails grandes y detalle tipo media viewer.

## Formularios

Máximo 760px, labels claros, ayuda contextual, errores debajo del campo y secciones separadas.

## Tablas

Header ligero, filas altas, avatar o inicial, estado con badge, acciones agrupadas, filtros arriba y búsqueda visible.

## Microcopy

Claro, profesional, cercano y sin tecnicismos: “Pendiente de tu revisión”, “Última actualización hace 2 horas”, “Este entregable necesita tu aprobación”.

## Reglas para IA visual

No generar dashboard genérico, no usar gráficos innecesarios, no modo oscuro, no neones, no llenar de iconos. Prioridad: estado del proyecto y diario visual.
