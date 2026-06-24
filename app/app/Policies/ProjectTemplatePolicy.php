<?php

namespace App\Policies;

use App\Models\ProjectTemplate;
use App\Models\User;

/**
 * Politica de autorizacion para `ProjectTemplate`.
 *
 * Reglas:
 * - Todas las acciones (ver listado, ver detalle,
 *   crear, editar, eliminar, aplicar) son
 *   exclusivas del admin. La biblioteca de
 *   plantillas es una herramienta interna del
 *   freelancer / agencia; el cliente no la ve ni
 *   la usa directamente.
 */
class ProjectTemplatePolicy
{
    /**
     * Ver el listado o cualquier plantilla: solo
     * admin.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Ver el detalle de una plantilla: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProjectTemplate  $template
     * @return bool
     */
    public function view(User $user, ProjectTemplate $template): bool
    {
        return $user->isAdmin();
    }

    /**
     * Crear una plantilla nueva: solo admin.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Editar una plantilla (metadatos, columnas,
     * tareas o documentos): solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProjectTemplate  $template
     * @return bool
     */
    public function update(User $user, ProjectTemplate $template): bool
    {
        return $user->isAdmin();
    }

    /**
     * Eliminar una plantilla: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProjectTemplate  $template
     * @return bool
     */
    public function delete(User $user, ProjectTemplate $template): bool
    {
        return $user->isAdmin();
    }

    /**
     * Aplicar una plantilla a un proyecto nuevo:
     * solo admin. La policy se evalua contra la
     * plantilla (no contra un proyecto) porque la
     * aplicacion es lo que crea el proyecto.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProjectTemplate  $template
     * @return bool
     */
    public function apply(User $user, ProjectTemplate $template): bool
    {
        return $user->isAdmin();
    }
}
