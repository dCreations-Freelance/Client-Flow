<?php

namespace App\Models;

use Database\Factories\AgentTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Template de agente IA de la biblioteca del administrador.
 *
 * Un template es una pieza editorial: nombre, descripcion,
 * system prompt y (opcionalmente) herramientas y modelo por
 * defecto. El admin la usa para configurar sus IDEs y, desde la
 * fase transversal, para asignarla a un proyecto via el modelo
 * pivot `ProjectAgent`.
 *
 * Decisiones:
 * - `tools` se almacena como JSON libre: la estructura concreta
 *   (formato MCP, function calling de OpenAI, etc.) la imponen
 *   los IDEs clientes. El modelo no la interpreta, solo la
 *   persiste y la devuelve en `toExportArray()`. Validamos la
 *   forma basica (array) en el Form Request, no el esquema.
 * - `model` se persiste como string libre. Tampoco se interpreta
 *   en ClientFlow: es una pista para el IDE destino.
 * - `category` permite agrupar templates en el panel admin. Se
 *   mantiene como string libre para no atar el modelo a un enum
 *   y poder reagrupar sin migraciones.
 * - `created_by` apunta al admin que dio de alta el template. No
 *   se expone para nada operativo, solo trazabilidad.
 */
class AgentTemplate extends Model
{
    /** @use HasFactory<AgentTemplateFactory> */
    use HasFactory;

    /**
     * Atributos asignables en masa. `created_by` se rellena en
     * el controlador a partir de `auth()->id()` para evitar
     * dependencia del cliente en ese dato.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'system_prompt',
        'tools',
        'model',
        'category',
        'created_by',
    ];

    /**
     * Casts: `tools` como array para acceder en PHP sin JSON
     * decode manual; `created_by` como entero.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tools' => 'array',
            'created_by' => 'integer',
        ];
    }

    /**
     * Usuario administrador que creo el template. Es solo
     * trazabilidad y se mantiene aunque el admin sea borrado
     * por la cascada, lo que dejaria al template huerfano: en
     * ese caso, el controlador debe detectar la situacion
     * antes de borrar.
     *
     * @return BelongsTo<User, AgentTemplate>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Proyectos a los que esta asignado el template, via
     * pivot `project_agents`. Se expone con `withPivot` para
     * que la vista de detalle del template pueda listar los
     * overrides concretos de cada proyecto sin una query
     * adicional.
     *
     * @return BelongsToMany<Project>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_agents')
            ->withPivot('system_prompt_override')
            ->withTimestamps();
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Filtra por categoria exacta. Si la categoria es null o
     * vacia, no se aplica filtro: util para mantener el scope
     * usable desde el controlador, que decide segun la query
     * string.
     *
     * @param  Builder<AgentTemplate>  $query
     * @return Builder<AgentTemplate>
     */
    public function scopeByCategory(Builder $query, ?string $category): Builder
    {
        if ($category === null || trim($category) === '') {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Busca por coincidencia parcial en `name` y `description`.
     * Se aplica LIKE %term% en OR: si cualquiera contiene el
     * termino, el template aparece.
     *
     * @param  Builder<AgentTemplate>  $query
     * @return Builder<AgentTemplate>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('name', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Serializa el template al formato JSON que consumen los
     * endpoints de export. Pensado para descargarse desde la
     * vista de detalle y copiarse al `systemPromptFile` de un
     * IDE (Cursor, Claude Code, etc.).
     *
     * @return array<string, mixed>
     */
    public function toExportArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'system_prompt' => $this->system_prompt,
            'tools' => $this->tools,
            'model' => $this->model,
            'category' => $this->category,
        ];
    }
}
