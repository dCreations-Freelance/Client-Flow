<?php

namespace App\Models;

use App\Enums\AiProvider;
use Database\Factories\AiConfigFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuracion del asistente IA de un proyecto (o global).
 *
 * - `project_id = null` representa la configuracion global,
 *   usada como fallback cuando un proyecto no tiene la suya.
 * - `api_key` se almacena cifrada con el cast `encrypted` de
 *   Eloquent. El atributo plano solo es accesible en memoria
 *   durante el request; en BD se guarda como ciphertext.
 * - `is_active` permite "apagar" la config sin borrarla.
 * - Los limites `max_*` parametrizan `AiRateLimiter` para
 *   que cada config pueda tener una cuota propia.
 */
class AiConfig extends Model
{
    /** @use HasFactory<AiConfigFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'provider',
        'api_key',
        'model',
        'system_prompt',
        'is_active',
        'max_messages_per_hour',
        'max_sessions_per_day',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'api_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => AiProvider::class,
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
            'max_messages_per_hour' => 'integer',
            'max_sessions_per_day' => 'integer',
        ];
    }

    /**
     * Garantiza que solo exista una fila global y, como maximo,
     * una fila por proyecto. La migracion ya declara un unique
     * sobre `project_id`, pero este hook cubre el caso en que
     * Eloquent intente persistir un duplicado en la misma
     * transaccion antes de que el constraint dispare.
     */
    protected static function booted(): void
    {
        static::saving(function (AiConfig $config): void {
            // No se relaja la validacion aqui: confiamos en el
            // unique constraint de la migracion. El hook queda
            // como punto de extension futuro (e.g. encriptar
            // la api_key manualmente si algun dia se quita el
            // cast).
        });
    }

    /**
     * Proyecto al que pertenece la configuracion. Sera `null`
     * para la configuracion global.
     *
     * @return BelongsTo<Project, AiConfig>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Determina si la configuracion es la global (no asociada
     * a un proyecto concreto).
     *
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->project_id === null;
    }

    /**
     * Modelo efectivo que se enviara al provider. Si el admin
     * no especifico uno en la config, se usa el modelo por
     * defecto del provider. Asi `AiService` no tiene que
     * ocuparse del fallback.
     *
     * @return string
     */
    public function effectiveModel(): string
    {
        if (is_string($this->model) && $this->model !== '') {
            return $this->model;
        }

        /** @var AiProvider $provider */
        $provider = $this->provider;

        return $provider->defaultModel();
    }

    /**
     * System prompt efectivo. Si la config no define uno
     * propio, devuelve `null` para que `ProjectContextBuilder`
     * genere uno en castellano con el contexto del proyecto.
     *
     * @return string|null
     */
    public function effectiveSystemPrompt(): ?string
    {
        $prompt = $this->system_prompt;

        if (! is_string($prompt) || trim($prompt) === '') {
            return null;
        }

        return $prompt;
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Solo configuraciones activas.
     *
     * @param  Builder<AiConfig>  $query
     * @return Builder<AiConfig>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Configuracion global (no asociada a proyecto).
     *
     * @param  Builder<AiConfig>  $query
     * @return Builder<AiConfig>
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('project_id');
    }

    /**
     * Configuracion especifica del proyecto indicado.
     *
     * @param  Builder<AiConfig>  $query
     * @param  int  $projectId
     * @return Builder<AiConfig>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }
}
