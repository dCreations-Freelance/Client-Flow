<?php

namespace App\Services\Project;

use App\DTOs\Project\BoardColumnPreview;
use App\DTOs\Project\ProjectSummary;
use App\Enums\DocumentVisibility;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\ProjectChatRead;
use App\Models\ProjectMessage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Prepara el snapshot de datos que renderiza la pagina de detalle
 * de un proyecto (admin y portal).
 *
 * Centraliza la carga eager de relaciones y los calculos que antes
 * estaban duplicados en los controladores (mensajes sin leer, fecha
 * de entrega "humana", mini-kanban, etc.). Asi la vista queda
 * declarativa y el coste en queries es controlado: solo se cargan
 * los datos que se van a mostrar, con `loadCount` para los totales.
 */
class ProjectSummaryService
{
    /**
     * Numero maximo de columnas que se muestran en el preview del
     * kanban del hub. Cuatro coincide con las columnas por defecto
     * del proyecto y cabe en una fila desktop sin scroll.
     */
    private const BOARD_PREVIEW_COLUMNS = 4;

    /**
     * Numero de tareas raiz que se muestran por columna en el
     * preview. Suficiente para que el cliente vea "hay movimiento"
     * sin saturar la pagina.
     */
    private const BOARD_PREVIEW_TASKS_PER_COLUMN = 3;

    /**
     * Numero de documentos que se muestran en el preview lateral.
     */
    private const DOCUMENT_PREVIEW_LIMIT = 3;

    /**
     * Numero de miembros que se muestran en el grid de avatares.
     * Si hay mas se rendera un "+N" al final.
     */
    private const MEMBER_PREVIEW_LIMIT = 8;

    /**
     * Prepara el snapshot para el panel de administracion.
     * Carga todos los datos (incluidos documentos privados y
     * miembros completos) sin aplicar filtros de visibilidad.
     *
     * @return ProjectSummary
     */
    public function loadForAdmin(Project $project, User $viewer): ProjectSummary
    {
        $project = $this->loadProjectRelations($project, includePrivateDocuments: true);

        return $this->buildSummary($project, $viewer, includePrivateDocuments: true);
    }

    /**
     * Prepara el snapshot para el portal del cliente. Aplica los
     * filtros de visibilidad: solo documentos publicos y solo
     * miembros de la organizacion que el cliente puede ver.
     *
     * @return ProjectSummary
     */
    public function loadForPortal(Project $project, User $viewer): ProjectSummary
    {
        $project = $this->loadProjectRelations($project, includePrivateDocuments: false);

        return $this->buildSummary($project, $viewer, includePrivateDocuments: false);
    }

    /**
     * Carga las relaciones comunes al proyecto y aplica el filtro
     * de visibilidad de documentos segun el area. Los `loadCount`
     * se usan para los totales que se muestran en badges o tiles
     * sin tener que cargar la coleccion completa.
     *
     * Para el portal, anadimos un `public_documents_count`
     * adicional (filtrado por visibilidad) via `loadCount` con
     * closure, evitando una query extra posterior. El admin
     * consume `documents_count` (total) y el portal
     * `public_documents_count` (filtrado).
     *
     * @return Project
     */
    private function loadProjectRelations(Project $project, bool $includePrivateDocuments): Project
    {
        $project->loadMissing([
            'organization',
            'members',
        ]);

        $counts = [
            'members',
            'messages',
            'documents',
            'calendarEvents',
            'rootTasks',
        ];

        if (! $includePrivateDocuments) {
            $counts['documents as public_documents_count'] = function ($query): void {
                $query->where('visibility', DocumentVisibility::Public->value);
            };
        }

        $project->loadCount($counts);

        return $project;
    }

    /**
     * Compone el DTO a partir del proyecto ya cargado con sus
     * contadores. Aqui se ejecutan las queries restantes (previews,
     * ultimo mensaje, proximo evento) que son pequenas y siempre
     * se necesitan.
     */
    private function buildSummary(
        Project $project,
        User $viewer,
        bool $includePrivateDocuments,
    ): ProjectSummary {
        $boardPreview = $this->boardPreview($project);
        $columnCounts = $this->columnCounts($project);

        $documentsQuery = $project->documents()->with('creator')->recent();
        if (! $includePrivateDocuments) {
            $documentsQuery->public();
        }
        $previewDocuments = $documentsQuery->limit(self::DOCUMENT_PREVIEW_LIMIT)->get();

        $latestMessage = $project->messages()
            ->with('user')
            ->where('type', '!=', 'system')
            ->orderByDesc('id')
            ->first();

        $nextEvent = $project->calendarEvents()
            ->upcoming(1)
            ->first();

        $previewMembers = $project->members
            ->sortBy('name')
            ->take(self::MEMBER_PREVIEW_LIMIT)
            ->values();

        $unreadMessages = $this->unreadCount($project, $viewer);

        [$nextDelivery, $nextDeliveryLabel, $nextDeliveryTone] = $this->nextDelivery($project);

        return new ProjectSummary(
            project: $project,
            viewer: $viewer,
            unreadMessages: $unreadMessages,
            nextDelivery: $nextDelivery,
            nextDeliveryLabel: $nextDeliveryLabel,
            nextDeliveryTone: $nextDeliveryTone,
            totalMembers: (int) $project->members_count,
            previewMembers: $previewMembers,
            boardPreview: $boardPreview,
            columnCounts: $columnCounts,
            previewDocuments: $previewDocuments,
            latestMessage: $latestMessage,
            nextEvent: $nextEvent,
            totalMessages: (int) $project->messages_count,
            totalDocuments: $includePrivateDocuments
                ? (int) $project->documents_count
                : (int) ($project->public_documents_count ?? 0),
        );
    }

    /**
     * Construye el preview del kanban: hasta 4 columnas con sus 3
     * primeras tareas raiz cargadas. Se hace en una sola query por
     * columna (no N+1 sobre el assignee) usando `with`.
     *
     * @return Collection<int, BoardColumnPreview>
     */
    private function boardPreview(Project $project): Collection
    {
        $columns = $project->columns()
            ->ordered()
            ->limit(self::BOARD_PREVIEW_COLUMNS)
            ->get();

        $taskIds = BoardColumn::query()
            ->where('project_id', $project->id)
            ->ordered()
            ->limit(self::BOARD_PREVIEW_COLUMNS)
            ->pluck('id');

        // Cargamos las tareas raiz en una sola query agrupada por
        // columna, ordenadas por position. Esto evita N+1 cuando
        // se rendericen los 4 mini-boards a la vez.
        $tasksByColumn = Task::query()
            ->with('assignee')
            ->whereIn('column_id', $taskIds)
            ->whereNull('parent_id')
            ->ordered()
            ->get()
            ->groupBy('column_id');

        return $columns->map(function (BoardColumn $column) use ($tasksByColumn): BoardColumnPreview {
            $tasks = $tasksByColumn->get($column->id, collect());

            return new BoardColumnPreview(
                column: $column,
                previewTasks: $tasks->take(self::BOARD_PREVIEW_TASKS_PER_COLUMN)->values(),
            );
        });
    }

    /**
     * Mapa con el total de tareas raiz por cada slug de columna
     * del proyecto. La vista lo usa para mostrar "(5)" junto al
     * nombre de la columna aunque solo renderice 3 tareas.
     *
     * Se calcula con una sola query agrupada por slug (no N+1)
     * para mantener el numero de queries del show acotado. Las
     * columnas sin tareas aparecen en el mapa con valor 0 para
     * que la vista no tenga que tratar el caso null.
     *
     * @return array<string, int>
     */
    private function columnCounts(Project $project): array
    {
        $columns = $project->columns()->ordered()->get();

        $counts = Task::query()
            ->selectRaw('board_columns.slug as slug, COUNT(tasks.id) as total')
            ->join('board_columns', 'board_columns.id', '=', 'tasks.column_id')
            ->where('board_columns.project_id', $project->id)
            ->whereNull('tasks.parent_id')
            ->groupBy('board_columns.slug')
            ->pluck('total', 'slug')
            ->all();

        return $columns
            ->mapWithKeys(fn (BoardColumn $column) => [$column->slug => (int) ($counts[$column->slug] ?? 0)])
            ->all();
    }

    /**
     * Cuenta los mensajes no leidos del usuario en este proyecto.
     * Si nunca ha abierto el chat, todos cuentan como no leidos.
     */
    private function unreadCount(Project $project, User $viewer): int
    {
        $read = ProjectChatRead::query()
            ->where('project_id', $project->id)
            ->where('user_id', $viewer->id)
            ->value('last_read_message_id');

        $query = ProjectMessage::query()->where('project_id', $project->id);
        if ($read !== null) {
            $query->where('id', '>', (int) $read);
        }

        return $query->count();
    }

    /**
     * Calcula la "proxima entrega" humana para el tile de resumen.
     * Devuelve la fecha nula si el proyecto no tiene
     * `estimated_ends_at`; en caso contrario, una etiqueta
     * localizada al castellano con tono semantico.
     *
     * Importante: en Carbon 3.x `diffInDays` devuelve un valor con
     * signo (positivo si el segundo argumento es pasado, negativo
     * si es futuro). Usamos el flag `absolute = true` para que el
     * calculo sea siempre positivo, evitando que un proyecto
     * vencido se muestre como "Retrasada -5 dias".
     *
     * @return array{0: ?Carbon, 1: string, 2: string}
     */
    private function nextDelivery(Project $project): array
    {
        $date = $project->estimated_ends_at;
        if ($date === null) {
            return [null, 'Sin fecha definida', 'neutral'];
        }

        $isCompleted = $project->status === \App\Enums\ProjectStatus::Completed;
        $today = Carbon::today();

        if ($isCompleted) {
            return [$date, 'Entregado el '.$date->format('d/m/Y'), 'success'];
        }

        if ($date->lessThan($today)) {
            $days = (int) $today->diffInDays($date, true);

            return [$date, 'Retrasada '.($days === 1 ? '1 dia' : $days.' dias'), 'danger'];
        }

        if ($date->isSameDay($today)) {
            return [$date, 'Para hoy', 'warning'];
        }

        $days = (int) $today->diffInDays($date, true);
        $word = $days === 1 ? 'dia' : 'dias';

        return [$date, 'En '.$days.' '.$word, 'warning'];
    }
}
