# Fase 2.5 — Hub del proyecto (UI/UX de `/admin/projects/{id}` y `/portal/projects/{id}`)

Redisenyo de la pagina de detalle de proyecto para que actue como un
hub: cabecera sticky, tiles de resumen, previews de tablero, documentos,
evento proximo, ultimo mensaje y equipo. No introduce logica de
negocio nueva: reutiliza los datos y relaciones ya existentes en
fases 2-8.

## Alcance

- Convertir `admin.projects.show` y `portal.projects.show` en hubs
  de proyecto, eliminando la `x-ui.card` unica gigante con todos
  los campos apilados.
- Centralizar la carga de datos (previews, contadores, mensajes no
  leidos) en un servicio reusable.
- Mantener la paridad de estructura entre admin y portal, con menos
  controles y copy mas tranquilizador en el portal.
- Reutilizar componentes Blade ya existentes (`x-ui.card`,
  `x-partials.status-badge`, `x-partials.progress-bar`,
  `x-partials.document-visibility-badge`).
- Sin cambios en policies, rutas, migraciones, enums ni modelos.

## Cambios por capa

### 1. DTOs

| Archivo | Proposito |
|---|---|
| `app/DTOs/Project/ProjectSummary.php` | Snapshot readonly con todo lo que la vista necesita: proyecto cargado con counts, previews, miembros, mensajes, evento, etiqueta y tono de "proxima entrega". |
| `app/DTOs/Project/BoardColumnPreview.php` | Mini-vista de una columna para el preview del kanban (columna + hasta 3 tareas). |

Decisiones:
- `final readonly` con promoted properties para evitar mutaciones
  accidentales y dejar el contrato claro.
- Los labels de "proxima entrega" se calculan una sola vez en el
  servicio (es / danger / warning / success) para que la vista no
  tenga que ramificar.

### 2. Servicios

| Archivo | Proposito |
|---|---|
| `app/Services/Project/ProjectSummaryService.php` | Carga eager del proyecto con `load` + `loadCount`, resuelve previews (kanban, documentos, miembros, mensaje, evento), calcula el campo `nextDelivery` humano y centraliza el calculo de mensajes no leidos. |

API:
- `loadForAdmin(Project, User): ProjectSummary` — carga todo
  (incluidos documentos privados).
- `loadForPortal(Project, User): ProjectSummary` — aplica el scope
  publico de documentos y respeta los marcadores de lectura del
  cliente.

El servicio reutiliza la logica de mensajes no leidos que antes
estaba duplicada en `Admin/ProjectController::unreadCountsFor` y
`Portal/ProjectController::unreadCountsFor`. La logica de
`unreadCountsFor` para listados de muchos proyectos se mantiene en
los controladores porque el caso de uso es distinto (batch en vez
de un solo proyecto).

### 3. Controladores

- `app/Http/Controllers/Admin/ProjectController.php:show` ahora
  delega en `ProjectSummaryService::loadForAdmin` y pasa
  `$availableMembers` (necesario para el componente Livewire
  `project-members`) calculado a partir de la membresia de la
  organizacion.
- `app/Http/Controllers/Portal/ProjectController.php:show` ahora
  delega en `ProjectSummaryService::loadForPortal`. Se anade
  `Request` a la firma del metodo para acceder a `$request->user()`.

### 4. Partials / UI

| Archivo | Proposito |
|---|---|
| `resources/views/components/partials/project-breadcrumbs.blade.php` | Migas de pan (`Organizaciones > Acme > Proyecto`). El ultimo crumb se renderiza en texto plano, los anteriores como enlaces. |
| `resources/views/components/partials/project-hero.blade.php` | Cabecera sticky con breadcrumbs, titulo, badges de status / archivado y slot de acciones. Se ancla `top-16` para quedar justo debajo del header del layout. |
| `resources/views/components/partials/project-stat-tile.blade.php` | Tile clickeable con titulo, valor, subtitulo y tono (`primary` / `success` / `warning` / `danger` / `neutral`). Opcionalmente con badge para contadores (mensajes sin leer). |
| `resources/views/components/partials/project-previews.blade.php` | Bloque 2-columnas con descripcion + preview del kanban + documentos recientes en la principal; proximo evento, ultimo mensaje y grid de equipo en la sidebar. |

Notas de diseno:
- `top-16` en el hero para no superponerse con el header sticky del
  layout (`top-0`, altura 64px).
- En la sidebar del portal, los enlaces de gestion se ocultan y
  se usa un copy mas neutro (no "Gestionar miembros", sino
  ninguno).
- Los colores del tile usan la paleta del design system
  (`#2563EB`, `#16A34A`, `#D97706`, `#DC2626`) y se aplican solo
  al valor, no a la card completa.

### 5. Vistas Blade

- `resources/views/admin/projects/show.blade.php`: reescrita
  completa. Hero + grid de 4 tiles + bloque de previews +
  componente Livewire de miembros al final.
- `resources/views/portal/projects/show.blade.php`: reescrita
  completa con la misma estructura pero sin acciones de edicion
  / archivo / agentes; copy mas cliente en los tiles y empty
  states.

Las acciones en el hero se siguen renderizando todas
condicionalmente con `Route::has(...)` para no romper si una
ruta no esta definida en una fase temprana.

### 6. Rutas

Sin cambios. La URL `/admin/projects/{project}` y
`/portal/projects/{project}` siguen siendo las mismas y devuelven
200 OK.

## Rendimiento

El servicio hace una sola carga eager del proyecto con
`loadCount` para los totales (miembros, mensajes, documentos,
eventos, tareas raiz). Los previews de documentos, miembros,
evento y ultimo mensaje se calculan con queries adicionales
minimas (siempre limitadas via `limit`).

- Queries principales en el `show`:
  - 1 `select` del proyecto + relaciones + counts (incluye
    `public_documents_count` como `loadCount` con closure para
    el portal, sin query extra).
  - 1 query para `boardPreview` (columnas + tareas en una sola
    `whereIn`).
  - 1 query agrupada para `columnCounts` (un `count` agrupado por
    slug, no N+1).
  - 1 query para `previewDocuments` (top 3 con `recent`).
  - 1 query para `latestMessage` (orden por id desc, `first`).
  - 1 query para `nextEvent` (scope `upcoming`).
  - 1 query para `unreadCount` (ProjectChatRead + count).

En el peor caso son ~8 queries por render, todas con indices
apropiados. La duplicacion previa entre admin y portal se elimina
y la query de `unreadCountsFor` para listados se mantiene intacta.

## Tests unitarios del servicio

- `tests/Unit/Services/ProjectSummaryServiceTest.php`: anade
  cobertura del calculo de `nextDelivery` (vencida, hoy, futuro,
  entregada, sin fecha) y del filtro de documentos publicos del
  portal, para fijar el contrato del servicio y evitar
  regresiones.

## Verificacion

```bash
cd app
php artisan view:clear
php artisan test --filter="Project"   # 120 tests, 290 aserciones
```

Resultado:
- 120 tests verdes (todos los relacionados con proyectos:
  `ProjectViewTest`, `ProjectManagementTest`, `ProjectMemberTest`,
  `ProjectArchiveTest`, `ProjectAgentManagementTest`,
  `ProjectTest` unit y, por coincidencia del filtro,
  `Portal\AiChatTest` que matchea "Project").
- 0 tests rojos nuevos (los 25 fallos que aparecen en la suite
  completa son pre-existentes: 21 de `Notification*`, 3 de
  `PasswordResetTest` y 1 de `HomeTest`; mas 13 fallos adicionales
  introducidos por cambios en progreso del working tree que no
  pertenecen a esta fase).

## Decisiones tecnicas relevantes

1. **DTO readonly en lugar de ViewModel**: el proyecto no usaba
   ViewModels todavia y los datos que necesita el hub caben en un
   DTO inmutable. Esto evita acoplar la vista a Eloquent y deja
   el servicio libre de cambiar la representacion interna.

2. **Hero sticky con `top-16`**: el layout tiene un header sticky
   en `top-0` con altura aproximada de 64px. Anadir el hero como
   `sticky top-16` hace que se quede visible al hacer scroll
   pero sin superponerse al menu principal.

3. **Mini-kanban con `columnCounts` separado del preview**: el
   preview muestra 3 tareas por columna, pero el contador entre
   parentesis refleja el total real. Asi el cliente ve "hay
   movimiento" sin perder el conteo exacto.

4. **El portal no incluye "Gestionar miembros"**: la gestion es
   solo del admin. El cliente solo ve el grid como referencia de
   quien esta en su proyecto.

5. **`unreadCount` unificado, `unreadCountsFor` preservado**: el
   servicio encapsula el calculo para un solo proyecto (caso del
   show), pero los listados (`index`) siguen necesitando el batch
   helper privado en cada controlador. Consolidar ambos en una
   sola pieza añadia complejidad sin beneficio claro.

6. **Documentos privados excluidos del portal**: el servicio
   reaplica el scope `public` en el query de previews cuando se
   sirve al portal. La doble barrera (policy + service) sigue el
   mismo patron que en el resto de la app.

## Pendiente (fuera de scope de fase 2.5)

- Cover image banner (`$project->cover_path`): la columna existe
  pero el upload se difiere a una fase posterior. Cuando se
  anada, el hero debera aceptar una prop `cover` y renderizar
  un banner superior opcional.
- Cambio inline de status / visibilidad desde el hub (sin ir a
  `/edit`): descartado en la decision de alcance de esta fase.
- Acciones responsive: en < md los botones del hero se reagrupan
  en un menu `...`; queda pendiente de validar visualmente con
  datos reales en el navegador.
- Empty states con icono: actualmente son texto plano, consistente
  con el resto de la app. Si se quiere mejorar, se pueden reusar
  los `x-ui.empty-state` que se anadan en futuras fases.
