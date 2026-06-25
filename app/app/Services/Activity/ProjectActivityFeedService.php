<?php

namespace App\Services\Activity;

use App\Enums\ActivityType;
use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio que prepara los datos del feed de actividad para
 * el componente Livewire y los controladores.
 *
 * Centraliza dos responsabilidades:
 *
 *  1. `countsByCategory`: numero de eventos por categoria
 *     (Tareas, Documentos, Eventos, ...) que se usan para
 *     pintar los chips del filtro. Se calcula con una sola
 *     query agregada en PHP (no se puede hacer un GROUP BY
 *     directamente porque la categoria se deriva de un enum,
 *     no de una columna).
 *
 *  2. `load`: query que devuelve los N eventos mas recientes
 *     del proyecto, opcionalmente filtrados por categoria y
 *     por modo (admin: todos, portal: solo publicos).
 *
 * Mantener esta logica fuera del componente Livewire permite
 * testearla sin levantar Livewire, y deja el componente como
 * una capa fina de UI + estado (filtros, paginacion).
 */
class ProjectActivityFeedService
{
    /**
     * Numero por defecto de entradas a cargar en el feed. Lo
     * usa el componente Livewire como punto de partida antes
     * de que el usuario pulse "Cargar mas".
     */
    public const DEFAULT_PAGE_SIZE = 20;

    /**
     * Incremento de entradas por cada "Cargar mas". Mismo
     * patron que el chat (saltos de 50); aqui usamos 20 para
     * que el feed no pida bloques enormes de golpe.
     */
    public const LOAD_MORE_STEP = 20;

    /**
     * Calcula el conteo de eventos por categoria para un proyecto,
     * respetando el modo (admin o portal).
     *
     * Para el modo portal se filtran los eventos privados. Para
     * el modo admin se cuentan todos.
     *
     * @return array<string, int>  mapa categoria => total (incluye 'all' con la suma)
     */
    public function countsByCategory(Project $project, bool $portalMode = false): array
    {
        $query = ActivityLog::query()->forProject($project);

        if ($portalMode) {
            $query->public();
        }

        // Cargamos solo `type` (string pequeno) y hacemos el
        // agrupado en PHP. Para proyectos tipicos (<200 eventos)
        // es mas rapido y simple que un `GROUP BY` + `CASE WHEN`.
        // `pluck('type')` devuelve el enum gracias al cast del
        // modelo; lo convertimos a su valor para usar `tryFrom`.
        $types = $query->pluck('type');

        $counts = array_fill_keys(array_keys(ActivityType::categoryLabels()), 0);

        foreach ($types as $type) {
            // Puede ser una instancia del enum (con cast) o un
            // string (sin cast). Normalizamos a ActivityType.
            $enum = $type instanceof ActivityType
                ? $type
                : ActivityType::tryFrom((string) $type);

            if ($enum === null) {
                continue;
            }

            $counts[$enum->category()] = ($counts[$enum->category()] ?? 0) + 1;
        }

        // `all` es la suma de las demas categorias. Asi el chip
        // "Todas" cuadra siempre con el total visible.
        $counts['all'] = array_sum(array_filter(
            $counts,
            fn (string $key): bool => $key !== 'all',
            ARRAY_FILTER_USE_KEY,
        ));

        return $counts;
    }

    /**
     * Carga las entradas del feed para un proyecto, aplicando
     * el filtro de categoria y el modo (admin/portal).
     *
     * El orden es cronologico descendente: las mas recientes
     * primero. Si `$beforeId` se indica, se cargan las entradas
     * con `id < $beforeId` (paginacion keyset para "Cargar mas").
     *
     * @return Collection<int, ActivityLog>
     */
    public function load(
        Project $project,
        bool $portalMode = false,
        string $category = 'all',
        int $limit = self::DEFAULT_PAGE_SIZE,
        ?int $beforeId = null,
    ): Collection {
        $query = $this->baseQuery($project, $portalMode, $category);

        if ($beforeId !== null) {
            $query->beforeId($beforeId);
        }

        return $query
            ->recent($limit)
            ->with('user', 'subject')
            ->get();
    }

    /**
     * Cuenta el total de entradas que se mostrarian en el feed
     * (modo + categoria aplicados, sin limite de paginacion).
     *
     * Se usa para decidir si mostrar el boton "Cargar mas":
     * `total > loadedCount`.
     */
    public function totalCount(
        Project $project,
        bool $portalMode = false,
        string $category = 'all',
    ): int {
        return $this->baseQuery($project, $portalMode, $category)->count();
    }

    /**
     * Query base compartida por `load` y `totalCount`. Encapsula
     * el filtro de proyecto + modo + categoria para que ambos
     * metodos devuelvan resultados consistentes.
     */
    private function baseQuery(
        Project $project,
        bool $portalMode,
        string $category,
    ): Builder {
        $query = ActivityLog::query()->forProject($project);

        if ($portalMode) {
            $query->public();
        }

        if ($category !== '' && $category !== 'all') {
            $query->inCategory($category);
        }

        return $query;
    }
}
