<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Pide el enlace para restablecer contrasena.
 *
 * Sigue el patron de Laravel: delega en el broker de passwords para
 * generar el token y enviar el email. En el MVP el mailer esta en modo
 * `log`, asi que el enlace se imprime en `storage/logs/laravel.log`.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Muestra el formulario para introducir el email.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('auth.password-request');
    }

    /**
     * Envia el enlace de recuperacion. La respuesta es siempre la misma
     * para no filtrar que emails estan registrados en la aplicacion.
     *
     * @param  \App\Http\Requests\Auth\PasswordResetLinkRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(PasswordResetLinkRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Por seguridad, indistintamente del estado real (usuario encontrado
        // o no), devolvemos el mismo mensaje de exito. Asi no se filtra que
        // direcciones tienen cuenta.
        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __('Si el email esta registrado, enviaremos un enlace de recuperacion.'))
            : back()->with('status', __('Si el email esta registrado, enviaremos un enlace de recuperacion.'));
    }
}
