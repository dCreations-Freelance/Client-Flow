<?php

use App\Models\AgentTemplate;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla pivot `project_agents` que asocia proyectos
 * con templates de agentes IA.
 *
 * Decisiones de diseno:
 * - Se modela como tabla propia (no `belongsToMany` con attach
 *   directo) porque necesitamos editar `system_prompt_override`
 *   por asignacion: un mismo template puede tener system prompts
 *   distintos segun el proyecto donde se use, y eso no encaja en
 *   un pivot plano. Ademas, prevemos que en fases futuras se
 *   anadan columnas (e.g. `assigned_by`, `is_active`,
 *   `display_order` por proyecto) y partir de un modelo
 *   `ProjectAgent` es mas comodo.
 * - `system_prompt_override` es `longText` y nullable: si esta
 *   vacio, el IDE cliente debe usar el `system_prompt` del
 *   template; si trae texto, ese texto manda. La logica de
 *   "prompt efectivo" vive en `ProjectAgent::effectiveSystemPrompt()`.
 * - El unique `(project_id, agent_template_id)` impide asignar
 *   el mismo template dos veces al mismo proyecto. Se valida
 *   tambien en el Form Request con `Rule::unique` para devolver
 *   un 422 limpio en vez de un 500 por violacion de constraint.
 * - `cascadeOnDelete` en ambas FK: si se borra un proyecto o un
 *   template, sus asignaciones desaparecen. Esto evita filas
 *   huerfanas que ensucien `withCount` y los endpoints de export.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('project_agents', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Project::class, 'project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignIdFor(AgentTemplate::class, 'agent_template_id')
                ->constrained('agent_templates')
                ->cascadeOnDelete();

            $table->longText('system_prompt_override')->nullable();

            $table->timestamps();

            // Un template se asigna a un proyecto una sola vez.
            $table->unique(['project_id', 'agent_template_id']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_agents');
    }
};
