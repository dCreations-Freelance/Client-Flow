<?php

namespace App\Listeners;

use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;

/**
 * Siembra las preferencias por defecto cuando se crea un usuario.
 *
 * El listener se dispara en el evento `Registered` de Laravel
 * (que se lanza tanto en el registro publico como en la aceptacion
 * de una invitacion a una organizacion). Asi no hace falta tocar
 * `RegisteredUserController` ni `InvitationAcceptanceController`.
 *
 * `User::preferenceFor()` ya devuelve los defaults del enum si el
 * usuario no tiene fila, pero sembrar explicitamente las seis
 * filas aporta dos cosas:
 * 1. La pagina de preferencias puede listar y editar las seis
 *    casillas sin comprobar si estan o no persistidas.
 * 2. El admin puede ver en BD que preferencias tiene cada usuario
 *    sin tener que interpretar "lo que dice el enum".
 */
class CreateDefaultNotificationPreferences
{
    /**
     * Crea las seis filas de preferencias para el usuario recien
     * registrado. Se hace fila a fila (no `upsert`) para que el
     * codigo sea facil de leer y se pueda usar el enum como
     * referencia del nombre del evento.
     *
     * Si el usuario ya tiene alguna fila (por ejemplo si se
     * re-dispara el evento), `firstOrCreate` evita duplicados.
     */
    public function handle(Registered $event): void
    {
        /** @var Model $user */
        $user = $event->user;

        foreach (NotificationEvent::cases() as $event) {
            NotificationPreference::firstOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'event' => $event->value,
                ],
                [
                    'in_app' => $event->defaultInApp(),
                    'email' => $event->defaultEmail(),
                ],
            );
        }
    }
}
