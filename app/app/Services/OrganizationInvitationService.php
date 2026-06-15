<?php

namespace App\Services;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Logica reutilizable de invitaciones a organizaciones.
 *
 * Concentra la creacion del token crudo + su hash, el envio del email
 * y la aceptacion con vinculacion al usuario. El token en claro solo
 * se entrega al metodo que envia el email; en BD solo se guarda el
 * hash, igual que hace Laravel con los tokens de password reset.
 */
class OrganizationInvitationService
{
    /**
     * Numero de dias que permanece valida una invitacion.
     */
    private const EXPIRATION_DAYS = 7;

    /**
     * Crea una invitacion para el email indicado y devuelve el token
     * en claro (necesario para construir el enlace del email).
     *
     * @param  \App\Models\Organization  $organization
     * @param  string  $email
     * @param  \App\Enums\OrganizationUserRole  $role
     * @param  \App\Models\User  $inviter
     * @return array{0: \App\Models\OrganizationInvitation, 1: string}
     */
    public function create(
        Organization $organization,
        string $email,
        OrganizationUserRole $role,
        User $inviter,
    ): array {
        $rawToken = Str::random(64);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'email' => strtolower($email),
            'token' => Hash::make($rawToken),
            'role' => $role,
            'expires_at' => Carbon::now()->addDays(self::EXPIRATION_DAYS),
            'created_by' => $inviter->id,
        ]);

        return [$invitation, $rawToken];
    }

    /**
     * Busca una invitacion a partir de un token en claro. Recorre las
     * invitaciones pendientes y valida el hash. Es O(n) pero aceptable
     * para el volumen esperado en MVP; si la base crece se puede
     * indexar por prefijo del hash.
     *
     * @param  string  $rawToken
     * @return \App\Models\OrganizationInvitation|null
     */
    public function findByRawToken(string $rawToken): ?OrganizationInvitation
{
    if ($rawToken === '') {
        return null;
    }

    return OrganizationInvitation::usable()
        ->get()
        ->first(fn (OrganizationInvitation $invitation) => Hash::check($rawToken, $invitation->token));
}

    /**
     * Acepta una invitacion: vincula al usuario a la organizacion con
     * el rol correspondiente y marca la invitacion como aceptada.
     *
     * @param  \App\Models\OrganizationInvitation  $invitation
     * @param  \App\Models\User  $user
     * @return void
     */
    public function accept(OrganizationInvitation $invitation, User $user): void
    {
        if ($invitation->isAccepted() || $invitation->isExpired()) {
            return;
        }

        $organization = $invitation->organization;

        if (! $organization->members()->where('users.id', $user->id)->exists()) {
            $organization->members()->attach($user->id, [
                'role' => $invitation->role->value,
            ]);
        }

        $invitation->markAccepted();
    }
}
