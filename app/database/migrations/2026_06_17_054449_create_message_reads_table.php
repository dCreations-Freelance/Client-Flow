<?php

use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Crea el pivot que registra quien ha visto cada mensaje.
     *
     * A diferencia de `project_chat_reads` (que guarda solo el
     * ultimo mensaje leido por usuario/proyecto), esta tabla
     * permite saber si un mensaje concreto ha sido visto por
     * alguien mas, lo que alimenta el doble check del chat.
     */
    public function up(): void
    {
        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProjectMessage::class, 'message_id')->constrained('project_messages')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_reads');
    }
};
