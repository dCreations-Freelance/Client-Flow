<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificacion de tarea con deadline cercano.
 *
 * La dispara el comando `notifications:task-due-soon` cuando
 * detecta que una tarea asignada a un usuario tiene su `due_date`
 * en los proximos tres dias. El sello `last_due_notification_at`
 * en la tabla `tasks` impide que el mismo recordatorio se envie
 * dos veces seguidas.
 *
 * La notificacion NO lleva el `User` actor (no hay actor humano;
 * la dispara el sistema). El destinatario es el `assignee_id`
 * de la tarea.
 */
class TaskDueSoon extends Notification
{
    use Queueable;

    /**
     * Crea la notificacion con la tarea y su proyecto.
     */
    public function __construct(
        public Task $task,
        public Project $project,
    ) {
    }

    /**
     * Canales. Igual que `TaskAssigned`, el dispatcher decide a
     * quien llamar en funcion de las preferencias; declaramos
     * ambos como declaracion de intenciones.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Payload in-app: la campana muestra el titulo de la tarea y
     * cuantos dias quedan para la entrega.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        $daysLeft = (int) now()->startOfDay()->diffInDays($this->task->due_date, false);

        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'priority' => $this->task->priority?->value,
            'priority_label' => $this->task->priority?->label(),
            'due_date' => $this->task->due_date?->toDateString(),
            'days_left' => $daysLeft,
            'url' => route('admin.projects.board', $this->project),
        ];
    }

    /**
     * Email de recordatorio: la tarea tiene fecha limite en N dias.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $url = route('admin.projects.board', $this->project);
        $daysLeft = (int) now()->startOfDay()->diffInDays($this->task->due_date, false);
        $when = $daysLeft <= 0
            ? 'hoy'
            : ($daysLeft === 1 ? 'mañana' : sprintf('en %d dias', $daysLeft));

        return (new MailMessage)
            ->subject(sprintf('Recordatorio: "%s" vence %s', $this->task->title, $when))
            ->greeting('Hola!')
            ->line(sprintf(
                'La tarea "%s" del proyecto "%s" tiene su fecha limite %s.',
                $this->task->title,
                $this->project->name,
                $when,
            ))
            ->line(sprintf('Fecha exacta: %s.', $this->task->due_date->format('d/m/Y')))
            ->action('Ir al tablero', $url)
            ->line('Si ya esta terminada, marcala como completada para que no vuelva a avisarte.');
    }
}
