<?php

namespace App\Models;

use App\Enums\DocumentVisibility;
use Database\Factories\ProjectDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Documento markdown asociado a un proyecto.
 *
 * Los documentos viven dentro de un proyecto y tienen una visibilidad
 * que determina quien puede verlos en el portal cliente:
 * - `private`: solo admin (y sera accesible via MCP en fases futuras).
 * - `public`: admin + clientes miembros de la organizacion del
 *   proyecto, siempre que el proyecto sea visible y no este
 *   archivado.
 *
 * El contenido se almacena en la propia fila (`content` longtext)
 * para simplificar la busqueda y evitar problemas de storage en
 * hosting compartido, tal como define `docs/DATA_MODEL.md`.
 */
class ProjectDocument extends Model
{
    /** @use HasFactory<ProjectDocumentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'title',
        'content',
        'visibility',
        'created_by',
    ];

    /**
     * Casts: el enum facilita comparaciones type-safe en policies y
     * vistas, y los timestamps pasan a Carbon para formato en Blade.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => DocumentVisibility::class,
        ];
    }

    /**
     * Proyecto al que pertenece el documento.
     *
     * @return BelongsTo<Project, ProjectDocument>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Usuario que creo el documento. Se mantiene `restrictOnDelete`
     * en la FK para que el borrado de un usuario no destruya
     * documentos historicos.
     *
     * @return BelongsTo<User, ProjectDocument>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    /**
     * Markdown ya renderizado a HTML. Se cachea en una propiedad
     * volatil para no recalcular en cada llamada dentro del mismo
     * request (util en listados con excerpt + render completo).
     */
    public function getRenderedContentAttribute(): string
    {
        return Str::markdown($this->content ?? '');
    }

    /**
     * Extracto plano del contenido markdown pensado para el listado.
     * Quita simbolos de markdown mas obvios y trunca a la longitud
     * pedida. Si el contenido es vacio devuelve cadena vacia.
     */
    public function excerpt(int $length = 160): string
    {
        $text = strip_tags($this->rendered_content);

        // Quitamos espacios y saltos sobrantes para que el excerpt
        // sea legible cuando el markdown tiene multiples lineas.
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length - 1)).'…';
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Solo documentos publicos.
     *
     * @param  Builder<ProjectDocument>  $query
     * @return Builder<ProjectDocument>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', DocumentVisibility::Public->value);
    }

    /**
     * Solo documentos privados.
     *
     * @param  Builder<ProjectDocument>  $query
     * @return Builder<ProjectDocument>
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('visibility', DocumentVisibility::Private->value);
    }

    /**
     * Filtra los documentos que pertenecen a un proyecto concreto.
     *
     * @param  Builder<ProjectDocument>  $query
     * @param  int  $projectId
     * @return Builder<ProjectDocument>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Busqueda simple por termino: aplica `LIKE` insensible a
     * mayusculas sobre `title` y `content`. El termino se sanea
     * para evitar wildcards peligrosos.
     *
     * @param  Builder<ProjectDocument>  $query
     * @param  string  $term
     * @return Builder<ProjectDocument>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        // Escapamos `%` y `_` para que el usuario pueda buscar
        // literalmente estos caracteres sin disparar wildcards.
        $escaped = addcslashes($term, '%_\\');
        $like = '%'.$escaped.'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('title', 'like', $like)
                ->orWhere('content', 'like', $like);
        });
    }

    /**
     * Orden por mas recientes. Pensado para listados por defecto;
     * se puede combinar con otros scopes.
     *
     * @param  Builder<ProjectDocument>  $query
     * @return Builder<ProjectDocument>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('updated_at')->orderByDesc('id');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Determina si el documento es visible para clientes.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->visibility?->isPublic() ?? false;
    }

    /**
     * Determina si el documento es de uso interno.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->visibility?->isPrivate() ?? false;
    }
}
