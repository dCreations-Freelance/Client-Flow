<?php

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `activity_log` que almacena el feed cronologico
 * de eventos del proyecto.
 *
 * Cada fila es un "suceso" discreto: una tarea creada, un
 * documento publicado, un cambio de estado, un mensaje humano
 * enviado, etc. El feed (admin y portal) consume esta tabla con
 * paginacion y filtros por categoria; en paralelo, cada evento
 * genera tambien un `ProjectMessage::system` en el chat del
 * proyecto (escrito por `ActivityLogger`). De ahi la mencion
 * a "doble persistencia" en la documentacion.
 *
 * Decisiones de diseno:
 *
 * - `project_id` y `organization_id` son ambos nullable: la mayoria
 *   de eventos pertenecen a un proyecto, pero dejamos la puerta
 *   abierta a eventos cross-project en una fase futura (por
 *   ejemplo, "se creo la organizacion X") sin necesidad de
 *   migracion. `cascadeOnDelete` para que borrar un proyecto borre
 *   tambien su feed.
 * - `user_id` nullable con `nullOnDelete`: un evento del sistema
 *   (por ejemplo, un job automatico) no tiene autor humano, y si
 *   en una fase futura se borra un admin, conservamos su feed con
 *   autor "desconocido" en lugar de perder historial.
 * - `type` como string (no enum de BD) para que anadir un caso al
 *   enum `ActivityType` no requiera migracion. Indexado por
 *   frecuencia de filtrado.
 * - `description` es el texto legible que muestra el feed. Se
 *   genera con sprintf en `ActivityLogger` (ej: `"Daniel creo la
 *   tarea Login"`). String acotado (255) para mantener el feed
 *   visualmente consistente.
 * - `subject_type` + `subject_id` polimorficos: permiten enlazar
 *   el evento a su "sujeto" real (la tarea concreta, el documento,
 *   el evento de calendario) para que el feed pinte un link
 *   directo. Indice compuesto para que el morphTo sea eficiente.
 * - `properties` json libre: para datos especificos de cada tipo
 *   (columna origen/destino, old/new status, visibility). Asi
 *   anadir un dato nuevo no requiere migracion de columna.
 * - `created_at` indexado desc: el feed se renderiza del mas
 *   reciente al mas antiguo; el indice evita filesort.
 *
 * Indices principales:
 * - `(project_id, id)`: paginacion del feed por proyecto.
 * - `(organization_id, id)`: feed agregado por org (fase futura).
 * - `type`: filtro por tipo de evento (admin).
 * - `(subject_type, subject_id)`: resolver el morphTo.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();

            // FK al proyecto: nullable porque permitimos eventos a
            // nivel organizacion (futuro) o del sistema. Cascade
            // para que al borrar un proyecto se borre su feed.
            $table->foreignIdFor(Project::class, 'project_id')
                ->nullable()
                ->constrained('projects')
                ->cascadeOnDelete();

            // FK a la organizacion: nullable para simetria con
            // project_id. Cascade por la misma razon.
            $table->foreignIdFor(Organization::class, 'organization_id')
                ->nullable()
                ->constrained('organizations')
                ->cascadeOnDelete();

            // FK al autor: nullOnDelete para que un usuario borrado
            // no destruya el feed historico.
            $table->foreignIdFor(User::class, 'user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Tipo de evento: valor del enum `ActivityType`.
            // Indexado por frecuencia de filtrado.
            $table->string('type')->index();

            // Texto legible: "Daniel creo la tarea Login".
            $table->string('description', 255);

            // Subject polimorfico: el modelo concreto al que
            // apunta el evento (Task, ProjectDocument, etc.).
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Datos especificos del evento en JSON libre.
            $table->json('properties')->nullable();

            // `created_at` explicito (no usamos `timestamps()` porque
            // los eventos del feed son inmutables: nunca se
            // actualizan). Indexado para que el ORDER BY en el
            // feed sea eficiente.
            $table->timestamp('created_at')->index();

            // Indice principal: feed por proyecto. La paginacion
            // keyset sobre `id` aprovecha que `id` es monótonamente
            // creciente y equivalente a `created_at` en orden.
            $table->index(['project_id', 'id']);

            // Indice polimorfico: resolver el morphTo.
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
