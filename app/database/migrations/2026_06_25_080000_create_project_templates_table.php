<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `project_templates` para la biblioteca
 * de plantillas reutilizables.
 *
 * Cada plantilla es un "esqueleto" de proyecto: un
 * conjunto de columnas, tareas predefinidas y
 * documentos iniciales. Al aplicar una plantilla, se
 * copian todos esos elementos a un proyecto nuevo.
 *
 * Decisiones de diseno:
 * - `name` y `slug` son obligatorios. El slug se
 *   genera automaticamente (igual que en `projects`)
 *   y se usa en URLs amigables.
 * - `description` opcional como texto largo
 *   (markdown) para que el admin documente en que
 *   consiste la plantilla.
 * - `category` opcional e indexado: las plantillas
 *   se agrupan por categoria (web, mobile, design,
 *   etc.) y el listado se puede filtrar por ella.
 *   Se permite texto libre para que el admin no este
 *   limitado a un catalogo cerrado.
 * - `created_by` con `restrictOnDelete` para que no
 *   se pueda borrar un admin que ha creado plantillas
 *   sin reasignar primero.
 * - Sin `organization_id`: las plantillas son
 *   globales (de la biblioteca del admin), no de
 *   una organizacion concreta. Si en una fase
 *   futura se quiere multi-tenant, se anade esta
 *   columna con una migracion posterior.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('project_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->nullable()->index();
            $table->foreignIdFor(User::class, 'created_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_templates');
    }
};
