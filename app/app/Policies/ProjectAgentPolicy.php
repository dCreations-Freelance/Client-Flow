<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectAgent;
use App\Models\User;

/**
 * Politica de autorizacion para `ProjectAgent` (asignacion
 * de un template de agente a un proyecto).
 *
 * En MVP la gestion es 100% admin: el cliente del portal
 * puede ver el resto del proyecto (kanban, documentos, chat,
 * calendario) pero no deberia ver ni configurar los agentes
 * IA. Los templates son internos para configurar los IDEs
 * del equipo tecnico; un cliente que pregunte "que IA
 * usas para responderme" es una pregunta de soporte, no
 * algo que el portal deba responder automaticamente.
 *
 * Por coherencia con el resto del proyecto, se mantiene la
 * posibilidad de evolucionar esto (e.g. un cliente premium
 * podria en una fase futura elegir su `system_prompt`),
 * pero la policy actual lo bloquea en seco.
 */
class ProjectAgentPolicy
{
    /**
     * Determina si el usuario puede ver la lista de agentes
     * asignados a un proyecto. Admin siempre; cliente nunca
     * en MVP, ni aunque sea miembro de la organizacion.
     *
     * @return bool
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede ver el detalle de una
     * asignacion concreta. Mismo criterio que `viewAny`.
     *
     * @return bool
     */
    public function view(User $user, ProjectAgent $agent): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede asignar un template al
     * proyecto. Solo admin.
     *
     * @return bool
     */
    public function create(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede editar el override de
     * system prompt de una asignacion. Solo admin.
     *
     * @return bool
     */
    public function update(User $user, ProjectAgent $agent): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede desasignar un template
     * del proyecto. Solo admin.
     *
     * @return bool
     */
    public function delete(User $user, ProjectAgent $agent): bool
    {
        return $user->isAdmin();
    }
}
