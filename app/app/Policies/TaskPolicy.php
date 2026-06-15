<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/**
 * Politica de tareas.
 *
 * - Lectura: admin ve todo; cliente ve tareas de proyectos que
 *   puede ver y que estan visibles.
 * - Modificacion: solo admin puede crear, editar, mover, eliminar,
 *   completar o reabrir tareas.
 */
class TaskPolicy
{
    /**
     * Ver una tarea requiere ver el proyecto al que pertenece.
     * Ademas, si el usuario es cliente, la tarea debe estar en un
     * proyecto visible (no archivado, is_visible_to_client true).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return bool
     */
    public function view(User $user, Task $task): bool
    {
        if ($task->project === null) {
            return false;
        }

        if (! $user->can('view', $task->project)) {
            return false;
        }

        // Admin ve siempre.
        if ($user->isAdmin()) {
            return true;
        }

        // Cliente: ademas requiere que el proyecto sea visible.
        return $task->project->isVisibleToClient();
    }

    /**
     * Crear tareas: solo admin. La policy del proyecto (que ya
     * aplica el middleware) garantiza que el usuario esta en la
     * zona admin.
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
     * Editar tareas: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return bool
     */
    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin();
    }

    /**
     * Eliminar tareas: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return bool
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin();
    }

    /**
     * Mover tareas entre columnas: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return bool
     */
    public function move(User $user, Task $task): bool
    {
        return $user->isAdmin();
    }

    /**
     * Marcar como completada: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return bool
     */
    public function complete(User $user, Task $task): bool
    {
        return $user->isAdmin();
    }

    /**
     * Re-abrir tarea: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return bool
     */
    public function reopen(User $user, Task $task): bool
    {
        return $user->isAdmin();
    }
}
