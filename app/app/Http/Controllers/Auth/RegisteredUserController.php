<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Registro publico de clientes.
 *
 * El registro solo crea cuentas con rol `client` (forzado en el
 * `RegisterRequest`). Tras crear la cuenta se dispara el evento
 * `Registered` por si en el futuro se anade verificacion de email;
 * en el MVP el usuario se loguea directamente. La ruta vive detras
 * del middleware `guest`, que se encarga de expulsar a usuarios ya
 * autenticados, por lo que el metodo `create` no necesita duplicar
 * esa comprobacion.
 */
class RegisteredUserController extends Controller
{
    /**
     * Muestra el formulario de registro.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Crea la cuenta, dispara el evento de registro y autentica al usuario.
     *
     * @param  \App\Http\Requests\Auth\RegisterRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $request->createUser();

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }
}
