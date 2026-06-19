<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Notificacion de invitacion a un evento de calendario.
 *
 * Se envia por dos canales:
 * - `database`: persiste una fila en la tabla `notifications` con
 *   el payload del evento. Es la base del badge in-app futuro y
 *   de los contadores de no leidos.
 * - `mail`: envia un email con los datos esenciales del evento
 *   (titulo, fecha formateada, tipo) y un boton para ir al
 *   calendario del proyecto.
 *
 * En MVP se envia una sola vez al crear el evento (a todos los
 * attendees que no son el emisor) y cada vez que se re-edita si
 * la lista de asistentes cambia. No hay recordatorios previos
 * (24h antes) por la decision de no introducir scheduler en el
 * MVP; eso queda como nota para una fase futura.
 */
class CalendarEventInvitation extends Notification
{
    use Queueable;

    /**
     * Crea la notificacion a partir del evento, el proyecto y el
     * emisor. La notificacion lleva todos los datos necesarios
     * para reconstruir el contexto sin tener que recargar el
     * modelo.
     */
    public function __construct(
        public CalendarEvent $event,
        public Project $project,
        public User $creator,
    ) {
    }

    /**
     * Canales de envio.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Payload que se persiste en la tabla `notifications`. El
     * campo `data` se almacena como JSON con la informacion
     * minima para renderizar la notificacion in-app sin
     * recargar el modelo.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_type' => $this->event->type?->value,
            'event_type_label' => $this->event->type?->label(),
            'starts_at' => $this->event->starts_at?->toIso8601String(),
            'starts_at_formatted' => $this->formattedStart(),
            'is_all_day' => (bool) $this->event->is_all_day,
            'creator_name' => $this->creator->name,
            'url' => $this->calendarUrl(),
        ];
    }

    /**
     * Construye el email con los datos del evento y un boton al
     * calendario del proyecto.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->calendarUrl();

        $typeLabel = $this->event->type?->label() ?? 'evento';
        $when = $this->formattedStart();

        return (new MailMessage)
            ->subject(sprintf('Nuevo evento en %s: %s', $this->project->name, $this->event->title))
            ->greeting('Hola!')
            ->line(sprintf(
                '%s te ha invitado a un evento en el proyecto "%s":',
                $this->creator->name,
                $this->project->name,
            ))
            ->line(sprintf('Tipo: %s', $typeLabel))
            ->line(sprintf('Cuando: %s', $when))
            ->action('Ir al calendario', $url)
            ->line('Puedes ver todos los detalles del evento desde la app.');
    }

    /**
     * Fecha formateada en castellano para mostrar en la UI in-app
     * y en el email. La salida incluye dia de la semana, fecha y
     * hora (o solo fecha si es all-day).
     */
    private function formattedStart(): string
    {
        $start = $this->event->starts_at;

        if ($start === null) {
            return '';
        }

        if ($this->event->is_all_day) {
            return Carbon::parse($start)
                ->locale('es')
                ->translatedFormat('D d \d\e M, Y');
        }

        return Carbon::parse($start)
            ->locale('es')
            ->translatedFormat('D d \d\e M, Y H:i');
    }

    /**
     * Devuelve la URL al calendario del proyecto. Se prefiere la
     * ruta admin porque existe siempre; si el destinatario es
     * cliente, el middleware le redirige a la ruta equivalente
     * del portal.
     *
     * @return string
     */
    private function calendarUrl(): string
    {
        return route('admin.projects.calendar', $this->project);
    }
}
