<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `agent_templates` que almacena la biblioteca de
 * templates de agentes IA configurados por el administrador de
 * la instancia.
 *
 * Decisiones de diseno:
 * - `name` se acota a 120 caracteres y se indexa dentro del indice
 *   compuesto `(category, name)` para soportar ordenaciones y
 *   busquedas por categoria + nombre de forma eficiente.
 * - `description` y `system_prompt` son los campos editoriales:
 *   la primera se renderiza en cards de resumen y el segundo se
 *   exporta tal cual al IDE del desarrollador.
 * - `tools` se almacena como JSON libre. La validacion del esquema
 *   concreto (e.g. formato MCP) vive en el Form Request, no en la
 *   base de datos, porque los IDEs clientes imponen formatos
 *   heterogeneos y no queremos atar el almacenamiento a ninguno.
 * - `model` es opcional. Si esta vacio, el IDE cliente puede usar
 *   su propio modelo por defecto; en ClientFlow no se interpreta.
 * - `category` se indexa porque el listado admin permite filtrar
 *   por categoria.
 * - `created_by` apunta al admin que dio de alta el template. Es
 *   solo trazabilidad: en MVP la biblioteca es operativa del admin
 *   y no hay flujo de "templates publicados por un usuario" o
 *   "templates favoritos".
 *
 * Los clientes del portal NO consumen esta tabla: el modelo de
 * negocio es admin-only. La relacion con proyectos vive en
 * `project_agents` (migracion siguiente) y es donde el cliente
 * podria, en una fase futura, ver los agentes asignados a sus
 * proyectos. Por ahora esa vista es tambien admin-only.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('agent_templates', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 120);

            $table->text('description')->nullable();

            $table->longText('system_prompt');

            $table->json('tools')->nullable();

            $table->string('model', 120)->nullable();

            $table->string('category', 60)->nullable()->index();

            $table->foreignIdFor(User::class, 'created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            // Ordenacion canonica dentro de cada categoria.
            $table->index(['category', 'name']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_templates');
    }
};
