<?php

namespace App\Livewire\Admin\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Gestion de miembros asignados a un proyecto.
 *
 * Reune en una sola pieza la lista actual, el selector de miembros
 * disponibles (filtrado para que solo aparezcan usuarios que ya
 * pertenecen a la organizacion) y la accion de anadir/quitar. La
 * autorizacion se delega al backend (rutas anidadas), pero el
 * componente revalida `manageMembers` para evitar llamadas si el
 * usuario perdio permisos.
 */
class ProjectMembers extends Component
{
    use AuthorizesRequests;

    public Project $project;

    /**
     * Listado de miembros de la organizacion que aun no estan en el
     * proyecto. Se recibe desde la vista show para evitar una query
     * extra en cada render.
     *
     * @var Collection<int, User>
     */
    public Collection $availableMembers;

    /**
     * ID del usuario a anadir. Se valida que exista y que sea
     * miembro de la organizacion en el backend.
     */
    #[Validate('required|integer|exists:users,id')]
    public ?int $userIdToAdd = null;

    /**
     * Inicializa el componente con el proyecto y los miembros
     * disponibles.
     */
    public function mount(Project $project, Collection $availableMembers): void
    {
        $this->project = $project;
        $this->availableMembers = $availableMembers;
    }

    /**
     * Anade el usuario seleccionado al proyecto. Despues de la
     * accion recarga la lista de miembros disponibles para que
     * desaparezca del selector.
     */
    public function addMember(): void
    {
        $this->authorize('manageMembers', $this->project);

        $this->validate();

        $userId = (int) $this->userIdToAdd;

        $belongsToOrg = $this->project->organization
            ->members()
            ->where('users.id', $userId)
            ->exists();

        if (! $belongsToOrg) {
            $this->addError('userIdToAdd', 'El usuario debe pertenecer a la organizacion del proyecto.');

            return;
        }

        if (! $this->project->members()->where('users.id', $userId)->exists()) {
            $this->project->members()->attach($userId);
        }

        $this->reset('userIdToAdd');
        $this->refreshAvailableMembers();

        session()->flash('status', 'Miembro anadido al proyecto.');
    }

    /**
     * Quita un miembro del proyecto. Se refresca la lista de
     * disponibles para que vuelva a aparecer en el selector.
     */
    public function removeMember(int $userId): void
    {
        $this->authorize('manageMembers', $this->project);

        $this->project->members()->detach($userId);

        $this->refreshAvailableMembers();

        session()->flash('status', 'Miembro retirado del proyecto.');
    }

    /**
     * Recalcula la coleccion de miembros disponibles a partir del
     * estado actual. Lo usamos despues de cualquier cambio para
     * que la UI este sincronizada sin recargar la pagina.
     */
    private function refreshAvailableMembers(): void
    {
        $this->project->load('organization.members', 'members');

        $this->availableMembers = $this->project->organization
            ->members
            ->diff($this->project->members)
            ->values();
    }

    /**
     * Renderiza la vista con los miembros actuales.
     */
    public function render(): \Illuminate\View\View
    {
        $this->project->load('members');

        return view('livewire.admin.project.project-members', [
            'project' => $this->project,
            'members' => $this->project->members,
        ]);
    }
}
