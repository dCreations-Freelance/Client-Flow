<?php

namespace App\Policies;

use App\Models\AiChatSession;
use App\Models\User;

/**
 * Politica de autorizacion para `AiChatSession`.
 *
 * Reglas:
 * - Admin puede ver, crear y borrar sesiones en cualquier
 *   proyecto.
 * - Cliente puede ver, crear y borrar solo las sesiones
 *   propias (`user_id = $user->id`) en proyectos que ya
 *   puede ver segun `ProjectPolicy::view`. Esto evita
 *   que un cliente liste sesiones de otros clientes del
 *   mismo proyecto.
 * - Las sesiones son inmutables salvo por borrado: no hay
 *   `update`.
 */
class AiChatSessionPolicy
{
    /**
     * Determina si el usuario puede listar las sesiones
     * de un proyecto. Admin: siempre. Cliente: solo si
     * puede ver el proyecto.
     *
     * @return bool
     */
    public function viewAny(User $user, \App\Models\Project $project): bool
    {
        return $user->can('view', $project);
    }

    /**
     * Determina si el usuario puede ver una sesion concreta.
     * Ademas de poder ver el proyecto, debe ser dueno de
     * la sesion (o ser admin).
     *
     * @return bool
     */
    public function view(User $user, AiChatSession $session): bool
    {
        if (! $user->can('view', $session->project)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $session->user_id === $user->id;
    }

    /**
     * Determina si el usuario puede crear una sesion nueva
     * en el proyecto. Delegamos en `ProjectPolicy::view`:
     * cualquier persona que pueda ver el proyecto puede
     * abrir su propio chat con el asistente.
     *
     * @return bool
     */
    public function create(User $user, \App\Models\Project $project): bool
    {
        return $user->can('view', $project);
    }

    /**
     * Las sesiones no se editan. Si en el futuro se
     * quisiera permitir renombrar, habria que cambiar el
     * `update` a admin o dueno.
     *
     * @return bool
     */
    public function update(User $user, AiChatSession $session): bool
    {
        return false;
    }

    /**
     * Determina si el usuario puede borrar la sesion.
     * Admin: siempre que pueda ver el proyecto. Cliente:
     * solo si es dueno.
     *
     * @return bool
     */
    public function delete(User $user, AiChatSession $session): bool
    {
        if (! $user->can('view', $session->project)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $session->user_id === $user->id;
    }
}
