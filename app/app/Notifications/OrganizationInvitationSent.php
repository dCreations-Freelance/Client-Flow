<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificacion de invitacion a una organizacion.
 *
 * Se envia por el canal `mail`. En el MVP el mailer esta en modo
 * `log`, asi que el enlace acabara en `storage/logs/laravel.log`
 * y el admin podra copiarlo para probar la aceptacion.
 */
class OrganizationInvitationSent extends Notification
{
    use Queueable;

    /**
     * Crea la notificacion a partir de la invitacion y el token crudo
     * (el hash nunca sale de la BD).
     */
    public function __construct(
        public OrganizationInvitation $invitation,
        public string $rawToken,
    ) {
    }

    /**
     * Canales de envio. Solo email en MVP.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construye el email con el enlace de aceptacion. El enlace apunta
     * a la ruta publica `invitation.accept`.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $url = route('invitation.accept', ['token' => $this->rawToken]);

        return (new MailMessage)
            ->subject('Invitacion a '.$this->invitation->organization->name)
            ->greeting('Hola!')
            ->line('Has sido invitado a colaborar en '.$this->invitation->organization->name.'.')
            ->action('Aceptar invitacion', $url)
            ->line('Este enlace expira el '.$this->invitation->expires_at->format('d/m/Y H:i').'.');
    }
}
