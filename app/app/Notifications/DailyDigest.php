<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Resumen diario de actividad de un usuario.
 *
 * La dispara el comando `notifications:daily-digest` (programado
 * para correr cada mañana) y solo se envia por email: el in-app
 * no aporta nada porque el usuario no tiene que "actuar" sobre
 * un resumen.
 *
 * El payload del email incluye:
 * - Lista de proyectos activos del usuario con su estado.
 * - Tareas pendientes asignadas, agrupadas por proyecto.
 * - Proximos eventos del calendario (7 dias) en los que el
 *   usuario es asistente.
 * - Mensajes no leidos en chats (para que sepa si tiene que
 *   entrar a la app).
 *
 * El calculo de las cuatro listas se hace en el comando y se
 * pasa a la notificacion ya materializada, para que el render
 * del email no tenga que tocar la BD.
 */
class DailyDigest extends Notification
{
    use Queueable;

    /**
     * Crea el resumen con los datos ya calculados. Cada parametro
     * es una coleccion para que el email se renderice sin consultas
     * adicionales.
     *
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, Task>  $pendingTasks
     * @param  Collection<int, object>  $upcomingEvents
     * @param  int  $unreadMessages
     */
    public function __construct(
        public User $user,
        public Collection $projects,
        public Collection $pendingTasks,
        public Collection $upcomingEvents,
        public int $unreadMessages,
    ) {
    }

    /**
     * Solo email. El in-app no se ofrece para el digest.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Email con el resumen. Se construye con bloques "intro",
     * "proyectos", "tareas", "eventos" y "mensajes", en orden
     * descendente de urgencia: primero lo que requiere accion.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Tu resumen diario de ClientFlow')
            ->greeting(sprintf('Buenos dias, %s!', $this->user->name))
            ->line('Esto es lo que ha pasado en tus proyectos en las ultimas 24 horas:');

        // Bloque 1: mensajes no leidos (mayor urgencia).
        if ($this->unreadMessages > 0) {
            $mail->line(sprintf(
                'Tienes %d mensaje(s) sin leer en tus chats.',
                $this->unreadMessages,
            ));
        }

        // Bloque 2: tareas pendientes (accionables).
        if ($this->pendingTasks->isNotEmpty()) {
            $mail->line('Tareas pendientes asignadas a ti:');
            foreach ($this->pendingTasks->take(10) as $task) {
                $due = $task->due_date ? ' (vence ' . $task->due_date->format('d/m/Y') . ')' : '';
                $mail->line(sprintf('- %s%s', $task->title, $due));
            }
            if ($this->pendingTasks->count() > 10) {
                $mail->line(sprintf('... y %d mas.', $this->pendingTasks->count() - 10));
            }
        }

        // Bloque 3: proximos eventos.
        if ($this->upcomingEvents->isNotEmpty()) {
            $mail->line('Tus proximos eventos:');
            foreach ($this->upcomingEvents->take(5) as $event) {
                $mail->line(sprintf(
                    '- %s (%s)',
                    $event->title,
                    $event->starts_at->format('d/m/Y H:i'),
                ));
            }
        }

        // Bloque 4: proyectos activos (contexto).
        if ($this->projects->isNotEmpty()) {
            $mail->line('Proyectos en los que participas:');
            foreach ($this->projects->take(5) as $project) {
                $mail->line(sprintf('- %s (%s)', $project->name, $project->status?->label() ?? 'activo'));
            }
        }

        $mail->action('Abrir ClientFlow', route('admin.dashboard'))
            ->line('Si no quieres seguir recibiendo este resumen, desactivalo en tus preferencias.');

        return $mail;
    }
}
