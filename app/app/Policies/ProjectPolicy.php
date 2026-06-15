<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;

/**
 * Politica de autorizacion para `Project`.
 *
 * Reglas:
 * - Admin tiene control total sobre cualquier proyecto.
 * - El cliente solo puede ver proyectos de organizaciones donde es
 *   miembro, que ademas esten marcados como visibles y no esten
 *   archivados. Esas comprobaciones se reflejan tambien en scopes
 *   del modelo para que el cliente nunca reciba datos de proyectos
 *   a los que no tendria que acceder.
 * - Crear, editar, archivar, eliminar y gestionar miembros son
 *   operaciones reservadas al admin.
 */
class ProjectPolicy
{
    /**
     * Solo admin puede listar proyectos desde el panel.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Ver un proyecto requiere:
     * - Ser admin, o
     * - Ser cliente y miembro de la organizacion del proyecto, y
     *   ademas que el proyecto este visible al cliente.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isClient()) {
            return false;
        }

        $isMember = $project->organization
            ->members()
            ->where('users.id', $user->id)
            ->exists();

        return $isMember && $project->isVisibleToClient();
    }

    /**
     * Solo admin puede crear proyectos.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Solo admin puede editar un proyecto.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Solo admin puede eliminar un proyecto.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Archivar / desarchivar es exclusivo del admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function archive(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Gestionar miembros del proyecto: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Comprobacion reutilizable: el usuario es miembro de la
     * organizacion a la que pertenece el proyecto. Pensada para
     * validar antes de anadir al usuario al proyecto.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @return bool
     */
    public function belongsToOrganization(User $user, Organization $organization): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $organization->members()->where('users.id', $user->id)->exists();
    }
}
