<?php

namespace App\Livewire\Admin\Document;

use App\Enums\DocumentVisibility;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Editor markdown de documentos con preview en vivo.
 *
 * Reune en un solo componente la gestion del estado del formulario
 * (titulo, contenido markdown, visibilidad) y la logica de guardado.
 * Soporta dos modos:
 * - `create`: se instancia pasando solo el proyecto; persiste un
 *   documento nuevo con `created_by = usuario actual`.
 * - `edit`: se instancia pasando proyecto y documento existente;
 *   precarga los campos y aplica `update()`.
 *
 * La interaccion con la API del DOM (posicion del cursor, longitud
 * de la seleccion) se hace via el JS minimo de la vista, que llama
 * a metodos del componente via `wire:click`. Sin librerias externas,
 * sin Alpine.js.
 */
class DocumentEditor extends Component
{
    use AuthorizesRequests;

    /**
     * Modo del componente: 'create' o 'edit'. Determina la accion
     * del boton guardar y el titulo del formulario.
     */
    public string $mode = 'create';

    public Project $project;

    /**
     * Documento que se esta editando. Es null en modo create.
     */
    public ?ProjectDocument $document = null;

    /**
     * Estado del formulario.
     */
    public string $title = '';

    public string $content = '';

    public string $visibility = 'private';

    /**
     * Tab activa: 'editor' o 'preview'. Mantenerla en estado del
     * componente (no en URL) para que el switch sea instantaneo.
     */
    public string $activeTab = 'editor';

    /**
     * Flag para evitar que `updatedContent` se dispare durante el
     * `mount` inicial y muestre el badge de cambios sin guardar.
     */
    public bool $initialized = false;

    /**
     * Inicializa el componente.
     *
     * - En modo `create` exige autorizacion `create` y deja el
     *   formulario con valores por defecto (titulo y contenido
     *   vacios, visibilidad `private`).
     * - En modo `edit` exige autorizacion `update` y precarga los
     *   datos del documento existente.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectDocument|null  $document
     */
    public function mount(Project $project, ?ProjectDocument $document = null): void
    {
        $this->project = $project;

        if ($document !== null) {
            $this->authorize('update', $document);
            $this->mode = 'edit';
            $this->document = $document;
            $this->title = $document->title;
            $this->content = $document->content ?? '';
            $this->visibility = $document->visibility?->value
                ?? DocumentVisibility::Private->value;
        } else {
            $this->authorize('create', [ProjectDocument::class, $project]);
            $this->mode = 'create';
            $this->document = null;
        }

        // Marcamos como inicializado al final para que los setters
        // que se disparen durante mount no activen efectos laterales.
        $this->initialized = true;
    }

    /**
     * Validador reactivo: se invoca desde `save()` y desde
     * `Livewire::validate` en cualquier momento. Centralizamos las
     * reglas para no duplicarlas.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'content' => ['required', 'string', 'min:1', 'max:100000'],
            'visibility' => ['required', 'in:'.DocumentVisibility::Private->value.','.DocumentVisibility::Public->value],
        ];
    }

    /**
     * Mensajes en castellano para los errores de validacion.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'title.required' => 'Introduce un titulo para el documento.',
            'title.min' => 'El titulo es demasiado corto.',
            'title.max' => 'El titulo es demasiado largo.',
            'content.required' => 'El contenido del documento no puede estar vacio.',
            'content.max' => 'El documento excede el tamano maximo permitido.',
            'visibility.required' => 'Selecciona una visibilidad.',
            'visibility.in' => 'La visibilidad seleccionada no es valida.',
        ];
    }

    /**
     * Cambia la tab activa. Llamado desde los botones del tab.
     *
     * @param  string  $tab  'editor' o 'preview'
     */
    public function setTab(string $tab): void
    {
        if (in_array($tab, ['editor', 'preview'], true)) {
            $this->activeTab = $tab;
        }
    }

    /**
     * Inserta un fragmento de markdown en la posicion actual del
     * cursor. Pensado para los botones de la toolbar. Si `selection`
     * viene informado (longitud > 0) se reemplaza la seleccion.
     *
     * @param  string  $snippet  texto a insertar (puede ser markdown)
     * @param  string  $selection  texto actualmente seleccionado
     * @param  int  $start  posicion inicial de la seleccion
     * @param  int  $end  posicion final de la seleccion
     */
    #[On('editor-insert')]
    public function insertFromToolbar(string $snippet, string $selection, int $start, int $end): void
    {
        $before = mb_substr($this->content, 0, $start);
        $after = mb_substr($this->content, $end);
        $this->content = $before.$snippet.$after;

        // Re-emitimos un evento para que la vista reposicione el
        // cursor tras la insercion (lo hace el listener de la vista).
        $this->dispatch('editor-content-updated', caret: $start + mb_strlen($snippet));
    }

    /**
     * Envoltorio para selecciones: el JS envia `before`, `selection`
     * y `after` para reconstruir el contenido. La vista lo invoca
     * desde el listener `editor-wrap`.
     *
     * @param  string  $before  texto a colocar antes de la seleccion
     * @param  string  $after  texto a colocar despues de la seleccion
     * @param  string  $selection  texto actualmente seleccionado
     * @param  int  $start  posicion inicial de la seleccion
     * @param  int  $end  posicion final de la seleccion
     */
    #[On('editor-wrap')]
    public function wrapSelection(string $before, string $after, string $selection, int $start, int $end): void
    {
        $prefix = mb_substr($this->content, 0, $start);
        $suffix = mb_substr($this->content, $end);
        $this->content = $prefix.$before.$selection.$after.$suffix;

        $caret = $start + mb_strlen($before) + mb_strlen($selection) + mb_strlen($after);
        $this->dispatch('editor-content-updated', caret: $caret);
    }

    /**
     * Persiste el documento. Se delega en la policy de
     * `ProjectDocument` y se aplican las mismas reglas que el
     * Form Request para mantener la consistencia entre la API
     * tradicional y la API Livewire.
     */
    public function save(): void
    {
        $this->validate();

        if ($this->mode === 'create') {
            $this->authorize('create', [ProjectDocument::class, $this->project]);

            $created = ProjectDocument::create([
                'project_id' => $this->project->id,
                'title' => trim($this->title),
                'content' => $this->content,
                'visibility' => $this->visibility,
                'created_by' => Auth::id(),
            ]);

            session()->flash('status', 'Documento creado.');

            $this->redirectRoute('admin.projects.documents.show', [
                'project' => $this->project,
                'document' => $created,
            ]);
        } else {
            $this->authorize('update', $this->document);

            $this->document->update([
                'title' => trim($this->title),
                'content' => $this->content,
                'visibility' => $this->visibility,
            ]);

            session()->flash('status', 'Documento actualizado.');

            $this->redirectRoute('admin.projects.documents.show', [
                'project' => $this->project,
                'document' => $this->document,
            ]);
        }
    }

    /**
     * Cancela la edicion. En modo `create` vuelve al listado; en
     * modo `edit` vuelve al detalle del documento sin guardar.
     */
    public function cancel(): void
    {
        if ($this->mode === 'create') {
            $this->redirectRoute('admin.projects.documents.index', [
                'project' => $this->project,
            ]);
        } else {
            $this->redirectRoute('admin.projects.documents.show', [
                'project' => $this->project,
                'document' => $this->document,
            ]);
        }
    }

    /**
     * Renderiza la vista del editor. Pasa las visibilidades posibles
     * para que el radio button las pueda listar.
     */
    public function render(): View
    {
        return view('livewire.admin.document.document-editor', [
            'visibilities' => DocumentVisibility::cases(),
        ]);
    }
}
