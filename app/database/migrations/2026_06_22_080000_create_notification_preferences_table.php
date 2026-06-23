<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `notification_preferences` segun el plan de la fase
 * transversal de Notificaciones.
 *
 * Cada fila representa la decision de un usuario sobre un evento
 * concreto (mensaje nuevo, tarea asignada, etc.) y los canales por
 * los que quiere recibirlo (in-app y email). El sistema consulta
 * esta tabla antes de despachar cualquier notificacion, asi que es
 * la pieza que materializa el opt-out.
 *
 * Decisiones de diseno:
 * - Una fila por (user_id, event). El unique compuesto impide que un
 *   mismo usuario tenga dos preferencias para el mismo evento.
 * - Los booleanos `in_app` y `email` tienen `default(true)` para que
 *   un usuario nuevo reciba todo por defecto; el listener
 *   `CreateDefaultNotificationPreferences` se encarga de sembrar las
 *   filas en el alta del usuario, pero el default en BD es un
 *   seguro para datos preexistentes.
 * - `event` se guarda como string (no como enum nativo de MySQL)
 *   para que añadir un nuevo evento en el futuro sea solo una
 *   migracion de codigo, no de esquema.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('event')->index();
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'event']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
