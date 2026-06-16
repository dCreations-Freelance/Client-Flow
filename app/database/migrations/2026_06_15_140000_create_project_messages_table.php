<?php

use App\Enums\MessageType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `project_messages` que almacena los mensajes del
 * chat por proyecto.
 *
 * - `content` es `text` y no `longtext` porque los mensajes de
 *   chat rara vez superan los 65KB y mantener un tamano acotado
 *   ayuda a perfilar consultas.
 * - `user_id` admite NULL porque los mensajes de sistema no tienen
 *   autor humano. Se usa `nullOnDelete` para que un usuario borrado
 *   no rompa hilos de conversacion: el mensaje queda con autor
 *   desconocido en lugar de eliminarse en cascada.
 * - `type` esta indexado para que el filtrado por `system`/`text`
 *   sea eficiente en el futuro (analitica, mod de moderacion).
 * - Indice compuesto `(project_id, id)` para resolver consultas
 *   "ultimo mensaje" y "no leidos hasta id X" en O(log n) sin
 *   filesort.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('project_messages', function (Blueprint $table): void {
            $table->id();

            // FK al proyecto: cascade para que al borrar un proyecto
            // se borre todo su historial.
            $table->foreignIdFor(Project::class, 'project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            // FK al autor: nullOnDelete para que un usuario borrado
            // no destruya mensajes; el contenido se conserva.
            $table->foreignIdFor(User::class, 'user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('content');

            // Tipo de mensaje: indexado para filtros.
            $table->string('type')
                ->default(MessageType::Text->value)
                ->index();

            $table->timestamps();

            // Indice principal: lista de mensajes por proyecto en
            // orden cronologico. Cubre las queries de "ultimo id"
            // y "no leidos hasta X" que usa el chat.
            $table->index(['project_id', 'id']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_messages');
    }
};
