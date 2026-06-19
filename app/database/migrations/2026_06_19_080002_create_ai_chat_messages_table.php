<?php

use App\Enums\AiChatRole;
use App\Models\AiChatSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `ai_chat_messages` que almacena cada mensaje
 * individual (usuario, asistente o system) dentro de una sesion
 * de chat con el asistente IA.
 *
 * Decisiones:
 * - `ai_chat_session_id` con `cascadeOnDelete`: borrar la sesion
 *   borra todos sus mensajes.
 * - `role` se persiste como string y se castea en el modelo al
 *   enum `AiChatRole`. Se indexa porque en el futuro se podra
 *   filtrar por tipo de mensaje.
 * - `content` es `text` y no `longText` porque los chats con IA
 *   rara vez superan los 64 KB por mensaje y, en caso de
 *   hacerlo, el modelo truncara la respuesta.
 * - `tokens_used` se persiste aunque el MVP no lo muestre en
 *   ningun panel. Asi no hay que migrar la tabla cuando se
 *   implemente un dashboard de consumo en una fase futura.
 * - Indice compuesto `(ai_chat_session_id, id)` para acelerar
 *   la carga cronologica del historial.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('ai_chat_messages', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(AiChatSession::class, 'ai_chat_session_id')
                ->constrained('ai_chat_sessions')
                ->cascadeOnDelete();

            $table->string('role')
                ->default(AiChatRole::User->value)
                ->index();

            $table->text('content');

            $table->unsignedInteger('tokens_used')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['ai_chat_session_id', 'id']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
