<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `project_template_columns` para las
 * columnas predefinidas que copia una plantilla al
 * aplicarse a un proyecto.
 *
 * Decisiones:
 * - `template_id` con `cascadeOnDelete`: borrar la
 *   plantilla borra sus columnas.
 * - Sin `slug`: las tareas de la plantilla se ligan
 *   a la columna por `position` (que es estable
 *   durante la creacion de la plantilla), no por
 *   slug. Asi simplificamos: la columna copiada
 *   mantiene su `position` y las tareas se mapean
 *   a la columna real del proyecto por su
 *   `column_position` (campo en la tabla de tareas
 *   predefinidas; ver siguiente migracion).
 * - `is_default = false` en las columnas copiadas
 *   para distinguirlas de las 4 columnas canonicas
 *   de `DefaultBoardColumnsService`. Asi, si en
 *   una fase futura el admin quiere "resetear a
 *   las columnas por defecto", sabe cuales son
 *   las suyas y cuales son las del sistema.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('project_template_columns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')
                ->constrained('project_templates')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['template_id', 'position']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_template_columns');
    }
};
