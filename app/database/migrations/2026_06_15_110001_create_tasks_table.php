<?php

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla `tasks` segun `docs/DATA_MODEL.md`. La columna
     * `parent_id` permite anidar subtareas; usamos `cascadeOnDelete`
     * para que al borrar el padre se lleve sus subtareas (politica
     * decidida en el plan: cascade por simplicidad).
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();
            $table->foreignId('column_id')
                ->constrained('board_columns')
                ->cascadeOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('tasks')
                ->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')
                ->default(TaskPriority::Medium->value)
                ->index();
            $table->string('type')
                ->default(TaskType::Task->value)
                ->index();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->decimal('actual_hours', 6, 2)->nullable();
            $table->date('due_date')->nullable()->index();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('assignee_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index(['column_id', 'position']);
            $table->index(['project_id', 'column_id']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
