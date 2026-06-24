<?php

use App\Enums\TimeEntryType;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `time_entries` para almacenar las entradas de
 * tiempo (manuales y de temporizador) que el admin registra
 * contra las tareas del proyecto.
 *
 * Decisiones de diseno:
 * - `task_id` con `cascadeOnDelete`: si se borra la tarea se
 *   eliminan sus entradas. Esto evita filas huerfanas y es
 *   coherente con el resto de pivots que cuelgan de `tasks`.
 * - `project_id` con `cascadeOnDelete`: aunque `task_id` ya
 *   implica pertenencia al proyecto (via la tarea), lo
 *   persistimos para acelerar los calculos del dashboard
 *   (no hay que hacer JOIN con `tasks` en cada consulta
 *   agregada).
 * - `user_id` con `restrictOnDelete`: autoria historica.
 *   Mismo patron que `task_attachments`. Un admin que quiere
 *   borrar a un usuario que ha registrado tiempo debe
 *   reasignar primero sus entradas.
 * - `description` opcional: una entrada puede ser solo "30
 *   minutos" sin contexto adicional.
 * - `type` como string con valores del enum `TimeEntryType`
 *   (manual o timer). Se valida en Form Request, no en BD,
 *   para que anadir un tipo futuro (p. ej. `imported`) sea
 *   solo codigo.
 * - `started_at` solo se rellena para entradas de tipo
 *   `timer`. Es la marca de inicio del cronometro. Para
 *   entradas manuales, la fecha del trabajo se obtiene de
 *   `created_at` (o de un campo `entry_date` si en una
 *   fase futura se permite registrar tiempo en dias
 *   pasados).
 * - `billed` indica si la entrada se ha incluido ya en una
 *   facturacion. Indexado porque la vista de dashboard
 *   filtra frecuentemente por este flag.
 * - `minutes` como `unsignedInteger` (max ~4 mil millones,
 *   mas que suficiente para una sola entrada).
 * - Indices:
 *     - `(task_id, created_at)` para la lista "entradas de
 *       esta tarea en orden cronologico".
 *     - `(user_id, project_id)` para el dashboard por
 *       miembro.
 *     - `(project_id, billed)` para el dashboard filtrado
 *       por facturable.
 *     - `(project_id, created_at)` para los calculos de
 *       totales por proyecto.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Task::class, 'task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class, 'user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignIdFor(Project::class, 'project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->text('description')->nullable();
            $table->string('type')
                ->default(TimeEntryType::Manual->value);

            $table->unsignedInteger('minutes');

            $table->timestamp('started_at')->nullable();

            $table->boolean('billed')
                ->default(false)
                ->index();

            $table->timestamps();

            $table->index(['task_id', 'created_at']);
            $table->index(['user_id', 'project_id']);
            $table->index(['project_id', 'created_at']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
