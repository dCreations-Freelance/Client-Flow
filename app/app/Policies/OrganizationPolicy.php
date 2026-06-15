<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

/**
 * Politica de autorizacion para `Organization`.
 *
 * Reglas:
 * - Solo admin puede crear, editar, eliminar o invitar.
 * - Admin siempre puede ver cualquier organizacion.
 * - Clientes solo pueden ver organizaciones donde son miembros.
 * - El resto de operaciones (create/update/delete/invite) quedan
 *   restringidas a admin para mantener el control sobre la
 *   informacion de cada cliente.
 */
class OrganizationPolicy
{
    /**
     * Determina si el usuario puede listar organizaciones.
     * Admin ve todo; cliente ve solo las suyas (filtradas en el controller).
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede ver el detalle de la organizacion.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @return bool
     */
    public function view(User $user, Organization $organization): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $organization->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Determina si el usuario puede crear organizaciones.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede editar la organizacion.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @return bool
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede eliminar la organizacion.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @return bool
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede invitar miembros.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @return bool
     */
    public function invite(User $user, Organization $organization): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede gestionar miembros.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @return bool
     */
    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->isAdmin();
    }
}
