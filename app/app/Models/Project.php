<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Modelo de proyecto.
 *
 * Un proyecto pertenece a una organizacion y tiene un equipo de
 * usuarios miembros (via pivot `project_user`). Soporta archivado
 * via `archived_at` (soft) y visibilidad selectiva para clientes
 * via `is_visible_to_client`. Las relaciones con BoardColumn, Task,
 * ProjectDocument, ProjectMessage, CalendarEvent, ProjectAgent y
 * AiConfig se declaran como firmas adelantadas para alinear el
 * codigo con `docs/DATA_MODEL.md` y compilar aunque las clases
 * no existan todavia.
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'status',
        'progress',
        'starts_at',
        'estimated_ends_at',
        'cover_path',
        'is_visible_to_client',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'progress' => 'integer',
            'is_visible_to_client' => 'boolean',
            'starts_at' => 'date',
            'estimated_ends_at' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Genera el slug automaticamente al crear si no se ha establecido.
     * Se anade un sufijo numerico si ya existe para garantizar
     * unicidad sin necesidad de intervencion del usuario.
     */
    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (blank($project->slug)) {
                $project->slug = static::generateUniqueSlug($project->name);
            }
        });
    }

    /**
     * Genera un slug unico para el nombre del proyecto.
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
     * Organizacion a la que pertenece el proyecto.
     *
     * @return BelongsTo<Organization, Project>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Usuarios asignados al proyecto. Las relaciones se cargan con
     * `withTimestamps` para reflejar cuando se unio cada uno.
     *
     * @return BelongsToMany<User>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withTimestamps();
    }

    /**
     * Acceso rapido al owner del proyecto via la organizacion. Es un
     * helper, no una relacion almacenada, y se mantiene sincronizado
     * por el flujo de creacion de la organizacion.
     */
    public function owner(): ?User
    {
        return $this->organization?->owner;
    }

    /**
     * Determina si el proyecto esta archivado.
     *
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Marca el proyecto como archivado. No se hace soft-delete de la
     * fila para no romper relaciones; basta con `archived_at`.
     *
     * @return void
     */
    public function archive(): void
    {
        if (! $this->isArchived()) {
            $this->forceFill(['archived_at' => Carbon::now()])->save();
        }
    }

    /**
     * Quita el archivado del proyecto.
     *
     * @return void
     */
    public function unarchive(): void
    {
        if ($this->isArchived()) {
            $this->forceFill(['archived_at' => null])->save();
        }
    }

    /**
     * Acceso al porcentaje de progreso normalizado. Devuelve 0-100,
     * equivalente al valor almacenado pero como float para
     * integraciones que esperen ese tipo.
     */
    public function getProgressPercentAttribute(): float
    {
        return (float) $this->progress;
    }

    /**
     * Determina si el proyecto es visible para los clientes del
     * portal. Combina el flag explicito y el estado de archivado:
     * un proyecto archivado no se muestra aunque su flag diga true.
     *
     * @return bool
     */
    public function isVisibleToClient(): bool
    {
        return $this->is_visible_to_client && ! $this->isArchived();
    }

    // -----------------------------------------------------------------
    // Relaciones adelantadas para fases siguientes.
    // Compilan aunque las clases no existan todavia y serviran de
    // contrato cuando lleguen kanban, docs, chat, calendario, etc.
    // -----------------------------------------------------------------

    /** @return HasMany<BoardColumn> */
    public function columns(): HasMany
    {
        return $this->hasMany(BoardColumn::class);
    }

    /** @return HasMany<Task> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return HasMany<ProjectDocument> */
    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    /** @return HasMany<ProjectMessage> */
    public function messages(): HasMany
    {
        return $this->hasMany(ProjectMessage::class);
    }

    /** @return HasMany<CalendarEvent> */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /** @return BelongsToMany<AgentTemplate> */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(AgentTemplate::class, 'project_agents')
            ->withPivot('system_prompt_override')
            ->withTimestamps();
    }

    /** @return HasOne<AiConfig> */
    public function aiConfig(): HasOne
    {
        return $this->hasOne(AiConfig::class);
    }

    /** @return HasMany<AiChatSession> */
    public function aiChatSessions(): HasMany
    {
        return $this->hasMany(AiChatSession::class);
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Proyectos abiertos: excluye completados y archivados.
     *
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            ProjectStatus::Completed->value,
            ProjectStatus::Archived->value,
        ])->whereNull('archived_at');
    }

    /**
     * Solo proyectos archivados.
     *
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Proyectos visibles para clientes: el flag explicito debe estar
     * activo y no deben estar archivados.
     *
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeVisibleToClient(Builder $query): Builder
    {
        return $query->where('is_visible_to_client', true)
            ->whereNull('archived_at');
    }

    /**
     * Proyectos visibles al usuario a traves de las organizaciones a
     * las que pertenece. Un cliente ve los proyectos visibles de sus
     * organizaciones aunque no este asignado como miembro del
     * proyecto. La asignacion a proyecto (vía pivot `project_user`)
     * es ortogonal: sirve para saber quien trabaja en el proyecto,
     * no para limitar la visibilidad.
     *
     * @param  Builder<Project>  $query
     * @param  \App\Models\User  $user
     * @return Builder<Project>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('organization.members', fn (Builder $q) => $q->where('users.id', $user->id));
    }
}
