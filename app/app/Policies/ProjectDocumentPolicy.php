<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;

/**
 * Politica de autorizacion para `ProjectDocument`.
 *
 * Reglas:
 * - Admin: control total (crear, leer privados, editar, eliminar).
 * - Cliente: solo puede listar/ver documentos `public` de proyectos
 *   a los que ya tiene acceso (es decir, proyectos visibles al
 *   cliente, no archivados, donde el cliente es miembro de la
 *   organizacion). Los documentos `private` nunca son visibles para
 *   el cliente, ni siquiera como respuesta de un endpoint. Esto se
 *   refuerza en el controlador del portal aplicando un scope `public`
 *   y la policy a nivel de modelo.
 */
class ProjectDocumentPolicy
{
    /**
     * Determina si el usuario puede ver el listado de documentos de
     * un proyecto. La policy real de cada documento se evalua en
     * `view` y `viewPrivate`.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function viewAny(User $user, Project $project): bool
    {
        // El listado se muestra si el usuario puede ver el proyecto.
        // Para clientes, eso ya filtra a proyectos visibles; ademas
        // el scope `public` se aplica en el controlador del portal
        // para no devolver documentos privados.
        return $user->can('view', $project);
    }

    /**
     * Ver un documento concreto.
     *
     * - Admin: siempre puede.
     * - Cliente: solo si el documento es publico y el proyecto es
     *   visible al cliente (mismo chequeo que `ProjectPolicy::view`).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProjectDocument  $document
     * @return bool
     */
    public function view(User $user, ProjectDocument $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isClient()) {
            return false;
        }

        if ($document->project === null) {
            return false;
        }

        if (! $document->isPublic()) {
            return false;
        }

        return $user->can('view', $document->project);
    }

    /**
     * Crear documentos: solo admin.
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
     * Editar un documento: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProjectDocument  $document
     * @return bool
     */
    public function update(User $user, ProjectDocument $document): bool
    {
        return $user->isAdmin();
    }

    /**
     * Eliminar un documento: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProjectDocument  $document
     * @return bool
     */
    public function delete(User $user, ProjectDocument $document): bool
    {
        return $user->isAdmin();
    }
}
