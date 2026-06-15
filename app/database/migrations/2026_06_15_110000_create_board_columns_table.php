<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla `board_columns` segun `docs/DATA_MODEL.md`. Las
     * columnas son configurables por proyecto: nombre, color
     * opcional, posicion y un flag `is_default` para distinguir las
     * columnas creadas automaticamente al crear un proyecto de las
     * que el admin anade despues.
     */
    public function up(): void
    {
        Schema::create('board_columns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'position']);
            $table->unique(['project_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_columns');
    }
};
