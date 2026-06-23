<?php

use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `message_attachments` para los archivos que
 * acompanhan a un mensaje del chat de un proyecto.
 *
 * Decisiones de diseno:
 * - `message_id` con `cascadeOnDelete`: al borrar un mensaje se
 *   borran sus adjuntos. El borrado fisico lo coordina el evento
 *   `deleting` de `ProjectMessage` (ver su modelo).
 * - `user_id` con `restrictOnDelete`: autoria historica, mismo
 *   patron que `task_attachments` y `project_documents`. Un
 *   cliente que ha enviado un adjunto no puede ser borrado sin
 *   antes reasignar la autoria.
 * - `filename` (interno) + `original_name` (legible): evita
 *   exponer el path real del usuario y los caracteres raros en
 *   la URL de descarga.
 * - `mime_type` y `size` se persisten para no reabrir el archivo
 *   al pintar la lista de adjuntos o al servir la descarga.
 * - Indice compuesto `(message_id, created_at)` para que la vista
 *   del chat ("adjuntos de este mensaje en orden") sea O(1) sin
 *   escaneo lineal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(ProjectMessage::class, 'message_id')
                ->constrained('project_messages')
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class, 'user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');

            $table->timestamps();

            $table->index(['message_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
