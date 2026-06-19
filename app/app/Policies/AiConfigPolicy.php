<?php

namespace App\Policies;

use App\Models\AiConfig;
use App\Models\User;

/**
 * Politica de autorizacion para `AiConfig`.
 *
 * La configuracion del modulo IA es operativa del
 * administrador de la instancia: solo el admin puede
 * verla, crearla, editarla, borrarla o probar la
 * conexion. Los clientes no deberian poder ni siquiera
 * saber que provider esta configurado.
 */
class AiConfigPolicy
{
    /**
     * Determina si el usuario puede ver el listado de
     * configuraciones (panel de settings).
     *
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede ver una configuracion
     * concreta. La vista detallada solo la usa el admin
     * desde el panel de settings.
     *
     * Recibe solo el usuario porque el controlador siempre
     * invoca `authorize('view', AiConfig::class)`: la policy
     * discrimina solo por rol.
     *
     * @return bool
     */
    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede crear configuraciones.
     * En MVP solo tiene sentido una global + N por proyecto,
     * pero la policy no restringe el modelo: eso lo hace
     * el unique constraint.
     *
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede editar una configuracion.
     * Recibe solo el usuario por la misma razon que `view`.
     *
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede borrar una configuracion.
     * Recibe solo el usuario por la misma razon que `view`.
     *
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede probar la conexion con
     * un provider concreto. Util para mostrar u ocultar el
     * boton "Probar conexion" en el formulario.
     *
     * No recibe el modelo `AiConfig` porque el `authorize()`
     * se invoca con la clase cuando todavia no hay una fila
     * persistida: la policy solo discrimina por rol.
     *
     * @return bool
     */
    public function test(User $user): bool
    {
        return $user->isAdmin();
    }
}
