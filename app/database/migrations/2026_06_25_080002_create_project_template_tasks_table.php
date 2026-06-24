<?php

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `project_template_tasks` para las
 * tareas predefinidas que se copian al aplicar la
 * plantilla.
 *
 * Decisiones:
 * - `template_id` con `cascadeOnDelete`.
 * - `column_position` (no `column_id`): al copiar
 *   la plantilla, las columnas del proyecto
 *   destino se crean en el mismo orden que en la
 *   plantilla. Asi, las tareas predefinidas
 *   pueden referirse a la columna por su
 *   `position` (que es estable mientras no se
 *   reordenen las columnas en la plantilla). Esto
 *   evita tener que reescribir la tarea si la
 *   columna cambia de nombre.
 * - `type` y `priority` como strings con los
 *   valores de los enums correspondientes. La
 *   validacion se hace en Form Request.
 * - Sin `assignee_id`, `due_date`, `parent_id`,
 *   `completed_at`: son especificos del proyecto
 *   y no tiene sentido en una plantilla.
 * - `position` para el orden dentro de la columna
 *   destino (mismo concepto que en `tasks`).
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('project_template_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')
                ->constrained('project_templates')
                ->cascadeOnDelete();
            $table->unsignedInteger('column_position')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')
                ->default(TaskType::Task->value);
            $table->string('priority')
                ->default(TaskPriority::Medium->value);
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            // Indice compuesto con nombre corto explicito
            // para no superar el limite de 64 caracteres de
            // MySQL: el nombre autogenerado seria
            // "project_template_tasks_template_id_column_position_position_index"
            // (66 chars), lo que falla en MySQL con error
            // 1059. La consulta equivalente es el patron
            // "tareas de una plantilla en una columna, en
            // orden de position".
            $table->index(
                ['template_id', 'column_position', 'position'],
                'ptt_template_colpos_pos_idx',
            );
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_template_tasks');
    }
};
