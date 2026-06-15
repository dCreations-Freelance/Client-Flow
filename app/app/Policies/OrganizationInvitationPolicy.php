<?php

namespace App\Policies;

use App\Models\OrganizationInvitation;
use App\Models\User;

/**
 * Politica para aceptar o revocar invitaciones.
 *
 * Aceptar es publico en el sentido de que cualquiera con el token
 * puede hacerlo, pero lo verificamos con un usuario autenticado.
 * La policy evita que un usuario logueado con otro email pueda
 * aceptar una invitacion ajena.
 */
class OrganizationInvitationPolicy
{
    /**
     * Determina si el usuario puede aceptar la invitacion.
     *
     * Reglas:
     * - El email autenticado debe coincidir con el de la invitacion.
     * - La invitacion debe estar vigente (no aceptada, no expirada).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\OrganizationInvitation  $invitation
     * @return bool
     */
    public function accept(User $user, OrganizationInvitation $invitation): bool
    {
        if (! $invitation->isUsable()) {
            return false;
        }

        return strcasecmp($user->email, $invitation->email) === 0;
    }

    /**
     * Determina si el usuario puede revocar la invitacion. Reservado
     * al admin que la creo o a otro admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\OrganizationInvitation  $invitation
     * @return bool
     */
    public function revoke(User $user, OrganizationInvitation $invitation): bool
    {
        return $user->isAdmin();
    }
}
