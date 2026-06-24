<?php

use App\Enums\DocumentVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `project_template_documents` para
 * los documentos "esqueleto" que se copian al
 * aplicar la plantilla.
 *
 * Decisiones:
 * - `template_id` con `cascadeOnDelete`.
 * - `visibility` con default `private` (es el caso
 *   habitual: documentacion interna del equipo).
 *   El admin puede ponerlo a `public` si la
 *   plantilla incluye docs para clientes.
 * - `position` para preservar el orden de los docs
 *   al copiarlos.
 * - `content` como `longtext` (mismo tipo que en
 *   `project_documents`) para admitir markdown
 *   extenso.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('project_template_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')
                ->constrained('project_templates')
                ->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('visibility')
                ->default(DocumentVisibility::Private->value);
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
        Schema::dropIfExists('project_template_documents');
    }
};
