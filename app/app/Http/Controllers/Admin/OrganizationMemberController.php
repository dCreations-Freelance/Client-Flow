<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationUserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteMemberRequest;
use App\Models\Organization;
use App\Notifications\OrganizationInvitationSent;
use App\Services\OrganizationInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * Gestion de miembros e invitaciones de una organizacion.
 *
 * Pensado como rutas anidadas bajo `admin.organizations`. Las invitaciones
 * ya existentes (pendientes) se listan para que el admin pueda
 * reenviarlas o cancelarlas en una fase futura.
 */
class OrganizationMemberController extends Controller
{
    /**
     * Inyeccion del servicio de invitaciones. Se hace por constructor
     * para que sea facil de mockear en tests y para no instanciar el
     * servicio en cada llamada.
     */
    public function __construct(
        private OrganizationInvitationService $invitations,
    ) {
    }

    /**
     * Listado de miembros actuales. La pantalla de detalle ya muestra
     * los miembros; este endpoint se reserva para vistas dedicadas en
     * fases siguientes.
     *
     * @param  \App\Models\Organization  $organization
     * @return \Illuminate\View\View
     */
    public function index(Organization $organization): View
    {
        $this->authorize('manageMembers', $organization);

        $organization->load(['members', 'pendingInvitations']);

        return view('admin.organizations.members', [
            'organization' => $organization,
        ]);
    }

    /**
     * Envia una invitacion al email indicado. Se genera el token, se
     * guarda su hash en BD y se notifica al email (en MVP el mailer
     * es `log`, asi que el enlace aparecera en el log de Laravel).
     *
     * @param  \App\Http\Requests\Admin\InviteMemberRequest  $request
     * @param  \App\Models\Organization  $organization
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(InviteMemberRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('invite', $organization);

        $role = OrganizationUserRole::from($request->string('role')->toString());
        $email = (string) $request->string('email');

        [$invitation, $rawToken] = $this->invitations->create(
            $organization,
            $email,
            $role,
            $request->user(),
        );

        // La invitacion se envia por email al destinatario, que
        // todavia no es usuario de ClientFlow, asi que usamos un
        // `AnonymousNotifiable` con el canal `mail`. No podemos
        // consultar `preferenceFor()` porque el destinatario no
        // tiene fila en `users` todavia; el opt-out queda
        // pendiente de aplicar a la primera vez que el usuario
        // acepte la invitacion y se cree la cuenta.
        $notifiable = (new AnonymousNotifiable)
            ->route('mail', $email);

        Notification::send(
            $notifiable,
            new OrganizationInvitationSent($invitation, $rawToken),
        );

        return back()->with('status', 'Invitacion enviada a '.$email.'.');
    }

    /**
     * Elimina un miembro de la organizacion. Se anade una salvaguarda
     * para no permitir eliminar al unico owner: si fuera el unico
     * responsable, la org quedaria huerfana.
     *
     * @param  \App\Models\Organization  $organization
     * @param  int  $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Organization $organization, int $userId): RedirectResponse
    {
        $this->authorize('manageMembers', $organization);

        $isOwner = $organization->members()
            ->where('users.id', $userId)
            ->wherePivot('role', OrganizationUserRole::Owner->value)
            ->exists();

        if ($isOwner && $organization->owners()->count() <= 1) {
            return back()->withErrors(['members' => 'No puedes eliminar al unico responsable de la organizacion.']);
        }

        $organization->members()->detach($userId);

        return back()->with('status', 'Miembro eliminado.');
    }
}
