<?php

use App\Enums\ProjectStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla `projects` segun `docs/DATA_MODEL.md`. Cubre los
     * campos necesarios para la fase 2 (CRUD, miembros, progreso y
     * archivo) y deja hueco para `cover_path` y futuros ajustes
     * (kanban, documentos, etc.) que se anadiran en fases siguientes.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')
                ->default(ProjectStatus::Planning->value)
                ->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->date('starts_at')->nullable();
            $table->date('estimated_ends_at')->nullable();
            $table->string('cover_path')->nullable();
            $table->boolean('is_visible_to_client')
                ->default(true)
                ->index();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
