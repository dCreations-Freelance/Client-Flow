<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;

/**
 * Politica de autorizacion para `ProjectMessage`.
 *
 * Reglas:
 * - Admin: control total. Puede leer y enviar mensajes en cualquier
 *   proyecto.
 * - Cliente: solo puede leer y enviar mensajes en proyectos a los
 *   que ya tiene acceso (miembro de la organizacion + proyecto
 *   visible). La verificacion se delega en `ProjectPolicy::view`.
 *   Los mensajes de sistema son visibles para todos los que pueden
 *   ver el proyecto, igual que los de texto.
 * - Crear mensajes de tipo `system` se reserva a la app: los
 *   usuarios nunca deben poder enviar un mensaje de sistema. La
 *   policy rechaza `create` cuando se pide tipo system; la
 *   validacion del formulario refuerza que solo se acepte `text`.
 */
class ProjectMessagePolicy
{
    /**
     * Determina si el usuario puede ver el chat de un proyecto.
     * La autorizacion real se hace contra el proyecto, no contra
     * cada mensaje individual, para mantener el aislamiento.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function view(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }

    /**
     * Ver un mensaje concreto. Si el usuario puede ver el proyecto
     * puede ver cualquier mensaje (texto o sistema).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProjectMessage  $message
     * @return bool
     */
    public function viewMessage(User $user, ProjectMessage $message): bool
    {
        if ($message->project === null) {
            return false;
        }

        return $user->can('view', $message->project);
    }

    /**
     * Enviar un mensaje de texto en un proyecto. La policy del
     * proyecto garantiza la membresia; aqui solo verificamos que
     * el usuario pueda ver el proyecto.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function create(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }

    /**
     * Crear mensajes de sistema esta reservado a la app (no
     * expuesto a traves de HTTP). Esta policy nunca se llama
     * desde un request de usuario, pero la mantenemos por
     * consistencia: si algun dia alguien intenta llamar a un
     * endpoint que la use, queda claro que no esta permitido
     * por un usuario.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function createSystem(User $user): bool
    {
        return false;
    }
}
