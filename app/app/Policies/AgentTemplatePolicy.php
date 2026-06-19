<?php

namespace App\Policies;

use App\Models\AgentTemplate;
use App\Models\User;

/**
 * Politica de autorizacion para `AgentTemplate`.
 *
 * La biblioteca de templates de agentes IA es operativa del
 * administrador de la instancia: solo el admin puede
 * verla, crearla, editarla o borrarla. Los clientes no
 * deberian ni siquiera saber que existe, porque expone
 * system prompts y herramientas internas de ClientFlow
 * que son detalles de la operacion del admin.
 *
 * El controlador invoca `authorize('viewAny', AgentTemplate::class)`
 * en todos los endpoints, asi que la policy no recibe nunca
 * una instancia: discrimina unicamente por rol.
 */
class AgentTemplatePolicy
{
    /**
     * Determina si el usuario puede ver el listado de templates.
     *
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede ver el detalle de un
     * template concreto. La policy solo discrimina por rol.
     *
     * @return bool
     */
    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede crear templates.
     *
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede editar un template.
     *
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede eliminar un template.
     *
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
