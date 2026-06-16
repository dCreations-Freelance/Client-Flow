<?php

use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `project_chat_reads` para trackear el avance de
 * lectura del chat por proyecto y usuario.
 *
 * En lugar de un pivot por mensaje (que crece con cada lectura),
 * se guarda solo el id del ultimo mensaje leido por el usuario
 * en ese proyecto. Asi:
 * - El numero de no leidos se calcula como un `count()` con un
 *   simple `where id > last_read_message_id`.
 * - Insertar/actualizar es un upsert idempotente.
 *
 * `last_email_sent_at` se guarda aqui para que un futuro debounce
 * de notificaciones email (seccion "Transversal: Notificaciones")
 * tenga donde apoyarse sin necesidad de otra migracion.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('project_chat_reads', function (Blueprint $table): void {
            $table->id();

            // FK al proyecto: cascade porque si se borra el proyecto
            // ya no hay chat que leer.
            $table->foreignIdFor(Project::class, 'project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            // FK al usuario: cascade. Al borrar un usuario se
            // eliminan sus marcadores de lectura. Esto no borra
            // los mensajes (que usan nullOnDelete).
            $table->foreignIdFor(User::class, 'user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // FK al ultimo mensaje leido: nullOnDelete permite
            // borrar un mensaje sin invalidar el marcador (queda
            // apuntando a NULL).
            $table->foreignIdFor(ProjectMessage::class, 'last_read_message_id')
                ->nullable()
                ->constrained('project_messages')
                ->nullOnDelete();

            // Para debounce de emails (futuro). Nullable para
            // distinguir "nunca se le ha enviado email" de
            // "se le envio email en una fecha concreta".
            $table->timestamp('last_email_sent_at')->nullable();

            $table->timestamps();

            // Un usuario solo tiene un marcador por proyecto.
            $table->unique(['project_id', 'user_id']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_chat_reads');
    }
};
