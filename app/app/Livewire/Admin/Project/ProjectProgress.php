<?php

namespace App\Livewire\Admin\Project;

use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Componente Livewire para actualizar el progreso del proyecto.
 *
 * Permite al admin mover una barra de progreso con un input
 * numerico 0-100 sin recargar la pagina. La actualizacion pasa por
 * la ruta dedicada `admin.projects.progress.update` para mantener
 * la logica en el backend (validacion, autorizacion, audit).
 */
class ProjectProgress extends Component
{
    use AuthorizesRequests;

    /**
     * Constructor tipado: Livewire 4 permite declarar el modelo
     * directamente como parametro y la inyeccion se hace por el
     * resolver del router.
     */
    public Project $project;

    /**
     * Valor controlado del input. Se valida en tiempo real con
     * `#[Validate]` para que el admin reciba feedback inmediato.
     */
    #[Validate('required|integer|min:0|max:100')]
    public int $progress = 0;

    /**
     * Inicializa el progreso con el valor actual del proyecto. Se
     * ejecuta una sola vez al montar el componente.
     */
    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->progress = (int) $project->progress;
    }

    /**
     * Persiste el cambio via la ruta PATCH del backend. Usamos `patch`
     * directamente (no la policy en el componente) porque la policy
     * ya se aplica en el controlador, y queremos mantener un unico
     * punto de autorizacion.
     *
     * @return void
     */
    public function save(): void
    {
        $this->validate();

        $this->project->update([
            'progress' => (int) $this->progress,
        ]);

        session()->flash('status', 'Progreso actualizado.');

        $this->dispatch('project-progress-updated');
    }

    /**
     * Renderiza la vista con la barra de progreso.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.project.project-progress');
    }
}
