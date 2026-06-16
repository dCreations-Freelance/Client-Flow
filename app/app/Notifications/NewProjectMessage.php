<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificacion de nuevo mensaje en el chat de un proyecto.
 *
 * Se envia por dos canales:
 * - `database`: persiste una fila en la tabla `notifications` con
 *   el payload del mensaje. Es la base del badge in-app y de los
 *   contadores de "no leidos".
 * - `mail`: envia un email al destinatario con un preview del
 *   mensaje y un boton para ir al chat del proyecto.
 *
 * El debounce real (no enviar si ya se le envio en la ultima hora
 * para ese mismo proyecto) se difiere a la seccion "Transversal:
 * Notificaciones" del TODO. En esta fase se envia cada vez, lo
 * cual es aceptable en MVP porque el volumen de chats es bajo.
 */
class NewProjectMessage extends Notification
{
    use Queueable;

    /**
     * Crea la notificacion a partir del mensaje, el proyecto y el
     * emisor. La notificacion lleva todos los datos necesarios para
     * reconstruir el contexto sin tener que recargar el modelo.
     */
    public function __construct(
        public ProjectMessage $message,
        public Project $project,
        public User $sender,
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
     * Payload que se persiste en la tabla `notifications`. El campo
     * `data` se almacena como JSON con la informacion minima para
     * renderizar la notificacion in-app sin recargar el modelo.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'message_id' => $this->message->id,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'content_preview' => $this->preview(),
            'url' => $this->chatUrl(),
        ];
    }

    /**
     * Construye el email con un preview del mensaje y un boton al
     * chat del proyecto. El preview es texto plano (sin markdown)
     * para que cualquier cliente de correo lo muestre correctamente.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->chatUrl();

        return (new MailMessage)
            ->subject('Nuevo mensaje en '.$this->project->name)
            ->greeting('Hola!')
            ->line(sprintf(
                '%s ha enviado un mensaje en el proyecto "%s":',
                $this->sender->name,
                $this->project->name,
            ))
            ->line($this->preview())
            ->action('Ir al chat', $url)
            ->line('Puedes responder directamente desde la app.');
    }

    /**
     * Construye un preview de una linea del mensaje, escapando
     * caracteres problematicos y cortando a una longitud razonable.
     */
    private function preview(): string
    {
        $content = trim(strip_tags($this->message->content ?? ''));
        $content = preg_replace('/\s+/u', ' ', $content) ?? '';

        if (mb_strlen($content) > 160) {
            $content = rtrim(mb_substr($content, 0, 159)).'…';
        }

        return $content;
    }

    /**
     * Devuelve la URL al chat del proyecto segun el rol del
     * destinatario. No se puede saber el rol aqui, asi que se
     * prefiere la ruta admin (que existe) y se deja que el
     * middleware redirija al portal si el destinatario es cliente.
     *
     * En la practica la mayoria de los flujos acabaran en el chat
     * del portal del cliente; si la URL admin se rebota, la pagina
     * sigue siendo util como link.
     */
    private function chatUrl(): string
    {
        return route('admin.projects.chat', $this->project);
    }
}
