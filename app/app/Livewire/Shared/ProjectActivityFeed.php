<?php

namespace App\Livewire\Shared;

use App\Enums\ActivityType;
use App\Models\Project;
use App\Models\User;
use App\Services\Activity\ProjectActivityFeedService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Componente Livewire compartido del feed de actividad de un
 * proyecto.
 *
 * Es el mismo componente para admin y portal: la diferencia
 * (autorizacion, layout, modo de visibilidad) la aplican los
 * controladores que lo montan. Esto evita duplicar la logica
 * de paginacion, filtros y renderizado del feed.
 *
 * Comportamiento:
 *
 * - Carga los 20 eventos mas recientes al inicio. El usuario
 *   puede pulsar "Cargar entradas anteriores" para traer 20 mas
 *   (paginacion keyset via `beforeId`).
 * - Filtra por categoria (Todas, Tareas, Documentos, Eventos,
 *   Mensajes, Proyecto, Miembros). La categoria se persiste en
 *   la query string para que la URL sea compartible.
 * - En modo portal, aplica el filtro fino de eventos publicos
 *   via el scope `public` del modelo. El admin ve todos.
 * - Sin polling: el feed es historico, no necesita real-time.
 *   La parte reactiva (mensajes nuevos) la cubre el chat con
 *   su propio polling cada 5s.
 *
 * La autorizacion se hace en `mount` contra `ProjectPolicy::view`
 * (no contra `ActivityLogPolicy`) para mantener un unico punto
 * de entrada. Asi si en una fase futura se afina la policy
 * del proyecto, el feed hereda el cambio automaticamente.
 */
class ProjectActivityFeed extends Component
{
    use AuthorizesRequests;

    public Project $project;

    /**
     * Usuario autenticado, capturado en mount.
     */
    public User $user;

    /**
     * Modo de operacion: `false` para admin (ve todos los eventos),
     * `true` para portal (solo publicos). El controlador que monta
     * el componente lo pasa como parametro.
     */
    public bool $portalMode = false;

    /**
     * Categoria activa del filtro. Persistida en la URL con
     * `#[Url]` para que sea compartible. `all` significa
     * "sin filtro" (es el comportamiento por defecto del
     * chip "Todas" en la UI).
     */
    #[Url(as: 'c')]
    public string $category = 'all';

    /**
     * Numero de entradas cargadas en pantalla. Se incrementa
     * con "Cargar entradas anteriores" en saltos de 20.
     */
    public int $loadedCount = 20;

    /**
     * Servicio de carga y conteo. Se resuelve por el container
     * en cada peticion (lazy) en vez de inyectarse en el
     * constructor, para mantener compatibilidad con el ciclo
     * de hidratacion de Livewire (los servicios con dependencias
     * inyectadas en el constructor requieren un setup mas
     * elaborado en tests).
     */
    private function feedService(): ProjectActivityFeedService
    {
        return app(ProjectActivityFeedService::class);
    }

    /**
     * Inicializa el componente. La autorizacion la hacemos
     * contra el proyecto, no contra cada entrada del feed:
     * cargar la policy de cada ActivityLog seria O(n) en cada
     * re-render, y la policy del proyecto es la fuente de
     * verdad para el acceso al feed.
     */
    public function mount(
        Project $project,
        bool $portalMode = false,
    ): void {
        $this->project = $project;
        $this->user = Auth::user();
        $this->portalMode = $portalMode;

        $this->authorize('view', $project);
    }

    // -----------------------------------------------------------------
    // Propiedades computadas (cacheadas por Livewire)
    // -----------------------------------------------------------------

    /**
     * Entradas actualmente cargadas. Llama al servicio con
     * el filtro de categoria y el modo (admin/portal) actuales.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityLog>
     */
    public function getEntriesProperty()
    {
        return $this->feedService()->load(
            project: $this->project,
            portalMode: $this->portalMode,
            category: $this->category,
            limit: $this->loadedCount,
        );
    }

    /**
     * Conteo de eventos por categoria, para pintar los chips
     * con numeros entre parentesis. Alimenta la decision
     * "mostrar / ocultar chip" segun si hay eventos.
     *
     * @return array<string, int>
     */
    public function getCountsProperty(): array
    {
        return $this->feedService()->countsByCategory(
            $this->project,
            $this->portalMode,
        );
    }

    /**
     * Total de entradas que coinciden con el filtro (modo +
     * categoria), sin limite de paginacion. Usado para
     * decidir si mostrar "Cargar entradas anteriores".
     */
    public function getTotalProperty(): int
    {
        return $this->feedService()->totalCount(
            $this->project,
            $this->portalMode,
            $this->category,
        );
    }

    /**
     * Mapa de categorias a etiquetas, consumido por la vista
     * para renderizar los chips. Delega en el enum para
     * que traducion y agregacion vivan en el mismo sitio.
     *
     * @return array<string, string>
     */
    public function getCategoryLabelsProperty(): array
    {
        return ActivityType::categoryLabels();
    }

    // -----------------------------------------------------------------
    // Acciones del componente
    // -----------------------------------------------------------------

    /**
     * Cambia la categoria activa. Resetea `loadedCount` para
     * que la paginacion vuelva al inicio; si no, el usuario
     * podria quedarse con "20 entradas de la categoria A"
     * aplicadas al filtro B, mostrando un subset inconsistente.
     */
    public function setCategory(string $category): void
    {
        if (! array_key_exists($category, $this->categoryLabels)) {
            $category = 'all';
        }

        $this->category = $category;
        $this->loadedCount = ProjectActivityFeedService::DEFAULT_PAGE_SIZE;
    }

    /**
     * Incrementa `loadedCount` para traer 20 entradas mas.
     * Limpiamos errores previos por si el componente se reusa
     * despues de un error de carga (defensa).
     */
    public function loadMore(): void
    {
        $this->loadedCount += ProjectActivityFeedService::LOAD_MORE_STEP;
    }

    // -----------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------

    /**
     * Render principal. Pasamos al view los datos que necesita
     * sin que tenga que conocer el servicio.
     */
    public function render(): View
    {
        return view('livewire.shared.project-activity-feed', [
            'entries' => $this->entries,
            'counts' => $this->counts,
            'total' => $this->total,
            'categoryLabels' => $this->categoryLabels,
            'hasMore' => $this->total > $this->loadedCount,
        ]);
    }
}
