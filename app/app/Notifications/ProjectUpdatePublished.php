<?php

namespace App\Notifications;

use App\Models\ProjectUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectUpdatePublished extends Notification
{
    use Queueable;

    public function __construct(private readonly ProjectUpdate $update) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->update->project;

        return (new MailMessage)
            ->subject('Nueva actualización en '.$project->name)
            ->greeting('Hola '.$project->client->name)
            ->line('Hay una nueva actualización en '.$project->name.'.')
            ->line($this->update->title)
            ->line($this->update->content)
            ->line('Puedes verla accediendo a tu portal ClientFlow.');
    }
}
