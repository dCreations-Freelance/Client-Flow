<?php

namespace App\DTOs\Project;

use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

/**
 * Snapshot inmutable de los datos que necesita la pagina de detalle
 * de un proyecto (admin y portal).
 *
 * La vista no debe lanzar queries: todo lo que renderiza llega aqui
 * precalculado por `ProjectSummaryService`. Asi el show queda
 * declarativo y se puede cachear o reusar en tests sin levantar
 * el modelo en distintas formas.
 */
final class ProjectSummary
{
    /**
     * @param  Collection<int, BoardColumnPreview>  $boardPreview  mini-kanban con hasta 3 tareas por columna
     * @param  Collection<int, ProjectDocument>  $previewDocuments  documentos recientes (todos para admin, publicos para portal)
     * @param  Collection<int, User>  $previewMembers  hasta 8 usuarios para el grid de avatares
     * @param  array<string, int>  $columnCounts  mapa slug de columna => total de tareas raiz en esa columna
     */
    public function __construct(
        public readonly Project $project,
        public readonly User $viewer,
        public readonly int $unreadMessages,
        public readonly ?Carbon $nextDelivery,
        public readonly string $nextDeliveryLabel,
        public readonly string $nextDeliveryTone,
        public readonly int $totalMembers,
        public readonly Collection $previewMembers,
        public readonly Collection $boardPreview,
        public readonly array $columnCounts,
        public readonly Collection $previewDocuments,
        public readonly ?ProjectMessage $latestMessage,
        public readonly ?CalendarEvent $nextEvent,
        public readonly int $totalMessages,
        public readonly int $totalDocuments,
        public readonly int $totalLoggedMinutes = 0,
    ) {}

    /**
     * Area desde la que se esta visualizando el proyecto. Lo usa
     * la vista para alternar copy y acciones (admin vs portal).
     */
    public function area(): string
    {
        return $this->viewer->isAdmin() ? 'admin' : 'portal';
    }
}
