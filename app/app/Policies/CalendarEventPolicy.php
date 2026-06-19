<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\User;

/**
 * Politica de autorizacion para `CalendarEvent`.
 *
 * Reglas:
 * - Admin: control total. Puede crear, ver, editar, eliminar
 *   cualquier evento del calendario de cualquier proyecto.
 * - Cliente: solo puede ver los eventos de los proyectos a los que
 *   ya tiene acceso (miembro de la organizacion y proyecto visible
 *   al cliente). El cliente nunca crea, edita ni elimina eventos
 *   en MVP: es un consumidor del calendario, no un editor.
 *
 * La verificacion de visibilidad se delega en `ProjectPolicy::view`
 * para mantener una unica fuente de verdad sobre "quien puede ver
 * este proyecto". Asi un cambio en la policy de proyecto se
 * propaga automaticamente a los eventos.
 */
class CalendarEventPolicy
{
    /**
     * Determina si el usuario puede ver el listado de eventos de
     * un proyecto. La policy real de cada evento se evalua en
     * `view`.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }

    /**
     * Ver un evento concreto. Si el usuario puede ver el proyecto
     * del evento, puede ver el evento (sea admin o cliente).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CalendarEvent  $event
     * @return bool
     */
    public function view(User $user, CalendarEvent $event): bool
    {
        if ($event->project === null) {
            return false;
        }

        return $user->can('view', $event->project);
    }

    /**
     * Crear eventos: solo admin. El cliente nunca crea eventos
     * en MVP; si en una fase futura se permite, esta policy
     * debe actualizarse y validar la membresia.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Editar un evento: solo admin. Mantener simetria con `create`
     * para no abrir vectores de fuga donde un cliente edite
     * eventos del proyecto.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CalendarEvent  $event
     * @return bool
     */
    public function update(User $user, CalendarEvent $event): bool
    {
        return $user->isAdmin();
    }

    /**
     * Eliminar un evento: solo admin. Misma justificacion que
     * `update`.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CalendarEvent  $event
     * @return bool
     */
    public function delete(User $user, CalendarEvent $event): bool
    {
        return $user->isAdmin();
    }

    /**
     * Gestionar la lista de asistentes: solo admin. En MVP los
     * clientes no pueden invitar a otros usuarios a eventos;
     * si en una fase futura se ampla, esta policy debe
     * actualizarse con las mismas reglas que `update`.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CalendarEvent  $event
     * @return bool
     */
    public function manageAttendees(User $user, CalendarEvent $event): bool
    {
        return $user->isAdmin();
    }
}
