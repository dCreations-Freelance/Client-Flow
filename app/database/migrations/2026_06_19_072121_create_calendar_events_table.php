<?php

use App\Enums\CalendarEventType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `calendar_events` que almacena los eventos de
 * calendario asociados a un proyecto.
 *
 * - `project_id` es NOT NULL: en MVP todos los eventos pertenecen
 *   a un proyecto concreto. Se descartan eventos cross-project
 *   (ferias, eventos internos) para simplificar la UI: el admin
 *   gestiona eventos desde el calendario de cada proyecto y el
 *   cliente solo ve los de sus proyectos.
 * - `type` esta indexado para que el filtrado por `meeting` /
 *   `milestone` sea eficiente. El caso `deadline` se reserva para
 *   la representacion virtual derivada de `tasks.due_date`; nunca
 *   se persiste un `CalendarEvent` con `type = deadline`.
 * - `starts_at` y `ends_at` se indexan por separado y ademas en un
 *   indice compuesto `(project_id, starts_at)` para que la query
 *   habitual "eventos del proyecto en el mes X" se resuelva sin
 *   filesort.
 * - `is_all_day` se anade como desviacion controlada del data
 *   model original: sin este flag los milestones y los eventos de
 *   todo un dia no son representables (no tendrian sentido con
 *   hora de inicio). Cuando es `true`, la UI oculta los inputs de
 *   hora y el evento ocupa la jornada completa.
 * - `created_by` mantiene la autoria; `restrictOnDelete` evita que
 *   un borrado accidental de un admin destruya el historial de
 *   eventos.
 * - `description` es `text` y no `longtext` porque un resumen de
 *   evento rara vez supera los 65KB y mantener un tamano acotado
 *   ayuda a perfilar consultas.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();

            // FK al proyecto: cascade para que al borrar un proyecto
            // se borren sus eventos. No permitimos eventos sin
            // proyecto (ver comentario de cabecera).
            $table->foreignIdFor(Project::class, 'project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->string('title', 200);

            $table->text('description')->nullable();

            // Tipo: meeting, milestone o (virtualmente) deadline.
            // Indexado para filtros rapidos por tipo.
            $table->string('type')
                ->default(CalendarEventType::Meeting->value)
                ->index();

            // Fechas: indexadas por separado y en el indice
            // compuesto de abajo para resolver queries mensuales.
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();

            // Flag de evento de todo el dia. Cuando es true la UI
            // oculta los inputs de hora y el evento ocupa la
            // jornada completa (ver comentario de cabecera).
            $table->boolean('is_all_day')->default(false);

            // Autoria: restrict para preservar trazabilidad si se
            // intenta borrar un admin con eventos a su nombre.
            $table->foreignIdFor(User::class, 'created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            // Indice principal: lista de eventos por proyecto en
            // orden cronologico ascendente. Cubre las queries de
            // "eventos del mes X" y "proximos eventos".
            $table->index(['project_id', 'starts_at']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
