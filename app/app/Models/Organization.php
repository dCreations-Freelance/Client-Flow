<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Modelo de organizacion (cliente externo).
 *
 * Una organizacion agrupa miembros y proyectos. El `owner_id` apunta
 * al usuario de ClientFlow que la creo (tipicamente un admin). El slug
 * se genera automaticamente a partir del nombre en el evento `creating`
 * para mantener las URLs limpias sin obligar a escribirlo a mano.
 */
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * Atributos asignables. `slug` se permite en fillable para soportar
     * tests que lo setean explicitamente, aunque normalmente se genera.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_path',
        'owner_id',
        'status',
    ];

    /**
     * Casts. `status` se castea al enum para tipado fuerte en policies.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
        ];
    }

    /**
     * Genera el slug automaticamente si no viene definido. Se aplica en
     * `creating` (no en `saving`) para no sobreescribir un slug que el
     * usuario haya establecido explicitamente en una edicion.
     */
    protected static function booted(): void
    {
        static::creating(function (Organization $organization): void {
            if (blank($organization->slug)) {
                $organization->slug = static::generateUniqueSlug($organization->name);
            }
        });
    }

    /**
     * Genera un slug unico a partir del nombre. Si ya existe uno igual
     * se le anade un sufijo numerico incremental.
     *
     * @param  string  $name
     * @return string
     */
    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    /**
     * Usuario administrador que creo la organizacion.
     *
     * @return BelongsTo<User, Organization>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Miembros de la organizacion. La columna `role` del pivot distingue
     * al owner del miembro regular.
     *
     * @return BelongsToMany<User>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Subset de miembros con rol `owner`. Util en policies para validar
     * que no se elimine al unico owner de la organizacion.
     *
     * @return BelongsToMany<User>
     */
    public function owners(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'owner');
    }

    /**
     * Proyectos de la organizacion. Se anade como declaracion adelantada
     * para alinear con `docs/DATA_MODEL.md`; el modelo `Project` se
     * creara en la fase 2.
     *
     * @return HasMany<Project>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Invitaciones pendientes o historicas emitidas a esta organizacion.
     *
     * @return HasMany<OrganizationInvitation>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    /**
     * Invitaciones que aun son utilizables (no expiradas, no aceptadas).
     *
     * @return HasMany<OrganizationInvitation>
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope a organizaciones activas. Pensado para el listado principal
     * y para mostrar solo las orgs a las que merece la pena invitar.
     *
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OrganizationStatus::Active->value);
    }

    /**
     * Scope a organizaciones en las que el usuario es miembro. Util
     * para que el cliente solo vea sus propias organizaciones en el
     * portal.
     *
     * @param  Builder<Organization>  $query
     * @param  \App\Models\User  $user
     * @return Builder<Organization>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('members', fn (Builder $q) => $q->where('users.id', $user->id));
    }
}
