<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de usuario base de ClientFlow.
 *
 * Contiene la columna `role` que diferencia administradores de clientes y
 * las relaciones con organizaciones y proyectos que se iran completando en
 * fases posteriores. Mantiene la convencion de casts para enums de PHP 8.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Atributos asignables en masa. Se incluyen `role` para permitir el
     * registro de clientes con su rol por defecto sin necesidad de un fill
     * explicito posterior.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Atributos ocultos en serializaciones. `remember_token` se mantiene
     * oculto por seguridad junto con el password.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts de atributos. `role` se castea al enum para que las comparaciones
     * en policies y middleware sean type-safe.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Comprobacion rapida del rol administrador.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role?->isAdmin() ?? false;
    }

    /**
     * Comprobacion rapida del rol cliente.
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return $this->role?->isClient() ?? false;
    }

    /**
     * Organizaciones en las que el usuario es miembro. Se declara de forma
     * adelantada porque se usara en policies y vistas desde la fase 1B.
     *
     * @return BelongsToMany<Organization>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Organizaciones donde el usuario figura como `owner` en el pivot. Es
     * el conjunto de organizaciones que el cliente ve destacado en su portal.
     *
     * @return BelongsToMany<Organization>
     */
    public function ownedOrganizations(): BelongsToMany
    {
        return $this->organizations()->wherePivot('role', 'owner');
    }

    /**
     * Proyectos en los que el usuario participa. Pendiente de uso hasta
     * la fase 2, pero se incluye la firma para mantener el codigo compilable
     * y alineado con `docs/DATA_MODEL.md`.
     *
     * @return BelongsToMany<Project>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withTimestamps();
    }

    /**
     * Invitaciones a organizaciones creadas por este usuario (admin). Solo
     * tendra datos para administradores, pero se declara para mantener la
     * simetria con `docs/DATA_MODEL.md`.
     *
     * @return HasMany<OrganizationInvitation>
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'created_by');
    }
}
