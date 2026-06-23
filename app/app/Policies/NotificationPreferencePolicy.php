<?php

namespace App\Policies;

use App\Models\NotificationPreference;
use App\Models\User;

/**
 * Politica de `NotificationPreference`.
 *
 * Las preferencias son estrictamente personales: cada usuario solo
 * puede ver, editar y borrar las suyas. Esto cierra el vector de
 * fuga donde un usuario A cambiase las preferencias del usuario B
 * (algo sin sentido funcional y con riesgo de spam/abuso).
 *
 * Las acciones estan pensadas para que el admin y el cliente
 * tengan exactamente los mismos permisos sobre sus propias
 * preferencias: la regla es "solo el dueno", no "solo admin".
 */
class NotificationPreferencePolicy
{
    /**
     * Ver el listado de preferencias propias. Equivalente a
     * poder ver la pagina `/admin/notifications/preferences` o
     * `/portal/notifications/preferences`.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Cualquier usuario autenticado puede ver SU listado
        // (la consulta ya filtra por el user actual). Esta policy
        // existe para que `authorize('viewAny', ...)` no devuelva
        // 403 por defecto en los controladores.
        return true;
    }

    /**
     * Ver una preferencia concreta. Solo el dueno.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\NotificationPreference  $preference
     * @return bool
     */
    public function view(User $user, NotificationPreference $preference): bool
    {
        return (int) $preference->user_id === (int) $user->id;
    }

    /**
     * Crear preferencias. La creacion automatica la hace el
     * listener `CreateDefaultNotificationPreferences`; un usuario
     * no deberia poder crearlas a mano, pero dejamos la policy
     * como hook por si en una fase futura se permite "importar"
     * presets.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Editar una preferencia. Solo el dueno.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\NotificationPreference  $preference
     * @return bool
     */
    public function update(User $user, NotificationPreference $preference): bool
    {
        return (int) $preference->user_id === (int) $user->id;
    }

    /**
     * Eliminar una preferencia. Solo el dueno. En la practica
     * no se expone UI para borrar; el usuario desactiva canales
     * via `update`. La policy se mantiene por simetria con el
     * resto de acciones.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\NotificationPreference  $preference
     * @return bool
     */
    public function delete(User $user, NotificationPreference $preference): bool
    {
        return (int) $preference->user_id === (int) $user->id;
    }
}
