<?php

use App\Enums\DocumentVisibility;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `project_documents` que almacena los documentos
 * markdown de cada proyecto.
 *
 * - `content` es longtext porque los documentos pueden ser largos
 *   (manuales, documentacion tecnica, etc.) y evitamos quedarnos
 *   cortos en MySQL.
 * - `visibility` es un enum con dos valores: `private` (solo admin)
 *   y `public` (visible para clientes del proyecto).
 * - `created_by` mantiene la autoria; se usa `restrictOnDelete` para
 *   no perder trazabilidad si se intenta borrar un usuario con
 *   documentos: en ese caso el admin debe reasignar primero.
 * - `project_id` se elimina en cascada con el proyecto, coherente
 *   con el resto del modelo de datos.
 *
 * Indices:
 * - `(project_id, visibility)`: patron de filtrado mas comun
 *   (listar docs de un proyecto, filtrar por visibilidad).
 * - `created_by` como FK indexada por defecto.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table): void {
            $table->id();

            // FK al proyecto: cascade para que al borrar un proyecto
            // se borren sus documentos y no queden filas huerfanas.
            $table->foreignIdFor(Project::class, 'project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->string('title', 200);

            // Contenido markdown del documento. Longtext admite hasta
            // 4GB en MySQL; suficiente para cualquier manual de
            // proyecto realista.
            $table->longText('content');

            // Visibilidad: indice porque se filtra con frecuencia.
            $table->string('visibility')
                ->default(DocumentVisibility::Private->value)
                ->index();

            // Autoria: restrict para que un borrado accidental de
            // usuario no destruya documentos historicos.
            $table->foreignIdFor(User::class, 'created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            // Indice compuesto principal: listar docs por proyecto y
            // opcionalmente filtrar por visibilidad.
            $table->index(['project_id', 'visibility']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_documents');
    }
};
