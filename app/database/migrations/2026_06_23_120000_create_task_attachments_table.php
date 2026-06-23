<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `task_attachments` para almacenar los archivos
 * adjuntos de las tareas del kanban.
 *
 * Decisiones de diseno:
 * - `task_id` con `cascadeOnDelete`: si se borra la tarea, sus
 *   adjuntos desaparecen. Esto evita filas huerfanas y libera el
 *   espacio en disco. El borrado fisico del archivo se hace via
 *   el `AttachmentService` que se invoca desde un observer o un
 *   evento del modelo (ver `Task::deleting`).
 * - `user_id` con `restrictOnDelete`: la autoria de un adjunto es
 *   dato historico. Si un admin intenta borrar un usuario que
 *   subio adjuntos, MySQL rechaza la operacion para forzar a
 *   reasignar primero. Patron consistente con `project_documents`
 *   y `agent_templates.created_by`.
 * - `filename` es el nombre interno en disco (generado por el
 *   servicio para evitar colisiones y fugas de info del nombre
 *   original del usuario). `original_name` guarda el nombre
 *   legible que vera el usuario al descargar.
 * - `mime_type` se valida en la Form Request contra la lista
 *   permitida en `config/clientflow.php`. Aun asi se persiste
 *   para no tener que reabrir el archivo en cada descarga.
 * - `size` en bytes como unsigned bigint para admitir archivos
 *   grandes (aunque la validacion los limita a 10 MB por
 *   defecto).
 * - Indice compuesto `(task_id, created_at)` para acelerar el
 *   listado "adjuntos de una tarea ordenados por fecha" que es
 *   el patron de uso principal (vista de detalle de tarea).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_attachments', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Task::class, 'task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class, 'user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');

            $table->timestamps();

            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
    }
};
