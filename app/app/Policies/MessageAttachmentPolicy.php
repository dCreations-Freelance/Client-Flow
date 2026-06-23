<?php

namespace App\Policies;

use App\Models\MessageAttachment;
use App\Models\Project;
use App\Models\User;

/**
 * Politica de autorizacion para `MessageAttachment`.
 *
 * Reglas:
 * - Ver/descargar: cualquier usuario que pueda ver el chat del
 *   proyecto puede descargar los adjuntos de los mensajes. La
 *   verificacion se delega en `ProjectPolicy::view`, que ya
 *   cubre el aislamiento entre organizaciones y la visibilidad
 *   para el cliente.
 * - Subir: cualquier usuario que pueda ver el proyecto. Aqui el
 *   cliente SI puede subir adjuntos, porque ya puede enviar
 *   mensajes de texto. Mantener la consistencia evita una
 *   UX confusa (poder escribir pero no poder adjuntar).
 * - Eliminar: solo admin, igual que en `TaskAttachmentPolicy`.
 */
class MessageAttachmentPolicy
{
    /**
     * Ver un adjunto requiere ver el proyecto del mensaje.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MessageAttachment  $attachment
     * @return bool
     */
    public function view(User $user, MessageAttachment $attachment): bool
    {
        if ($attachment->message === null) {
            return false;
        }

        return $user->can('view', $attachment->message->project);
    }

    /**
     * Descargar sigue la misma regla que `view`.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MessageAttachment  $attachment
     * @return bool
     */
    public function download(User $user, MessageAttachment $attachment): bool
    {
        return $this->view($user, $attachment);
    }

    /**
     * Subir un adjunto a un mensaje: quien pueda ver el proyecto
     * puede adjuntar. Asi, tanto el admin como un cliente del
     * proyecto pueden enviar un archivo.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function create(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }

    /**
     * Eliminar un adjunto: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MessageAttachment  $attachment
     * @return bool
     */
    public function delete(User $user, MessageAttachment $attachment): bool
    {
        return $user->isAdmin();
    }
}
