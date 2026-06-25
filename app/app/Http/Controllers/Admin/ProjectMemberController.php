<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachProjectMemberRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestion de miembros asignados a un proyecto.
 *
 * Solo admin puede ejecutar estas acciones. Antes de asociar al
 * usuario al proyecto verificamos que pertenezca a la organizacion
 * del proyecto: es la unica manera de mantener un modelo de
 * aislamiento consistente (no se puede "colar" un usuario de otra
 * org como miembro).
 */
class ProjectMemberController extends Controller
{
    /**
     * Anade un usuario al proyecto tras validar que es miembro de la
     * organizacion. Si ya es miembro del proyecto, no se duplica.
     *
     * @param  \App\Http\Requests\Admin\AttachProjectMemberRequest  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(AttachProjectMemberRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $userId = (int) $request->integer('user_id');

        $belongsToOrg = $project->organization
            ->members()
            ->where('users.id', $userId)
            ->exists();

        if (! $belongsToOrg) {
            return back()->withErrors([
                'user_id' => 'El usuario debe pertenecer a la organizacion del proyecto.',
            ]);
        }

        if (! $project->members()->where('users.id', $userId)->exists()) {
            $project->members()->attach($userId);

            // Registramos el alta en el feed (evento privado:
            // el portal cliente no ve quien-trabaja-con-quien).
            $member = User::find($userId);
            if ($member !== null) {
                app(ActivityLogger::class)->memberAdded(
                    $project,
                    $member,
                    $request->user(),
                );
            }
        }

        return back()->with('status', 'Miembro anadido al proyecto.');
    }

    /**
     * Quita a un usuario del proyecto. La pertenencia a la
     * organizacion se mantiene: solo se rompe la asignacion al
     * proyecto concreto.
     *
     * @param  \App\Models\Project  $project
     * @param  int  $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, Project $project, int $userId): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        // Capturamos el nombre ANTES de desvincular para que el
        // log de actividad siga siendo legible despues.
        $member = User::find($userId);
        $name = $member?->name ?? "Usuario #{$userId}";

        $project->members()->detach($userId);

        app(ActivityLogger::class)->memberRemoved(
            $project,
            $name,
            $request->user(),
        );

        return back()->with('status', 'Miembro retirado del proyecto.');
    }
}
