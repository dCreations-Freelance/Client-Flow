<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;

/**
 * Politica de autorizacion para `TimeEntry`.
 *
 * Reglas:
 * - Ver: admin siempre; cliente solo si la entrada es de
 *   un proyecto visible y del que es miembro de la
 *   organizacion (mismas reglas que `ProjectPolicy::view`).
 *   En la practica, el cliente rara vez llamara a este
 *   metodo porque la UI del portal solo muestra totales
 *   agregados, pero queda cubierto por si en una fase
 *   futura se expone el detalle de una entrada individual.
 * - Crear / editar / eliminar / marcar como facturable:
 *   exclusivo del admin. El cliente no tiene acceso al
 *   modulo de tiempo.
 */
class TimeEntryPolicy
{
    /**
     * Ver una entrada concreta.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TimeEntry  $entry
     * @return bool
     */
    public function view(User $user, TimeEntry $entry): bool
    {
        if ($entry->project === null) {
            return false;
        }

        return $user->can('view', $entry->project);
    }

    /**
     * Crear una entrada: solo admin.
     * La policy se evalua contra el proyecto, no contra
     * una entrada concreta, porque la creacion se hace
     * desde la vista de detalle de tarea o desde el
     * componente Livewire del timer.
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
     * Editar una entrada: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TimeEntry  $entry
     * @return bool
     */
    public function update(User $user, TimeEntry $entry): bool
    {
        return $user->isAdmin();
    }

    /**
     * Eliminar una entrada: solo admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TimeEntry  $entry
     * @return bool
     */
    public function delete(User $user, TimeEntry $entry): bool
    {
        return $user->isAdmin();
    }

    /**
     * Marcar como facturable / no facturable: solo admin.
     * La policy se evalua contra el proyecto, no contra
     * una entrada concreta, para simplificar la llamada
     * desde el dashboard.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function markAsBilled(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Ver el resumen de tiempo de un proyecto. Se evalua
     * contra el proyecto (no contra una entrada) porque
     * el dashboard y la vista de resumen muestran
     * agregados. El admin siempre puede; el cliente solo
     * si es miembro de la organizacion y el proyecto es
     * visible.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function viewSummary(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }
}
