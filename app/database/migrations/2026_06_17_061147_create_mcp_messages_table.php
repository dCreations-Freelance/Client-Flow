<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Crea la cola de mensajes SSE pendientes.
     *
     * Permite que el handler JSON-RPC (POST) deje respuestas para
     * que el loop SSE las recoja y emita, sin necesidad de Redis
     * ni WebSockets.
     */
    public function up(): void
    {
        Schema::create('mcp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mcp_session_id')->constrained('mcp_sessions')->cascadeOnDelete();
            $table->json('payload');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['mcp_session_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcp_messages');
    }
};
