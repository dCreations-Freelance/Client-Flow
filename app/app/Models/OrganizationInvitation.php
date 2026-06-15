<?php

namespace App\Models;

use App\Enums\OrganizationUserRole;
use Database\Factories\OrganizationInvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Invitacion a unirse a una organizacion.
 *
 * El token almacenado en BD es el hash del token entregado al usuario.
 * Esto evita que un dump de la BD permita aceptar invitaciones ajenas.
 * Para comparar tokens se usa `Hash::check` desde el servicio de
 * invitaciones.
 */
class OrganizationInvitation extends Model
{
    /** @use HasFactory<OrganizationInvitationFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'email',
        'token',
        'role',
        'expires_at',
        'accepted_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'role' => OrganizationUserRole::class,
        ];
    }

    /**
     * Organizacion a la que se invita.
     *
     * @return BelongsTo<Organization, OrganizationInvitation>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Usuario administrador que emitio la invitacion.
     *
     * @return BelongsTo<User, OrganizationInvitation>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determina si la invitacion ha sido ya aceptada.
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Determina si la invitacion ha expirado.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? true;
    }

    /**
     * Determina si la invitacion esta vigente: ni aceptada ni expirada.
     *
     * @return bool
     */
    public function isUsable(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    /**
     * Marca la invitacion como aceptada. Lo hace el servicio tras validar
     * el token y asociar al usuario a la organizacion.
     *
     * @return void
     */
    public function markAccepted(): void
    {
        $this->forceFill(['accepted_at' => Carbon::now()])->save();
    }

    /**
     * Scope a invitaciones utilizables (no aceptadas, no expiradas).
     *
     * @param  Builder<OrganizationInvitation>  $query
     * @return Builder<OrganizationInvitation>
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }
}
