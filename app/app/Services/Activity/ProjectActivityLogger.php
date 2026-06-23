<?php

namespace App\Services\Activity;

use App\Enums\DocumentVisibility;
use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\BoardColumn;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectMessage;
use App\Models\Task;
use App\Models\User;

/**
 * Centraliza la generacion de mensajes automaticos en el chat de
 * un proyecto.
 *
 * Centralizar el formato aqui tiene dos objetivos:
 * 1. Evitar strings duplicados en los controladores. Si queremos
 *    cambiar el formato ("Nueva tarea creada: X" -> "Tarea X creada")
 *    se cambia en un solo sitio.
 * 2. Permitir migrar a Events/Listeners en una fase futura sin
 *    reescribir las llamadas: el llamador solo invoca el metodo del
 *    servicio, no le importa como se persiste.
 *
 * Todos los mensajes se crean con `user_id = null` (los system
 * messages no tienen autor humano) y `type = system`.
 */
class ProjectActivityLogger
{
    /**
     * Registra la creacion de una tarea en el proyecto.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Task  $task
     * @return \App\Models\ProjectMessage
     */
    public function taskCreated(Project $project, Task $task): ProjectMessage
    {
        return $this->log(
            $project,
            sprintf(
                'Nueva tarea creada: "%s" (%s, prioridad %s).',
                $task->title,
                $task->type?->label() ?? TaskType::Task->label(),
                $task->priority?->label() ?? TaskPriority::Medium->label(),
            ),
        );
    }

    /**
     * Registra la finalizacion de una tarea.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Task  $task
     * @param  \App\Models\User  $actor
     * @return \App\Models\ProjectMessage
     */
    public function taskCompleted(Project $project, Task $task, User $actor): ProjectMessage
    {
        return $this->log(
            $project,
            sprintf(
                '%s completo la tarea "%s".',
                $actor->name,
                $task->title,
            ),
        );
    }

    /**
     * Registra la re-apertura de una tarea.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Task  $task
     * @param  \App\Models\User  $actor
     * @return \App\Models\ProjectMessage
     */
    public function taskReopened(Project $project, Task $task, User $actor): ProjectMessage
    {
        return $this->log(
            $project,
            sprintf(
                '%s re-abrio la tarea "%s".',
                $actor->name,
                $task->title,
            ),
        );
    }

    /**
     * Registra el movimiento de una tarea a otra columna.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Task  $task
     * @param  \App\Models\BoardColumn  $newColumn
     * @param  \App\Models\User  $actor
     * @return \App\Models\ProjectMessage
     */
    public function taskMoved(Project $project, Task $task, BoardColumn $newColumn, User $actor): ProjectMessage
    {
        return $this->log(
            $project,
            sprintf(
                '%s movio "%s" a la columna "%s".',
                $actor->name,
                $task->title,
                $newColumn->name,
            ),
        );
    }

    /**
     * Registra el archivado del proyecto.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\User  $actor
     * @return \App\Models\ProjectMessage
     */
    public function projectArchived(Project $project, User $actor): ProjectMessage
    {
        return $this->log(
            $project,
            sprintf('%s archivo el proyecto.', $actor->name),
        );
    }

    /**
     * Registra la desarchivacion del proyecto.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\User  $actor
     * @return \App\Models\ProjectMessage
     */
    public function projectUnarchived(Project $project, User $actor): ProjectMessage
    {
        return $this->log(
            $project,
            sprintf('%s desarchivo el proyecto.', $actor->name),
        );
    }

    /**
     * Registra la publicacion de un documento (visibilidad = public).
     * Si el documento es privado no se registra nada: el chat no
     * es el sitio para notificar sobre documentos privados.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectDocument  $document
     * @param  \App\Models\User  $actor
     * @return \App\Models\ProjectMessage|null
     */
    public function documentPublished(Project $project, ProjectDocument $document, User $actor): ?ProjectMessage
    {
        if (! $document->visibility instanceof DocumentVisibility || ! $document->visibility->isPublic()) {
            return null;
        }

        return $this->log(
            $project,
            sprintf(
                '%s publico el documento "%s".',
                $actor->name,
                $document->title,
            ),
        );
    }

    /**
     * Persiste un mensaje de sistema en el chat del proyecto.
     * Centraliza la creacion para que todos los mensajes tengan la
     * misma forma (user_id null, type system, sin relaciones
     * adicionales).
     *
     * @param  \App\Models\Project  $project
     * @param  string  $content
     * @return \App\Models\ProjectMessage
     */
    private function log(Project $project, string $content): ProjectMessage
    {
        return ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => null,
            'content' => $content,
            'type' => \App\Enums\MessageType::System,
        ]);
    }

    /**
     * Registra la creacion de un evento en el calendario del
     * proyecto. El mensaje resultante es legible para humanos y
     * usa el nombre del actor (admin) que creo el evento.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\CalendarEvent  $event
     * @param  \App\Models\User  $actor
     * @return \App\Models\ProjectMessage
     */
    public function eventCreated(Project $project, CalendarEvent $event, User $actor): ProjectMessage
    {
        return $this->log(
            $project,
            sprintf(
                '%s creo el evento "%s" (%s) en el calendario.',
                $actor->name,
                $event->title,
                $event->type?->label() ?? 'evento',
            ),
        );
    }

    /**
     * Registra la actualizacion de un evento existente. Se
     * incluye el titulo para que el chat refleje inmediatamente
     * que evento cambio, incluso si el titulo se renombro.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\CalendarEvent  $event
     * @param  \App\Models\User  $actor
     * @return \App\Models\ProjectMessage
     */
    public function eventUpdated(Project $project, CalendarEvent $event, User $actor): ProjectMessage
    {
        return $this->log(
            $project,
            sprintf(
                '%s actualizo el evento "%s" del calendario.',
                $actor->name,
                $event->title,
            ),
        );
    }

    /**
     * Registra la eliminacion de un evento. Se recibe el titulo
     * como string (no el modelo) porque el evento ya no existe
     * cuando se genera el mensaje.
     *
     * @param  \App\Models\Project  $project
     * @param  string  $eventTitle
     * @param  \App\Models\User  $actor
     * @return \App\Models\ProjectMessage
     */
    public function eventDeleted(Project $project, string $eventTitle, User $actor): ProjectMessage
    {
        return $this->log(
            $project,
            sprintf(
                '%s elimino el evento "%s" del calendario.',
                $actor->name,
                $eventTitle,
            ),
        );
    }

    /**
     * Registra la subida de uno o varios adjuntos a una tarea.
     *
     * El chat del proyecto es el sitio natural para enterarse de
     * que hay archivos nuevos en una tarea, especialmente para
     * los miembros que no la tienen abierta en el kanban. Se
     * incluye el nombre del actor y el titulo de la tarea para
     * que el mensaje sea localizable al hacer scroll.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Task  $task
     * @param  int  $count  numero de adjuntos subidos en la operacion
     * @param  \App\Models\User  $actor
     * @return \App\Models\ProjectMessage
     */
    public function attachmentUploadedToTask(Project $project, Task $task, int $count, User $actor): ProjectMessage
    {
        return $this->log(
            $project,
            sprintf(
                '%s subio %d adjunto%s a la tarea "%s".',
                $actor->name,
                $count,
                $count === 1 ? '' : 's',
                $task->title,
            ),
        );
    }
}
