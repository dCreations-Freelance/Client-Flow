<?php

namespace App\Models;

use App\Enums\ActivityType;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Entrada del feed de actividad de un proyecto.
 *
 * Cada fila es un "suceso" discreto (tarea creada, documento
 * publicado, mensaje enviado, etc.) que el feed cronologico
 * muestra al admin y al cliente del portal. El feed se sirve
 * desde esta tabla y se complementa con los `ProjectMessage`
 * de tipo `system` que el chat sigue mostrando.
 *
 * El modelo es inmutable: no se actualiza, solo se crea. La
 * unica operacion destructiva es la cascada via FK al borrar
 * un proyecto, que elimina tambien su feed.
 *
 * El campo `properties` (json libre) guarda datos especificos
 * del tipo de evento (columna origen/destino en un task_moved,
 * visibility en un document_published, etc.). Asi anadir un
 * dato nuevo no requiere migracion de columna.
 *
 * El campo `subject_type` + `subject_id` implementa un
 * `morphTo` para enlazar el evento a su "sujeto" real (la
 * tarea concreta, el documento, el evento de calendario). El
 * feed usa esto para pintar un link directo.
 */
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    /**
     * Nombre de la tabla asociado al modelo.
     *
     * Forzamos `activity_log` (singular) en vez del plural
     * por defecto `activity_logs` para que coincida con la
     * migracion y con `docs/DATA_MODEL.md`. Ademas de la
     * consistencia, evita que un cambio futuro en la
     * convencion de nombres de Laravel rompa el contrato.
     */
    protected $table = 'activity_log';

    /**
     * Atributos asignables en masa. Se incluyen los campos
     * polimorficos del `subject` y el `properties` para que
     * `ActivityLogger::record` pueda persistir en una sola
     * llamada.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'organization_id',
        'user_id',
        'type',
        'description',
        'subject_type',
        'subject_id',
        'properties',
    ];

    /**
     * Casts: `type` al enum `ActivityType` para comparaciones
     * type-safe, `properties` a array para que el codigo que
     * consulta `->properties` reciba un array asociativo en
     * lugar del string JSON crudo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Deshabilita los `updated_at` de Laravel: los eventos del
     * feed son inmutables (nunca se modifican tras su creacion).
     * Mantenemos `created_at` para el orden del feed.
     */
    public const UPDATED_AT = null;

    // -----------------------------------------------------------------
    // Relaciones
    // -----------------------------------------------------------------

    /**
     * Proyecto al que pertenece el evento. Nullable para soportar
     * eventos cross-project en una fase futura.
     *
     * @return BelongsTo<Project, ActivityLog>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Organizacion a la que pertenece el evento. Nullable por
     * la misma razon que `project()`.
     *
     * @return BelongsTo<Organization, ActivityLog>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Usuario autor del evento. Nullable para eventos automaticos
     * del sistema (jobs, etc).
     *
     * @return BelongsTo<User, ActivityLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sujeto polimorfico: el modelo concreto al que apunta el
     * evento (Task, ProjectDocument, CalendarEvent, etc.). Permite
     * al feed pintar un link directo al objeto.
     *
     * @return MorphTo<Model, ActivityLog>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // -----------------------------------------------------------------
    // Helpers de delegacion
    // -----------------------------------------------------------------

    /**
     * Determina si el evento es visible para clientes del portal.
     * Delega en el enum, que es quien conoce la politica. Para los
     * `document_*` y `attachment_*` el servicio aplica un filtro
     * adicional con `properties.visibility`.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->type?->isPublic() ?? false;
    }

    /**
     * Identificador del icono. Delega en el enum.
     *
     * @return string
     */
    public function icon(): string
    {
        return $this->type?->icon() ?? 'project';
    }

    /**
     * Color semantico del item. Delega en el enum.
     *
     * @return string
     */
    public function tone(): string
    {
        return $this->type?->tone() ?? 'gray';
    }

    /**
     * Categoria logica para los chips del filtro.
     *
     * @return string
     */
    public function category(): string
    {
        return $this->type?->category() ?? 'project';
    }

    /**
     * Resuelve la URL del sujeto si el modelo existe y la ruta
     * esta registrada. Pensado para que el partial del item
     * pueda hacer un link directo al objeto sin tener que
     * diferenciar tipos.
     *
     * Devuelve `null` si el sujeto ya no existe (borrado en
     * cascada) o si el modelo no tiene una ruta conocida en
     * esta fase. Asi el partial puede renderizar el item sin
     * link de forma segura.
     */
    public function subjectUrl(): ?string
    {
        $subject = $this->subject;

        if ($subject === null) {
            return null;
        }

        return match (true) {
            $subject instanceof Task => route('admin.projects.tasks.show', [$this->project_id, $subject->id], false),
            $subject instanceof ProjectDocument => $this->project_id !== null
                ? route('admin.projects.documents.show', [$this->project_id, $subject->id], false)
                : null,
            $subject instanceof CalendarEvent => $this->project_id !== null
                ? route('admin.projects.calendar', $this->project_id, false)
                : null,
            $subject instanceof ProjectMessage => $this->project_id !== null
                ? route('admin.projects.chat', $this->project_id, false)
                : null,
            default => null,
        };
    }

    /**
     * Resuelve la URL del sujeto en version portal. Para documentos
     * privados el portal no tiene ruta; en ese caso devuelve null
     * (el partial omitira el link).
     */
    public function portalSubjectUrl(): ?string
    {
        $subject = $this->subject;

        if ($subject === null || $this->project_id === null) {
            return null;
        }

        return match (true) {
            $subject instanceof Task => route('portal.projects.tasks.show', [$this->project_id, $subject->id], false),
            $subject instanceof ProjectDocument => $subject->visibility?->isPublic() === true
                ? route('portal.projects.documents.show', [$this->project_id, $subject->id], false)
                : null,
            $subject instanceof CalendarEvent => route('portal.projects.calendar', $this->project_id, false),
            $subject instanceof ProjectMessage => route('portal.projects.chat', $this->project_id, false),
            default => null,
        };
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Filtra por tipo de evento. Pensado para filtros finos
     * (`where('type', $type)`). El agrupado por categoria usa
     * `inCategory`.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeOfType(Builder $query, ActivityType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * Filtra por categoria logica. Acepta `'all'` (o string vacio)
     * como no-filtro, util para el chip "Todas" del filtro.
     *
     * Resuelve la lista de tipos que pertenecen a la categoria
     * y aplica un `whereIn`. El `IN` resultante esta acotado al
     * tamano de cada categoria (max 6 para `tasks`).
     *
     * @param  Builder<ActivityLog>  $query
     * @param  string  $category  clave de `ActivityType::categoryLabels()` sin 'all'
     * @return Builder<ActivityLog>
     */
    public function scopeInCategory(Builder $query, string $category): Builder
    {
        if ($category === '' || $category === 'all') {
            return $query;
        }

        $types = array_values(array_filter(
            ActivityType::cases(),
            fn (ActivityType $type) => $type->category() === $category,
        ));

        if ($types === []) {
            // Categoria desconocida: devolver query vacia en vez de
            // explotar. Asi un filtro manipulado en la URL no
            // rompe el feed.
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('type', array_map(fn (ActivityType $t) => $t->value, $types));
    }

    /**
     * Solo eventos visibles para el portal.
     *
     * Aplica dos filtros:
     * 1. El `isPublic()` del enum (categoria).
     * 2. Para `document_*` y `attachment_*`, exige que el
     *    `properties.visibility` sea `public`. Asi un documento
     *    privado que se creo no se filtra al portal aunque
     *    `DocumentCreated::isPublic()` sea true.
     *
     * Implementacion: usamos un solo `whereIn` con los tipos
     * publicos del enum, y luego un `where` anidado que para los
     * tipos de documento exige `visibility = public`. La logica
     * resultante es: incluir el evento si (su tipo es publico y
     * no es de documento) O (su tipo es de documento y su
     * visibility es public). Esto se traduce a una sola query
     * con un OR anidado.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopePublic(Builder $query): Builder
    {
        $publicTypes = array_values(array_filter(
            ActivityType::cases(),
            fn (ActivityType $t) => $t->isPublic(),
        ));

        $publicValues = array_map(fn (ActivityType $t) => $t->value, $publicTypes);

        $documentTypes = array_values(array_map(
            fn (ActivityType $t) => $t->value,
            array_filter($publicTypes, fn (ActivityType $t) => str_starts_with($t->value, 'document_')),
        ));

        $attachmentTypes = array_values(array_map(
            fn (ActivityType $t) => $t->value,
            array_filter($publicTypes, fn (ActivityType $t) => $t === ActivityType::AttachmentUploadedToTask),
        ));

        // Tipos que no son documento ni attachment: pasan directo.
        $nonDocumentTypes = array_values(array_filter(
            $publicValues,
            fn (string $v) => ! str_starts_with($v, 'document_') && ! in_array($v, $attachmentTypes, true),
        ));

        $query->where(function (Builder $q) use ($nonDocumentTypes, $documentTypes, $attachmentTypes): void {
            // 1. Tipos no-documento: siempre incluidos.
            if ($nonDocumentTypes !== []) {
                $q->whereIn('type', $nonDocumentTypes);
            }

            // 2. Adjuntos: solo se incluye el archivo publico. Un
            //    attachment siempre lleva `properties.visibility`
            //    asi que se filtra igual que los documentos.
            if ($attachmentTypes !== []) {
                $q->orWhere(function (Builder $qa) use ($attachmentTypes): void {
                    $qa->whereIn('type', $attachmentTypes)
                        ->where('properties->visibility', 'public');
                });
            }

            // 3. Documentos: solo se incluye el que es publico.
            if ($documentTypes !== []) {
                $q->orWhere(function (Builder $qb) use ($documentTypes): void {
                    $qb->whereIn('type', $documentTypes)
                        ->where('properties->visibility', 'public');
                });
            }

            // 4. Si por algun motivo no hay ningun tipo a incluir
            //    (caso degenerado), devolvemos query vacia.
            if ($nonDocumentTypes === [] && $attachmentTypes === [] && $documentTypes === []) {
                $q->whereRaw('1 = 0');
            }
        });

        return $query;
    }

    /**
     * Solo eventos NO visibles para el portal (admin-only). Es la
     * negacion de `public` para queries del panel de admin sin
     * filtro de visibilidad.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopePrivate(Builder $query): Builder
    {
        $publicTypes = array_map(
            fn (ActivityType $t) => $t->value,
            array_filter(ActivityType::cases(), fn (ActivityType $t) => $t->isPublic()),
        );

        return $query->whereNotIn('type', $publicTypes);
    }

    /**
     * Limita los eventos al proyecto indicado.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeForProject(Builder $query, Project $project): Builder
    {
        return $query->where('project_id', $project->id);
    }

    /**
     * Limita los eventos a la organizacion indicada.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeForOrganization(Builder $query, Organization $organization): Builder
    {
        return $query->where('organization_id', $organization->id);
    }

    /**
     * Orden cronologico descendente: los mas recientes primero.
     * Es el orden por defecto del feed.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderByDesc('id');
    }

    /**
     * Limita a los N ultimos eventos (por id descendente). Pensado
     * para el render inicial del feed antes de que el usuario
     * pulse "Cargar mas".
     *
     * @param  Builder<ActivityLog>  $query
     * @param  int  $limit
     * @return Builder<ActivityLog>
     */
    public function scopeRecent(Builder $query, int $limit = 20): Builder
    {
        return $query->orderByDesc('id')->limit($limit);
    }

    /**
     * Eventos con id estrictamente menor al indicado. Util para
     * "Cargar entradas anteriores" (paginacion keyset).
     *
     * @param  Builder<ActivityLog>  $query
     * @param  int  $id
     * @return Builder<ActivityLog>
     */
    public function scopeBeforeId(Builder $query, int $id): Builder
    {
        return $query->where('id', '<', $id);
    }
}
