<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Inicio y cierre de sesion.
 *
 * Centraliza el flujo de login con redireccion por rol. La ruta vive
 * detras del middleware `guest`, por lo que el metodo `create` no
 * necesita comprobar el estado de autenticacion: si el usuario ya esta
 * logueado, el middleware le redirige a su dashboard correspondiente.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Muestra el formulario de login.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Procesa las credenciales y autentica al usuario. Si son validas
     * se regenera la sesion para evitar session fixation y se redirige
     * segun el rol. Si no, se lanza un error de validacion generico.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = (bool) $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('Estas credenciales no coinciden con nuestros registros.'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $isAdmin = $user?->isAdmin() ?? false;

        return $this->redirectByRole($isAdmin);
    }

    /**
     * Cierra la sesion actual, invalida el token CSRF y limpia los datos
     * sensibles del guard web.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Calcula la ruta de destino tras login. Se extrae para no duplicar
     * la logica entre `create` y `store` y para tener un unico punto
     * que cambiar si las URLs evolucionan.
     *
     * @param  bool  $isAdmin
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectByRole(bool $isAdmin): RedirectResponse
    {
        return redirect()->intended(
            $isAdmin ? route('admin.dashboard') : route('portal.dashboard')
        );
    }
}
