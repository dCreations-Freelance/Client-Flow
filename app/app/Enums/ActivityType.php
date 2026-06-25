<?php

namespace App\Enums;

/**
 * Tipo de evento del feed de actividad del proyecto.
 *
 * Cada caso representa un "suceso" discreto que el feed muestra
 * como una entrada cronologica. La cerradura del enum (frente a
 * un `string` libre) garantiza que el resto del sistema pueda
 * hacer `match` exhaustivo y que un valor mal escrito se rechace
 * en tiempo de codigo.
 *
 * Ademas de la etiqueta legible (`label()`), cada caso expone:
 *
 * - `icon()`: nombre del icono SVG usado por el partial del feed.
 * - `tone()`: color semantico del borde izquierdo del item.
 * - `category()`: agrupacion logica para los chips del filtro
 *   (Tareas, Documentos, Eventos, Mensajes, Proyecto, Miembros).
 *   El chip "Todas" equivale a no filtrar.
 * - `isPublic()`: si el caso es visible para clientes del portal.
 *   El "set conservador" (PRD) deja a los clientes solo los
 *   eventos que ya ven en otras vistas: tareas creadas / movidas /
 *   completadas, documentos publicos, eventos, mensajes humanos,
 *   cambios de estado y archivado. El resto queda admin-only
 *   (auditoria interna: borrados, miembros, plantillas).
 */
enum ActivityType: string
{
    // -----------------------------------------------------------------
    // Tareas
    // -----------------------------------------------------------------
    case TaskCreated = 'task_created';
    case TaskCompleted = 'task_completed';
    case TaskReopened = 'task_reopened';
    case TaskMoved = 'task_moved';
    case TaskUpdated = 'task_updated';
    case TaskDeleted = 'task_deleted';

    // -----------------------------------------------------------------
    // Documentos
    // -----------------------------------------------------------------
    case DocumentCreated = 'document_created';
    case DocumentUpdated = 'document_updated';
    case DocumentPublished = 'document_published';
    case DocumentDeleted = 'document_deleted';

    // -----------------------------------------------------------------
    // Proyecto
    // -----------------------------------------------------------------
    case ProjectCreated = 'project_created';
    case StatusChanged = 'status_changed';
    case ProjectArchived = 'project_archived';
    case ProjectUnarchived = 'project_unarchived';
    case TemplateApplied = 'template_applied';

    // -----------------------------------------------------------------
    // Miembros
    // -----------------------------------------------------------------
    case MemberAdded = 'member_added';
    case MemberRemoved = 'member_removed';

    // -----------------------------------------------------------------
    // Calendario
    // -----------------------------------------------------------------
    case EventCreated = 'event_created';
    case EventUpdated = 'event_updated';
    case EventDeleted = 'event_deleted';

    // -----------------------------------------------------------------
    // Adjuntos y chat humano
    // -----------------------------------------------------------------
    case AttachmentUploadedToTask = 'attachment_uploaded_to_task';
    case MessageSent = 'message_sent';

    /**
     * Etiqueta legible en castellano para badges y listados.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::TaskCreated => 'Tarea creada',
            self::TaskCompleted => 'Tarea completada',
            self::TaskReopened => 'Tarea re-abierta',
            self::TaskMoved => 'Tarea movida',
            self::TaskUpdated => 'Tarea actualizada',
            self::TaskDeleted => 'Tarea eliminada',

            self::DocumentCreated => 'Documento creado',
            self::DocumentUpdated => 'Documento actualizado',
            self::DocumentPublished => 'Documento publicado',
            self::DocumentDeleted => 'Documento eliminado',

            self::ProjectCreated => 'Proyecto creado',
            self::StatusChanged => 'Estado cambiado',
            self::ProjectArchived => 'Proyecto archivado',
            self::ProjectUnarchived => 'Proyecto desarchivado',
            self::TemplateApplied => 'Plantilla aplicada',

            self::MemberAdded => 'Miembro anadido',
            self::MemberRemoved => 'Miembro eliminado',

            self::EventCreated => 'Evento creado',
            self::EventUpdated => 'Evento actualizado',
            self::EventDeleted => 'Evento eliminado',

            self::AttachmentUploadedToTask => 'Adjuntos subidos',
            self::MessageSent => 'Mensaje enviado',
        };
    }

    /**
     * Identificador del icono SVG usado por el partial del feed.
     *
     * Se mantiene como string para que el partial decida que
     * clase de Tailwind o que sprite usar. Mantener el match
     * exhaustivo aqui obliga a que cualquier caso nuevo tenga
     * un icono pensado (no por defecto), igual que `label()`.
     *
     * @return string
     */
    public function icon(): string
    {
        return match ($this) {
            self::TaskCreated,
            self::TaskCompleted,
            self::TaskReopened,
            self::TaskMoved,
            self::TaskUpdated,
            self::TaskDeleted => 'task',

            self::DocumentCreated,
            self::DocumentUpdated,
            self::DocumentPublished,
            self::DocumentDeleted => 'document',

            self::ProjectCreated,
            self::StatusChanged,
            self::ProjectArchived,
            self::ProjectUnarchived,
            self::TemplateApplied => 'project',

            self::MemberAdded,
            self::MemberRemoved => 'member',

            self::EventCreated,
            self::EventUpdated,
            self::EventDeleted => 'event',

            self::AttachmentUploadedToTask => 'attachment',
            self::MessageSent => 'message',
        };
    }

    /**
     * Color semantico del item. Se traduce a una clase Tailwind
     * en el partial. Set limitado a los tonos del design system.
     *
     * @return string
     */
    public function tone(): string
    {
        return match ($this) {
            self::TaskCreated => 'blue',
            self::TaskCompleted => 'green',
            self::TaskReopened => 'amber',
            self::TaskMoved => 'blue',
            self::TaskUpdated => 'gray',
            self::TaskDeleted => 'red',

            self::DocumentCreated => 'blue',
            self::DocumentUpdated => 'gray',
            self::DocumentPublished => 'green',
            self::DocumentDeleted => 'red',

            self::ProjectCreated => 'blue',
            self::StatusChanged => 'amber',
            self::ProjectArchived => 'gray',
            self::ProjectUnarchived => 'green',
            self::TemplateApplied => 'purple',

            self::MemberAdded => 'green',
            self::MemberRemoved => 'red',

            self::EventCreated => 'blue',
            self::EventUpdated => 'amber',
            self::EventDeleted => 'red',

            self::AttachmentUploadedToTask => 'gray',
            self::MessageSent => 'blue',
        };
    }

    /**
     * Categoria logica del evento para los chips del filtro.
     *
     * Hay 23 tipos pero solo 6 categorias: asi el filtro del feed
     * se renderiza con un numero manejable de chips. La traduccion
     * tipo -> categoria vive aqui, no en el partial, para que un
     * cambio de agrupacion se haga en un solo sitio.
     *
     * @return string
     */
    public function category(): string
    {
        return match ($this) {
            self::TaskCreated,
            self::TaskCompleted,
            self::TaskReopened,
            self::TaskMoved,
            self::TaskUpdated,
            self::TaskDeleted,
            self::AttachmentUploadedToTask => 'tasks',

            self::DocumentCreated,
            self::DocumentUpdated,
            self::DocumentPublished,
            self::DocumentDeleted => 'documents',

            self::EventCreated,
            self::EventUpdated,
            self::EventDeleted => 'events',

            self::MessageSent => 'messages',

            self::ProjectCreated,
            self::StatusChanged,
            self::ProjectArchived,
            self::ProjectUnarchived,
            self::TemplateApplied => 'project',

            self::MemberAdded,
            self::MemberRemoved => 'members',
        };
    }

    /**
     * Determina si el caso es visible para clientes del portal.
     *
     * El "set conservador" del PRD: solo lo que el cliente ya ve
     * en otras vistas del proyecto. La auditoria interna
     * (borrados, miembros, plantillas, cambios de tareas no
     * "heroicos") queda admin-only.
     *
     * Importante: este metodo decide la "macro-categoria" (publico
     * o no). Para `document_*` la decision fina se hace en el
     * servicio combinando `isPublic()` con la `visibility` del
     * documento persistida en `properties`. Aqui devolvemos el
     * "optimismo" (consideramos que un `document_*` puede ser
     * publico) y el servicio se encarga del filtro fino.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return match ($this) {
            self::TaskCreated,
            self::TaskCompleted,
            self::TaskReopened,
            self::TaskMoved,
            self::MessageSent,
            self::StatusChanged,
            self::ProjectArchived,
            self::ProjectUnarchived,
            self::EventCreated,
            self::EventUpdated,
            self::EventDeleted => true,

            // Los `document_*` y `attachment_*` se filtran a nivel
            // de servicio usando `properties.visibility`. Aqui
            // optamos por marcarlos como "potencialmente publicos"
            // para que el scope `public()` los incluya y el
            // servicio aplique el filtro fino.
            self::DocumentCreated,
            self::DocumentUpdated,
            self::DocumentPublished,
            self::DocumentDeleted,
            self::AttachmentUploadedToTask => true,

            // Auditoria interna: solo admin.
            self::TaskUpdated,
            self::TaskDeleted,
            self::MemberAdded,
            self::MemberRemoved,
            self::ProjectCreated,
            self::TemplateApplied => false,
        };
    }

    /**
     * Mapa estatico de categorias a etiquetas humanas en castellano.
     *
     * Pensado para alimentar el `select` / chips del filtro. La
     * clave `'all'` representa el filtro neutro ("Todas"). Las
     * demas claves coinciden con `category()`.
     *
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            'all' => 'Todas',
            'tasks' => 'Tareas',
            'documents' => 'Documentos',
            'events' => 'Eventos',
            'messages' => 'Mensajes',
            'project' => 'Proyecto',
            'members' => 'Miembros',
        ];
    }
}
