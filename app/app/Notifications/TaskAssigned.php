<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificacion de tarea asignada a un usuario.
 *
 * Se dispara cuando un administrador asigna (o reasigna) una tarea
 * a un miembro del proyecto. La logica de "solo si cambia el
 * assignee" vive en el `TaskController`, no aqui: la notificacion
 * es agnosta a la causa, simplemente lleva los datos.
 *
 * Canales:
 * - `database`: aparece en la campana del header con el titulo de
 *   la tarea y un enlace al kanban del proyecto.
 * - `mail`: email corto con los datos esenciales y un boton al
 *   tablero.
 *
 * El opt-out por canal lo gestiona el `NotificationDispatcher`,
 * que consulta `User::preferenceFor(TaskAssigned)` antes de llamar
 * a `send()`. Esta clase no deberia ser llamada directamente.
 */
class TaskAssigned extends Notification
{
    use Queueable;

    /**
     * Crea la notificacion con la tarea, el proyecto y el actor
     * que la asigno. La notificacion lleva los datos minimos para
     * que la campana y el email se rendericen sin recargar el
     * modelo desde BD.
     */
    public function __construct(
        public Task $task,
        public Project $project,
        public User $assigner,
    ) {
    }

    /**
     * Canales de envio. Por defecto ambos; el dispatcher podria
     * sobreescribirlos si la preferencia del usuario lo desactiva,
     * pero por simplicidad declaramos ambos y dejamos que el
     * dispatcher decida a quien llamar.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Payload persistido en la tabla `notifications`. El campo
     * `data` se almacena como JSON; la campana del header lo lee
     * para pintar titulo, body y enlace.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'priority' => $this->task->priority?->value,
            'priority_label' => $this->task->priority?->label(),
            'due_date' => $this->task->due_date?->toDateString(),
            'assigner_name' => $this->assigner->name,
            'url' => route('admin.projects.board', $this->project),
        ];
    }

    /**
     * Email corto: el admin te ha puesto una tarea nueva.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $url = route('admin.projects.board', $this->project);
        $due = $this->task->due_date
            ? 'Fecha limite: '.$this->task->due_date->format('d/m/Y').'.'
            : 'Sin fecha limite.';

        return (new MailMessage)
            ->subject(sprintf('Nueva tarea asignada: %s', $this->task->title))
            ->greeting('Hola!')
            ->line(sprintf(
                '%s te ha asignado la tarea "%s" en el proyecto "%s".',
                $this->assigner->name,
                $this->task->title,
                $this->project->name,
            ))
            ->line(sprintf('Prioridad: %s.', $this->task->priority?->label() ?? 'media'))
            ->line($due)
            ->action('Ir al tablero', $url)
            ->line('Puedes gestionar tus preferencias de notificacion desde tu cuenta.');
    }
}
