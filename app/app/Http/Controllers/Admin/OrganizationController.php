<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD de organizaciones del panel de administracion.
 *
 * Las autorizaciones se delegan en `OrganizationPolicy`. Tras crear
 * una organizacion, el admin actual se anade automaticamente como
 * `owner` para que pueda empezar a gestionarla.
 */
class OrganizationController extends Controller
{
    /**
     * Listado paginado con busqueda por nombre y filtro por estado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Organization::class);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $organizations = Organization::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.organizations.index', [
            'organizations' => $organizations,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Muestra el formulario de creacion.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $this->authorize('create', Organization::class);

        return view('admin.organizations.create');
    }

    /**
     * Persiste la organizacion y anade al admin actual como `owner`.
     *
     * @param  \App\Http\Requests\Admin\StoreOrganizationRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $this->authorize('create', Organization::class);

        $data = $request->validated();
        $data['owner_id'] = $request->user()->id;

        $organization = Organization::create($data);

        // El admin actual se une como owner para poder gestionarla
        // desde el primer momento (incluye projects en fases futuras).
        $organization->members()->attach($request->user()->id, [
            'role' => 'owner',
        ]);

        return redirect()
            ->route('admin.organizations.show', $organization)
            ->with('status', 'Organizacion creada.');
    }

    /**
     * Detalle con tabs General / Miembros / Proyectos.
     *
     * @param  \App\Models\Organization  $organization
     * @return \Illuminate\View\View
     */
    public function show(Organization $organization): View
    {
        $this->authorize('view', $organization);

        $organization->load(['members', 'pendingInvitations']);

        return view('admin.organizations.show', [
            'organization' => $organization,
        ]);
    }

    /**
     * Formulario de edicion.
     *
     * @param  \App\Models\Organization  $organization
     * @return \Illuminate\View\View
     */
    public function edit(Organization $organization): View
    {
        $this->authorize('update', $organization);

        return view('admin.organizations.edit', [
            'organization' => $organization,
        ]);
    }

    /**
     * Actualiza la organizacion.
     *
     * @param  \App\Http\Requests\Admin\UpdateOrganizationRequest  $request
     * @param  \App\Models\Organization  $organization
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);

        $organization->update($request->validated());

        return redirect()
            ->route('admin.organizations.show', $organization)
            ->with('status', 'Organizacion actualizada.');
    }

    /**
     * Elimina la organizacion. Se delega en policy + cascade para
     * limpiar relaciones.
     *
     * @param  \App\Models\Organization  $organization
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return redirect()
            ->route('admin.organizations.index')
            ->with('status', 'Organizacion eliminada.');
    }
}
