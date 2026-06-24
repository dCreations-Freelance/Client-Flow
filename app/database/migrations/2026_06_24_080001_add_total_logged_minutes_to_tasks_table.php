<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anade `total_logged_minutes` a la tabla `tasks`.
 *
 * Es la cache que mantiene el tiempo total registrado
 * (manual + timer) en una tarea, para que la vista de
 * detalle de tarea, el hub del proyecto y los badges no
 * tengan que sumar las entradas en cada render.
 *
 * La cache se actualiza desde el `boot()` del modelo
 * `TimeEntry` (observer automatico en created/updated/
 * deleted). Por eso el campo se incluye en una migracion
 * propia: aunque logicamente pertenece a la migracion
 * de `tasks`, separarlo evita que la cache se inicialice
 * con valores antiguos durante el `migrate:fresh` cuando
 * aun no existe la tabla `time_entries`.
 *
 * - `unsignedInteger` (max ~4 mil millones de minutos,
 *   mas que suficiente para cualquier proyecto).
 * - `default(0)` para que las tareas existentes se
 *   queden en cero al aplicar la migracion (no rompe
 *   la UI).
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->unsignedInteger('total_logged_minutes')
                ->default(0)
                ->after('actual_hours');
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('total_logged_minutes');
        });
    }
};
