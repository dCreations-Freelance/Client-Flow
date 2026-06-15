<?php

namespace App\Policies;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\User;

/**
 * Politica de columnas del kanban.
 *
 * - La lectura de columnas sigue la misma logica que la lectura
 *   del proyecto: admin ve todo, cliente ve lo que puede ver del
 *   proyecto.
 * - Cualquier modificacion (crear, renombrar, reordenar, eliminar)
 *   esta reservada al admin.
 */
class BoardColumnPolicy
{
    /**
     * Determina si el usuario puede ver las columnas del proyecto.
     * La autorizacion real se hace contra el proyecto, no contra
     * la columna individual, para mantener el aislamiento.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BoardColumn  $column
     * @return bool
     */
    public function view(User $user, BoardColumn $column): bool
    {
        if ($column->project === null) {
            return false;
        }

        return $user->can('view', $column->project);
    }

    /**
     * Crear columnas es exclusivo del admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function create(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Modificar una columna (renombrar, recolorear) es admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BoardColumn  $column
     * @return bool
     */
    public function update(User $user, BoardColumn $column): bool
    {
        return $user->isAdmin();
    }

    /**
     * Eliminar una columna es admin. El controlador comprobara
     * ademas que no tenga tareas.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BoardColumn  $column
     * @return bool
     */
    public function delete(User $user, BoardColumn $column): bool
    {
        return $user->isAdmin();
    }

    /**
     * Reordenar columnas es admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BoardColumn  $column
     * @return bool
     */
    public function reorder(User $user, BoardColumn $column): bool
    {
        return $user->isAdmin();
    }
}
