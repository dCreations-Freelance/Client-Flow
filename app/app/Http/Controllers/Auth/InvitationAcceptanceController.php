<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\User;
use App\Services\OrganizationInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Aceptacion publica de invitaciones.
 *
 * Es publico en el sentido de que la ruta vive sin middleware `auth`.
 * La logica decide en tiempo de peticion si:
 *  - El usuario no esta autenticado: muestra el formulario de
 *    aceptacion (solo password + nombre).
 *  - El usuario esta autenticado: vincula directamente la invitacion a
 *    su cuenta si el email coincide.
 */
class InvitationAcceptanceController extends Controller
{
    public function __construct(
        private OrganizationInvitationService $invitations,
    ) {
    }

    /**
     * Muestra la pantalla de aceptacion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $token
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $invitation = $this->invitations->findByRawToken($token);

        if ($invitation === null) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'La invitacion no existe o ha expirado.']);
        }

        if (Auth::check()) {
            return $this->acceptForCurrentUser(Auth::user(), $invitation);
        }

        return view('auth.invitation', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    /**
     * Procesa el formulario publico: crea la cuenta (si no existe),
     * loguea al usuario y acepta la invitacion.
     *
     * @param  \App\Http\Requests\Auth\AcceptInvitationRequest  $request
     * @param  string  $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = $this->invitations->findByRawToken($token);

        if ($invitation === null) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'La invitacion no existe o ha expirado.']);
        }

        // Si el email ya tiene cuenta, evitamos crear un duplicado y
        // devolvemos al usuario al login para que entre y se le asocie
        // automaticamente (logica en `show`).
        if (User::where('email', $invitation->email)->exists()) {
            return redirect()
                ->route('login')
                ->with('status', 'Ya tienes cuenta. Inicia sesion y unete a la organizacion.');
        }

        $user = DB::transaction(function () use ($request, $invitation): User {
            // `forceFill` + `save` (no `User::create`) porque `password`
            // ya no esta en `$fillable` (auditoria L-04) y un `create`
            // intermedio violaria la restriccion NOT NULL. El cast
            // `'hashed'` se encarga de hashear al persistir.
            $user = new User;
            $user->forceFill([
                'name' => $request->string('name')->toString(),
                'email' => $invitation->email,
                'role' => \App\Enums\UserRole::Client,
                'password' => $request->string('password')->toString(),
            ]);
            $user->save();

            $this->invitations->accept($invitation, $user);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }

    /**
     * Acepta la invitacion para un usuario ya autenticado. Se delega
     * en el servicio para mantener la logica de asociacion en un solo
     * lugar.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\OrganizationInvitation  $invitation
     * @return \Illuminate\Http\RedirectResponse
     */
    private function acceptForCurrentUser(User $user, \App\Models\OrganizationInvitation $invitation): RedirectResponse
    {
        if (strcasecmp($user->email, $invitation->email) !== 0) {
            throw ValidationException::withMessages([
                'email' => 'Esta invitacion es para otro email. Inicia sesion con la cuenta correcta.',
            ]);
        }

        $this->invitations->accept($invitation, $user);

        return redirect()->route('portal.dashboard');
    }
}
