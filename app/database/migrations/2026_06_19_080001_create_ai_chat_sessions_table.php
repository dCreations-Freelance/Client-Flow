<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `ai_chat_sessions` que agrupa los mensajes
 * intercambiados entre un usuario y el asistente IA dentro
 * del contexto de un proyecto.
 *
 * Decisiones:
 * - `project_id` y `user_id` con `cascadeOnDelete`: si se
 *   borra el proyecto o el usuario, sus sesiones se eliminan.
 *   Para un MVP es aceptable; en produccion podria optarse
 *   por `nullOnDelete` en `user_id` para preservar el
 *   historial.
 * - `title` es opcional. El admin puede ver las sesiones con
 *   un titulo autogenerado (primeros 60 caracteres del primer
 *   mensaje) o un titulo puesto por el usuario.
 * - Indice compuesto `(user_id, project_id)` porque el patron
 *   de consulta mas frecuente es "sesiones de este usuario en
 *   este proyecto" (sidebar del chat).
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('ai_chat_sessions', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Project::class, 'project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class, 'user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'project_id']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_sessions');
    }
};
