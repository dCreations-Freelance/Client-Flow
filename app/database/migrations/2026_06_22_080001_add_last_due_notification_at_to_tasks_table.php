<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anade `last_due_notification_at` a la tabla `tasks`.
 *
 * Es el sello temporal que usa el comando
 * `notifications:task-due-soon` para no enviar el mismo
 * recordatorio de deadline dos veces el mismo dia. Se almacena
 * en la propia tarea (no en una tabla aparte) porque:
 * - Solo hay un recordatorio activo por tarea, no una coleccion
 *   historica.
 * - Consultar y actualizar es una sola query.
 * - La limpieza en cascada al borrar la tarea es automatica.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->timestamp('last_due_notification_at')
                ->nullable()
                ->after('completed_at');
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('last_due_notification_at');
        });
    }
};
