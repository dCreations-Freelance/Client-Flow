<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\TaskAttachment;
use App\Models\User;

/**
 * Politica de autorizacion para `TaskAttachment`.
 *
 * Reglas:
 * - Ver/descargar: sigue la policy de la tarea padre. Como
 *   `TaskPolicy::view` ya gestiona que el cliente solo ve tareas
 *   de proyectos visibles, no hace falta duplicar la logica
 *   aqui.
 * - Subir: solo admin. Es coherente con el resto de CRUDs del
 *   kanban (el cliente es de solo lectura).
 * - Eliminar: solo admin, sin importar quien haya subido el
 *   adjunto. La regla es deliberadamente estricta: el cliente
 *   no debe poder borrar evidencia de la conversacion aunque
 *   el archivo sea suyo.
 */
class TaskAttachmentPolicy
{
    /**
     * Ver un adjunto requiere ver la tarea padre.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TaskAttachment  $attachment
     * @return bool
     */
    public function view(User $user, TaskAttachment $attachment): bool
    {
        if ($attachment->task === null) {
            return false;
        }

        return $user->can('view', $attachment->task);
    }

    /**
     * Descargar tiene la misma regla que `view`. Se expone como
     * metodo separado para que la intencion sea clara en el
     * controlador (`authorize('download', $attachment)`).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TaskAttachment  $attachment
     * @return bool
     */
    public function download(User $user, TaskAttachment $attachment): bool
    {
        return $this->view($user, $attachment);
    }

    /**
     * Subir adjuntos a una tarea: solo admin. La policy se
     * evalua contra el proyecto, no contra una tarea concreta,
     * porque la subida se hace en el modal de crear tarea o en
     * el detalle admin.
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
     * Eliminar un adjunto: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TaskAttachment  $attachment
     * @return bool
     */
    public function delete(User $user, TaskAttachment $attachment): bool
    {
        return $user->isAdmin();
    }
}
