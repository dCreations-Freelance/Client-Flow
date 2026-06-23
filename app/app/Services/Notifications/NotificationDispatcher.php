<?php

namespace App\Services\Notifications;

use App\Enums\NotificationEvent;
use App\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Despachador central de notificaciones.
 *
 * Es el UNICO punto por el que el resto de la app envia
 * notificaciones a un usuario. Centralizarlo permite:
 * 1. Consultar las preferencias (`notification_preferences`) y
 *    respetar el opt-out por canal.
 * 2. Recortar las clases de notificacion para que declaren
 *    siempre todos los canales en `via()`: el dispatcher filtra
 *    despues.
 * 3. Tener un solo sitio donde loguear/enviar a un bus externo/
 *    Pusher/etc. si en una fase futura se anade integracion.
 *
 * No se instancia como servicio: es una clase con metodos
 * estaticos para poder llamarla desde cualquier sitio sin
 * inyeccion de dependencias. La cobertura de tests se hace con
 * `Notification::fake()` igual que en el resto del proyecto.
 *
 * Uso tipico:
 * ```php
 * NotificationDispatcher::dispatch(
 *     user: $assignee,
 *     notification: new TaskAssigned($task, $project, $actor),
 *     event: NotificationEvent::TaskAssigned,
 * );
 * ```
 */
class NotificationDispatcher
{
    /**
     * Despacha una notificacion al usuario, respetando sus
     * preferencias por canal. Si el usuario ha deshabilitado
     * ambos canales, no se envia nada (ni in-app ni email).
     *
     * @param  User  $user  destinatario (notifiable)
     * @param  Notification  $notification  clase de notificacion a enviar
     * @param  NotificationEvent  $event  evento al que se asocia, para consultar preferencias
     * @param  User|null  $forceInApp  si se pasa, se ignora la preferencia in-app del usuario
     *                                  y se envia por in-app. Pensado para casos donde la
     *                                  notificacion es tan importante que no se puede
     *                                  silenciar (sin uso por ahora; se mantiene por si
     *                                  el product architect lo pide).
     * @return bool  true si la notificacion se intento enviar por al menos un canal
     */
    public static function dispatch(
        User $user,
        Notification $notification,
        NotificationEvent $event,
        ?bool $forceInApp = null,
    ): bool {
        $preference = $user->preferenceFor($event);

        // Si el usuario ha deshabilitado los dos canales, no hacemos
        // nada. Logueamos a nivel debug para que un test/manual
        // pueda confirmar que el opt-out funciona.
        if ($preference->isFullyDisabled()) {
            Log::debug('Notification suprimida por opt-out del usuario.', [
                'user_id' => $user->id,
                'event' => $event->value,
            ]);

            return false;
        }

        $channels = [];

        $inAppEnabled = $forceInApp ?? $preference->isInAppEnabled();
        if ($inAppEnabled) {
            $channels[] = 'database';
        }

        if ($preference->isEmailEnabled()) {
            $channels[] = 'mail';
        }

        if ($channels === []) {
            return false;
        }

        // Usamos el helper `sendNow` con el array de canales
        // calculado para evitar que `via()` se ejecute y devuelva
        // canales que el usuario ha deshabilitado. Asi no se
        // generan filas en la tabla `notifications` para un
        // usuario que ha apagado la campana.
        NotificationFacade::sendNow(
            $user,
            $notification,
            $channels,
        );

        return true;
    }

    /**
     * Variante que envia a varios usuarios a la vez. Itera sobre
     * la coleccion y aplica el opt-out individualmente. Devuelve
     * cuantos usuarios recibieron la notificacion por al menos un
     * canal (util para tests y para loguear volumen en el
     * daily-digest).
     *
     * @param  \Illuminate\Support\Collection<int, User>|array<int, User>  $users
     * @param  Notification  $notification
     * @param  NotificationEvent  $event
     * @return int
     */
    public static function dispatchToMany(
        iterable $users,
        Notification $notification,
        NotificationEvent $event,
    ): int {
        $sent = 0;
        foreach ($users as $user) {
            if (self::dispatch($user, $notification, $event)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Variante para notificaciones que NO tienen un destinatario
     * "natural" (por ejemplo, el daily-digest se envia por email
     * sin pasar por la campana in-app). Construye un notifiable
     * anonimo a partir de un email y un nombre.
     *
     * @param  string  $email
     * @param  string  $name
     * @param  Notification  $notification
     */
    public static function dispatchToAddress(
        string $email,
        string $name,
        Notification $notification,
    ): void {
        $notifiable = (new AnonymousNotifiable)
            ->route('mail', [$email => $name]);

        NotificationFacade::sendNow($notifiable, $notification);
    }
}
