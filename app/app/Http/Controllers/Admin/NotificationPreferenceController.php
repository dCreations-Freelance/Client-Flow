<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNotificationPreferencesRequest;
use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Gestion de las preferencias de notificacion del administrador.
 *
 * El admin es un usuario mas: tiene su propia campana y sus
 * propias preferencias. Aunque controle toda la plataforma, no
 * tiene visibilidad de las preferencias de los clientes (la
 * policy `NotificationPreferencePolicy` lo impide), solo puede
 * tocar las suyas.
 */
class NotificationPreferenceController extends Controller
{
    /**
     * Muestra la pagina de preferencias del admin actual. Carga
     * las seis preferencias persistidas (o genera defaults en
     * memoria para las que falten) y las pasa a la vista con
     * el catalogo completo de eventos del enum.
     */
    public function index(): View
    {
        $user = auth()->user();
        $this->authorize('viewAny', NotificationPreference::class);

        $preferences = $this->loadPreferencesFor($user);

        return view('admin.notifications.preferences', [
            'preferences' => $preferences,
            'events' => NotificationEvent::cases(),
            'zone' => 'admin',
        ]);
    }

    /**
     * Persiste las preferencias del admin actual. El payload
     * validado contiene un array `preferences` con una entrada
     * por evento; hacemos `updateOrCreate` fila a fila para
     * respetar la regla de negocio "el admin es dueno de su
     * fila".
     */
    public function update(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();

        foreach ($request->validated()['preferences'] as $entry) {
            $event = NotificationEvent::from($entry['event']);

            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'event' => $event->value,
                ],
                [
                    'in_app' => (bool) ($entry['in_app'] ?? false),
                    'email' => (bool) ($entry['email'] ?? false),
                ],
            );
        }

        return back()->with('status', 'Preferencias actualizadas.');
    }

    /**
     * Carga las preferencias del usuario rellenando los huecos
     * con los defaults del enum. La forma devuelta es estable
     * (siempre un entry por evento) y apta para iterar en la
     * vista sin chequear nulos.
     *
     * @return array<string, array{event: NotificationEvent, in_app: bool, email: bool, persisted: bool}>
     */
    private function loadPreferencesFor($user): array
    {
        $persisted = $user->notificationPreferences()
            ->get()
            ->keyBy('event');

        $out = [];
        foreach (NotificationEvent::cases() as $event) {
            $row = $persisted->get($event->value);
            $out[$event->value] = [
                'event' => $event,
                'in_app' => $row ? (bool) $row->in_app : $event->defaultInApp(),
                'email' => $row ? (bool) $row->email : $event->defaultEmail(),
                'persisted' => $row !== null,
            ];
        }

        return $out;
    }
}
