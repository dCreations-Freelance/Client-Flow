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
     * Crea la tabla de sesiones activas del MCP server.
     *
     * Se usa como mecanismo de correlacion entre el stream SSE y
     * los POST de JSON-RPC: el handler de mensajes encola la
     * respuesta aqui, y el loop SSE la recupera y la emite.
     */
    public function up(): void
    {
        Schema::create('mcp_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('session_id')->unique();
            $table->timestamp('last_activity_at');
            $table->timestamps();

            $table->index(['session_id', 'last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcp_sessions');
    }
};
