<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

/**
 * Politica de autorizacion para `ActivityLog`.
 *
 * Reglas:
 * - El feed se ve si el usuario puede ver el proyecto asociado
 *   (admin siempre; cliente miembro de la org con proyecto
 *   visible y no archivado). Asi no se introduce un bypass de
 *   autorizacion: el acceso al feed depende de la policy del
 *   proyecto, no de una comprobacion independiente.
 * - El portal ademas aplica el filtro de visibilidad de
 *   eventos (`ActivityType::isPublic()` + properties en el
 *   caso de documentos). Eso lo hace el servicio
 *   `ProjectActivityFeedService`, no la policy: la policy
 *   decide "puedes ver esta entrada?", el servicio decide
 *   "que entradas te mostramos?".
 * - No hay `create/update/delete` publicos: la escritura la
 *   controla `ActivityLogger`, que solo se invoca desde
 *   codigo autorizado. Mantener la policy asi refuerza el
 *   contrato y permite que tests fallen si en una fase futura
 *   se intenta autorizar escritura desde un controlador.
 */
class ActivityLogPolicy
{
    /**
     * Listar entradas del feed de un proyecto. Delega en
     * `ProjectPolicy::view` para mantener una unica fuente
     * de verdad sobre quien puede ver el proyecto.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ActivityLog  $log
     * @return bool
     */
    public function view(User $user, ActivityLog $log): bool
    {
        if ($log->project_id === null) {
            // Entradas sin proyecto (cross-project) son admin-only.
            return $user->isAdmin();
        }

        // Para entradas con proyecto delegamos en la policy
        // del proyecto. No comprobamos visibilidad de eventos
        // aqui: eso lo hace el servicio al cargar el feed.
        $project = $log->project;
        if ($project === null) {
            // El proyecto fue borrado pero la entrada quedo
            // por la configuracion actual de la FK? No deberia
            // pasar (cascadeOnDelete), pero defendemos.
            return $user->isAdmin();
        }

        return $user->can('view', $project);
    }
}
