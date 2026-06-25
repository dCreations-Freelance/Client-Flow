<?php

namespace App\Services\Activity;

use App\Enums\ActivityType;
use App\Enums\DocumentVisibility;
use App\Enums\ProjectStatus;
use App\Models\ActivityLog;
use App\Models\BoardColumn;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectMessage;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio que registra la actividad del proyecto.
 *
 * Este servicio es el UNICO punto de escritura del feed de
 * actividad. Se invoca desde controladores y componentes
 * Livewire cada vez que se genera un "suceso" relevante del
 * proyecto (tarea creada, documento publicado, etc.).
 *
 * Patron de doble persistencia:
 *
 *  1. Inserta una fila en `activity_log` con un `description`
 *     legible y un `type` del enum `ActivityType`. El feed
 *     lee de esta tabla.
 *  2. Delega en `ProjectActivityLogger` (existente) para que
 *     cree el `ProjectMessage::system` correspondiente en el
 *     chat del proyecto. Asi el chat sigue mostrando todos
 *     los eventos como hasta ahora y no hay regresiones.
 *
 * Centralizar ambas escrituras aqui tiene dos beneficios:
 *
 *  - Los call sites (controladores, Livewire) solo cambian
 *    una linea: de `app(ProjectActivityLogger::class)->X()`
 *    a `app(ActivityLogger::class)->X(...)`. El resto de la
 *    firma se mantiene compatible.
 *  - Si en una fase futura queremos migrar a Events/Listeners,
 *    el cambio es trivial: este servicio se convierte en
 *    listener y los call sites disparan `event(new TaskCreated($task))`.
 *    La doble persistencia se mantiene.
 *
 * Cada metodo publico hace su propia doble escritura: el
 * `record()` interno persiste a `activity_log` y devuelve la
 * entrada creada; la delegacion al chat se hace dentro de cada
 * metodo con los parametros exactos que espera el
 * `ProjectActivityLogger` (que a veces no coinciden con los
 * nuestros, por ejemplo `taskMoved` recibe `BoardColumn` y no
 * solo el slug).
 *
 * Algunos metodos (`documentCreated`, `taskUpdated`,
 * `projectCreated`, `templateApplied`, `memberAdded/Removed`)
 * no delegan al chat porque su evento no tiene un system
 * message asociado. La razon esta documentada en cada caso.
 */
class ActivityLogger
{
    /**
     * Servicio colaborador: mantiene la logica del chat.
     * Se inyecta en el constructor para que sea mockeable en
     * tests unitarios, aunque en produccion Laravel lo resuelve
     * por el container gracias al autowire.
     */
    public function __construct(
        private readonly ProjectActivityLogger $chatLogger,
    ) {}

    /**
     * Punto unico de escritura a `activity_log`. Crea la fila
     * con los datos recibidos y devuelve la entrada para que
     * el metodo publico pueda usarla (por ejemplo, para tests).
     *
     * La delegacion al chat NO se hace aqui: cada metodo publico
     * la hace con los parametros exactos que necesita, porque
     * la API de `ProjectActivityLogger` no es 1:1 con la nuestra.
     *
     * @param  array<string, mixed>  $properties
     */
    private function record(
        Project $project,
        ActivityType $type,
        string $description,
        ?User $actor,
        array $properties,
        mixed $subject,
    ): ActivityLog {
        return ActivityLog::create([
            'project_id' => $project->id,
            'organization_id' => $project->organization_id,
            'user_id' => $actor?->id ?? Auth::id(),
            'type' => $type,
            'description' => $description,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties !== [] ? $properties : null,
        ]);
    }

    // -----------------------------------------------------------------
    // Tareas
    // -----------------------------------------------------------------

    /**
     * Registra la creacion de una tarea en el proyecto.
     *
     * Delega en el chat (`taskCreated` del ProjectActivityLogger)
     * que ya genera el system message con el formato
     * "Nueva tarea creada: X".
     */
    public function taskCreated(Project $project, Task $task, User $actor): ActivityLog
    {
        $entry = $this->record(
            $project,
            ActivityType::TaskCreated,
            sprintf('%s creo la tarea "%s".', $actor->name, $task->title),
            $actor,
            [
                'type' => $task->type?->value,
                'priority' => $task->priority?->value,
            ],
            $task,
        );

        // Chat: delegamos al ProjectActivityLogger para mantener
        // el system message que el chat ya mostraba antes de
        // esta fase. El formato del chat lo controla ese
        // servicio, no nosotros.
        $this->chatLogger->taskCreated($project, $task);

        return $entry;
    }

    /**
     * Registra la finalizacion de una tarea.
     */
    public function taskCompleted(Project $project, Task $task, User $actor): ActivityLog
    {
        $entry = $this->record(
            $project,
            ActivityType::TaskCompleted,
            sprintf('%s completo la tarea "%s".', $actor->name, $task->title),
            $actor,
            [],
            $task,
        );

        $this->chatLogger->taskCompleted($project, $task, $actor);

        return $entry;
    }

    /**
     * Registra la re-apertura de una tarea completada.
     */
    public function taskReopened(Project $project, Task $task, User $actor): ActivityLog
    {
        $entry = $this->record(
            $project,
            ActivityType::TaskReopened,
            sprintf('%s re-abrio la tarea "%s".', $actor->name, $task->title),
            $actor,
            [],
            $task,
        );

        $this->chatLogger->taskReopened($project, $task, $actor);

        return $entry;
    }

    /**
     * Registra el movimiento de una tarea a otra columna.
     *
     * `$oldColumn` puede ser null si la tarea no estaba en
     * ninguna columna (caso degenerado, pero defendemos).
     * El `properties` guarda el slug de origen y destino para
     * que el feed pueda pintar un texto contextual o un
     * enlace futuro.
     */
    public function taskMoved(
        Project $project,
        Task $task,
        BoardColumn $newColumn,
        ?BoardColumn $oldColumn,
        User $actor,
    ): ActivityLog {
        $entry = $this->record(
            $project,
            ActivityType::TaskMoved,
            sprintf(
                '%s movio "%s" a la columna "%s".',
                $actor->name,
                $task->title,
                $newColumn->name,
            ),
            $actor,
            [
                'from_column' => $oldColumn?->slug,
                'to_column' => $newColumn->slug,
            ],
            $task,
        );

        $this->chatLogger->taskMoved($project, $task, $newColumn, $actor);

        return $entry;
    }

    /**
     * Registra cambios menores en una tarea (descripcion,
     * prioridad, fecha limite, etc.) que no generan un system
     * message en el chat.
     *
     * Devuelve `null` si `$changes` esta vacio: asi el
     * llamador puede hacer `if ($entry = $logger->taskUpdated(...))`
     * sin tener que comprobar nada.
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function taskUpdated(
        Project $project,
        Task $task,
        User $actor,
        array $changes,
    ): ?ActivityLog {
        if ($changes === []) {
            return null;
        }

        $labels = [
            'title' => 'el titulo',
            'description' => 'la descripcion',
            'priority' => 'la prioridad',
            'type' => 'el tipo',
            'due_date' => 'la fecha limite',
            'assignee_id' => 'el asignado',
        ];

        $parts = [];
        foreach ($changes as $field => $diff) {
            $parts[] = $labels[$field] ?? $field;
        }

        $description = sprintf(
            '%s actualizo %s de la tarea "%s".',
            $actor->name,
            $this->humanList($parts),
            $task->title,
        );

        return $this->record(
            $project,
            ActivityType::TaskUpdated,
            $description,
            $actor,
            ['changes' => $changes],
            $task,
        );
    }

    /**
     * Registra la eliminacion de una tarea. Se recibe el titulo
     * como string porque la tarea ya no existe cuando se
     * ejecuta el evento.
     */
    public function taskDeleted(Project $project, string $taskTitle, User $actor): ActivityLog
    {
        return $this->record(
            $project,
            ActivityType::TaskDeleted,
            sprintf('%s elimino la tarea "%s".', $actor->name, $taskTitle),
            $actor,
            ['title' => $taskTitle],
            null,
        );
    }

    // -----------------------------------------------------------------
    // Documentos
    // -----------------------------------------------------------------

    /**
     * Registra la creacion de un documento (publico o privado).
     * El `properties.visibility` decide si el portal lo ve o no
     * (filtro en el scope `public`).
     *
     * No delega al chat: el chat solo notifica sobre documentos
     * que pasan a ser publicos (regla del `ProjectActivityLogger`).
     * Para documentos privados el chat permanece en silencio.
     */
    public function documentCreated(
        Project $project,
        ProjectDocument $document,
        User $actor,
    ): ActivityLog {
        return $this->record(
            $project,
            ActivityType::DocumentCreated,
            sprintf(
                '%s creo el documento "%s" (%s).',
                $actor->name,
                $document->title,
                $document->visibility?->label() ?? 'documento',
            ),
            $actor,
            ['visibility' => $document->visibility?->value],
            $document,
        );
    }

    /**
     * Registra la actualizacion de un documento. No delega al
     * chat: las ediciones menores no merecen un system message.
     */
    public function documentUpdated(
        Project $project,
        ProjectDocument $document,
        User $actor,
    ): ActivityLog {
        return $this->record(
            $project,
            ActivityType::DocumentUpdated,
            sprintf('%s actualizo el documento "%s".', $actor->name, $document->title),
            $actor,
            ['visibility' => $document->visibility?->value],
            $document,
        );
    }

    /**
     * Registra la transicion de un documento a visibilidad
     * publica. A diferencia de `documentCreated`, este SI
     * delega al chat (es el caso que ya cubria el
     * `ProjectActivityLogger::documentPublished`).
     */
    public function documentPublished(
        Project $project,
        ProjectDocument $document,
        User $actor,
    ): ActivityLog {
        $entry = $this->record(
            $project,
            ActivityType::DocumentPublished,
            sprintf('%s publico el documento "%s".', $actor->name, $document->title),
            $actor,
            ['visibility' => DocumentVisibility::Public->value],
            $document,
        );

        // El chat solo emite system message si el documento es
        // publico. El `ProjectActivityLogger::documentPublished`
        // ya implementa esta defensa.
        $this->chatLogger->documentPublished($project, $document, $actor);

        return $entry;
    }

    /**
     * Registra la eliminacion de un documento.
     */
    public function documentDeleted(
        Project $project,
        string $documentTitle,
        User $actor,
        ?DocumentVisibility $visibility = null,
    ): ActivityLog {
        return $this->record(
            $project,
            ActivityType::DocumentDeleted,
            sprintf('%s elimino el documento "%s".', $actor->name, $documentTitle),
            $actor,
            array_filter([
                'visibility' => $visibility?->value,
                'title' => $documentTitle,
            ]),
            null,
        );
    }

    // -----------------------------------------------------------------
    // Proyecto
    // -----------------------------------------------------------------

    /**
     * Registra la creacion de un proyecto. Evento privado
     * (admin-only en el feed del portal).
     *
     * No delega al chat: la creacion de un proyecto no genera
     * un system message historico (seria ruido).
     */
    public function projectCreated(Project $project, User $actor): ActivityLog
    {
        return $this->record(
            $project,
            ActivityType::ProjectCreated,
            sprintf('%s creo el proyecto "%s".', $actor->name, $project->name),
            $actor,
            [],
            $project,
        );
    }

    /**
     * Registra un cambio de estado del proyecto (planning ->
     * in_progress, etc). Evento publico: el cliente quiere
     * saber si el proyecto arranca o se pausa.
     *
     * No delega al chat: no existe un system message "cambio
     * de estado" en el chat actualmente, y anadirlo en esta
     * fase seria un cambio de scope.
     */
    public function statusChanged(
        Project $project,
        ProjectStatus $oldStatus,
        ProjectStatus $newStatus,
        User $actor,
    ): ActivityLog {
        return $this->record(
            $project,
            ActivityType::StatusChanged,
            sprintf(
                '%s cambio el estado del proyecto de "%s" a "%s".',
                $actor->name,
                $oldStatus->label(),
                $newStatus->label(),
            ),
            $actor,
            ['from' => $oldStatus->value, 'to' => $newStatus->value],
            $project,
        );
    }

    /**
     * Registra el archivado del proyecto.
     */
    public function projectArchived(Project $project, User $actor): ActivityLog
    {
        $entry = $this->record(
            $project,
            ActivityType::ProjectArchived,
            sprintf('%s archivo el proyecto.', $actor->name),
            $actor,
            [],
            $project,
        );

        $this->chatLogger->projectArchived($project, $actor);

        return $entry;
    }

    /**
     * Registra la desarchivacion del proyecto.
     */
    public function projectUnarchived(Project $project, User $actor): ActivityLog
    {
        $entry = $this->record(
            $project,
            ActivityType::ProjectUnarchived,
            sprintf('%s desarchivo el proyecto.', $actor->name),
            $actor,
            [],
            $project,
        );

        $this->chatLogger->projectUnarchived($project, $actor);

        return $entry;
    }

    /**
     * Registra la aplicacion de una plantilla al crear un
     * proyecto. Evento privado (auditoria interna).
     */
    public function templateApplied(
        Project $project,
        ProjectTemplate $template,
        User $actor,
    ): ActivityLog {
        return $this->record(
            $project,
            ActivityType::TemplateApplied,
            sprintf(
                '%s creo el proyecto desde la plantilla "%s".',
                $actor->name,
                $template->name,
            ),
            $actor,
            ['template_id' => $template->id],
            $project,
        );
    }

    // -----------------------------------------------------------------
    // Miembros
    // -----------------------------------------------------------------

    /**
     * Registra la adicion de un miembro al proyecto. Privado:
     * es un detalle interno de quien-trabaja-con-quien.
     */
    public function memberAdded(Project $project, User $member, User $actor): ActivityLog
    {
        return $this->record(
            $project,
            ActivityType::MemberAdded,
            sprintf('%s anadio a %s al proyecto.', $actor->name, $member->name),
            $actor,
            ['member_id' => $member->id],
            null,
        );
    }

    /**
     * Registra la eliminacion de un miembro del proyecto.
     */
    public function memberRemoved(Project $project, string $memberName, User $actor): ActivityLog
    {
        return $this->record(
            $project,
            ActivityType::MemberRemoved,
            sprintf('%s elimino a %s del proyecto.', $actor->name, $memberName),
            $actor,
            ['member_name' => $memberName],
            null,
        );
    }

    // -----------------------------------------------------------------
    // Calendario
    // -----------------------------------------------------------------

    /**
     * Registra la creacion de un evento en el calendario.
     */
    public function eventCreated(
        Project $project,
        CalendarEvent $event,
        User $actor,
    ): ActivityLog {
        $entry = $this->record(
            $project,
            ActivityType::EventCreated,
            sprintf(
                '%s creo el evento "%s" (%s) en el calendario.',
                $actor->name,
                $event->title,
                $event->type?->label() ?? 'evento',
            ),
            $actor,
            ['event_type' => $event->type?->value],
            $event,
        );

        $this->chatLogger->eventCreated($project, $event, $actor);

        return $entry;
    }

    /**
     * Registra la actualizacion de un evento.
     */
    public function eventUpdated(
        Project $project,
        CalendarEvent $event,
        User $actor,
    ): ActivityLog {
        $entry = $this->record(
            $project,
            ActivityType::EventUpdated,
            sprintf(
                '%s actualizo el evento "%s" del calendario.',
                $actor->name,
                $event->title,
            ),
            $actor,
            [],
            $event,
        );

        $this->chatLogger->eventUpdated($project, $event, $actor);

        return $entry;
    }

    /**
     * Registra la eliminacion de un evento. Recibe el titulo
     * como string porque el evento ya no existe.
     */
    public function eventDeleted(
        Project $project,
        string $eventTitle,
        User $actor,
    ): ActivityLog {
        $entry = $this->record(
            $project,
            ActivityType::EventDeleted,
            sprintf(
                '%s elimino el evento "%s" del calendario.',
                $actor->name,
                $eventTitle,
            ),
            $actor,
            ['title' => $eventTitle],
            null,
        );

        $this->chatLogger->eventDeleted($project, $eventTitle, $actor);

        return $entry;
    }

    // -----------------------------------------------------------------
    // Adjuntos y chat
    // -----------------------------------------------------------------

    /**
     * Registra la subida de uno o varios adjuntos a una tarea.
     */
    public function attachmentUploadedToTask(
        Project $project,
        Task $task,
        int $count,
        User $actor,
    ): ActivityLog {
        $entry = $this->record(
            $project,
            ActivityType::AttachmentUploadedToTask,
            sprintf(
                '%s subio %d adjunto%s a la tarea "%s".',
                $actor->name,
                $count,
                $count === 1 ? '' : 's',
                $task->title,
            ),
            $actor,
            ['count' => $count],
            $task,
        );

        $this->chatLogger->attachmentUploadedToTask($project, $task, $count, $actor);

        return $entry;
    }

    /**
     * Registra un mensaje humano en el chat del proyecto.
     * El feed muestra los mensajes para que el cliente tenga
     * un timeline unificado (no tenga que abrir el chat
     * ademas del feed).
     */
    public function messageSent(
        Project $project,
        ProjectMessage $message,
    ): ActivityLog {
        $author = $message->user;
        $preview = $this->previewFor($message->content);

        return $this->record(
            $project,
            ActivityType::MessageSent,
            sprintf(
                '%s: "%s"',
                $author?->name ?? 'Alguien',
                $preview,
            ),
            $author,
            ['message_id' => $message->id],
            $message,
        );
    }

    // -----------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------

    /**
     * Trunca el contenido de un mensaje a una preview corta
     * para el feed. Mismo patron que `NewProjectMessage::preview()`.
     */
    private function previewFor(string $content): string
    {
        $trimmed = trim($content);
        if (mb_strlen($trimmed) <= 80) {
            return $trimmed;
        }

        return mb_substr($trimmed, 0, 77).'...';
    }

    /**
     * Convierte una lista de etiquetas en una enumeracion en
     * castellano ("a, b y c"). Pensado para descripciones de
     * cambios donde varios campos se actualizan a la vez.
     *
     * @param  array<int, string>  $items
     */
    private function humanList(array $items): string
    {
        $items = array_values(array_filter($items));
        $count = count($items);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $items[0];
        }

        if ($count === 2) {
            return $items[0].' y '.$items[1];
        }

        $last = array_pop($items);

        return implode(', ', $items).' y '.$last;
    }
}
