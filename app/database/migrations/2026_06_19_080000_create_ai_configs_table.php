<?php

use App\Enums\AiProvider;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `ai_configs` que almacena la configuracion de los
 * proveedores de IA disponibles en ClientFlow.
 *
 * Decisiones:
 * - `project_id` es nullable: una fila con `project_id = null` es la
 *   configuracion global (fallback) usada cuando un proyecto no tiene
 *   su propia configuracion. Se declara unica para que solo exista
 *   una fila global y, como maximo, una fila por proyecto.
 * - `provider` se persiste como string y se castea en el modelo al
 *   enum `AiProvider`. Se indexa porque el admin la filtrara por
 *   proveedor en el panel de settings.
 * - `api_key` se cifra en reposo con el cast `encrypted` de Eloquent.
 *   En BD se almacena como `longText` porque Laravel aplica CBC + HMAC
 *   y el ciphertext es mas largo que el texto plano.
 * - `model` es opcional. Si el admin no lo rellena, `AiService` usa
 *   el modelo por defecto del provider (`AiProvider::defaultModel()`).
 * - `system_prompt` permite al admin sobreescribir el system prompt
 *   base. Si esta vacio, `ProjectContextBuilder` genera uno en
 *   castellano con el contexto del proyecto.
 * - `max_messages_per_hour` y `max_sessions_per_day` parametrizan
 *   `AiRateLimiter` por configuracion, no de forma global.
 * - `is_active` permite al admin "apagar" una configuracion sin
 *   borrarla. Una config inactiva no se usa.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('ai_configs', function (Blueprint $table): void {
            $table->id();

            // FK al proyecto: una fila por proyecto (config por
            // proyecto) o null (config global). `nullOnDelete` no
            // aplica porque nunca se borra un proyecto sin borrarse
            // antes su config, pero se deja cascade por simetria
            // con el resto del modelo.
            $table->foreignIdFor(Project::class, 'project_id')
                ->nullable()
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->string('provider')
                ->default(AiProvider::Openai->value)
                ->index();

            // La API key se cifra en el modelo con `encrypted`. Aqui
            // solo declaramos el contenedor; el cifrado ocurre al
            // escribir desde PHP.
            $table->text('api_key');

            $table->string('model')->nullable();

            $table->text('system_prompt')->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->unsignedSmallInteger('max_messages_per_hour')
                ->default(20);

            $table->unsignedSmallInteger('max_sessions_per_day')
                ->default(10);

            $table->timestamps();

            // Solo puede haber una config por proyecto (o una
            // global con project_id null). Esto lo refuerza
            // `AiConfig::booted()` ademas del constraint.
            $table->unique('project_id');
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_configs');
    }
};
