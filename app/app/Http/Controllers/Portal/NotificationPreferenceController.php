<?php

namespace App\Http\Controllers\Portal;

use App\Enums\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNotificationPreferencesRequest;
use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Gestion de las preferencias de notificacion del cliente.
 *
 * Replica exactamente el comportamiento del controlador admin,
 * pero renderiza la vista del portal. Mantener los dos
 * controladores separados (en lugar de uno solo compartido) es
 * coherente con la convencion del proyecto: cada zona tiene su
 * propio controlador aunque la logica sea identica.
 */
class NotificationPreferenceController extends Controller
{
    /**
     * Muestra la pagina de preferencias del cliente actual.
     * Si el cliente todavia no tiene ninguna fila persistida,
     * siembra las seis preferencias por defecto antes de
     * renderizar.
     */
    public function index(): View
    {
        $user = auth()->user();
        $this->authorize('viewAny', NotificationPreference::class);

        $this->seedDefaultsIfMissing($user);

        $preferences = $this->loadPreferencesFor($user);

        return view('portal.notifications.preferences', [
            'preferences' => $preferences,
            'events' => NotificationEvent::cases(),
            'zone' => 'portal',
        ]);
    }

    /**
     * Persiste las preferencias del cliente actual.
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

    /**
     * Si el usuario no tiene ninguna preferencia persistida, crea
     * las seis filas con los defaults del enum. Es idempotente: si
     * ya existe al menos una fila, no toca la tabla.
     */
    private function seedDefaultsIfMissing($user): void
    {
        if ($user->notificationPreferences()->exists()) {
            return;
        }

        foreach (NotificationEvent::cases() as $event) {
            NotificationPreference::create([
                'user_id' => $user->id,
                'event' => $event->value,
                'in_app' => $event->defaultInApp(),
                'email' => $event->defaultEmail(),
            ]);
        }
    }
}
