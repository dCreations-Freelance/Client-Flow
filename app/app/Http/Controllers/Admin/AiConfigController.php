<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAiConfigRequest;
use App\Models\AiConfig;
use App\Services\Ai\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configuracion del modulo IA desde el panel admin.
 *
 * Expone tres endpoints:
 * - `edit`: formulario unificado para la config global y
 *   la de un proyecto concreto.
 * - `update`: persiste los cambios (recibe un
 *   `UpdateAiConfigRequest` que valida el input HTTP).
 * - `test`: lanza una peticion de prueba contra el
 *   provider activo y devuelve un flash con el resultado.
 *
 * Adicionalmente expone `save(array $data)`, un metodo
 * publico que recibe los datos ya validados. Se usa desde
 * el componente Livewire `SettingsForm`, donde no es
 * posible (ni deseable) construir un `FormRequest` real
 * con sus dependencias de Request/Container. La logica de
 * validacion se duplica en el componente (via `rules()`)
 * y aqui la validacion la hace `UpdateAiConfigRequest` en
 * HTTP y `validate()` en Livewire. Ambos llaman a
 * `save()` con la misma forma de datos.
 *
 * Decisiones:
 * - La API key nunca se devuelve al frontend: en la vista
 *   se renderiza un campo password vacio con marcador
 *   "sin cambios" si ya hay una clave persistida. Si el
 *   admin la deja vacia al guardar, se conserva la
 *   anterior.
 * - El listado de proyectos en el combo se calcula en
 *   cada request: son pocos y permite al admin crear
 *   una config especifica de un proyecto recien creado.
 */
class AiConfigController extends Controller
{
    /**
     * Muestra el formulario de configuracion. Si el admin
     * llega sin un `project_id` se trabaja sobre la fila
     * global; si llega con uno, sobre la config del
     * proyecto (creandola si no existe).
     */
    public function edit(Request $request, AiService $ai): View
    {
        $this->authorize('viewAny', AiConfig::class);

        $projectId = $request->integer('project_id') ?: null;

        $config = $this->resolveConfig($ai, $projectId);
        $projects = \App\Models\Project::orderBy('name')->get(['id', 'name']);

        return view('admin.settings.ai', [
            'config' => $config,
            'projectId' => $projectId,
            'projects' => $projects,
            'providers' => \App\Enums\AiProvider::cases(),
        ]);
    }

    /**
     * Persiste la configuracion desde una peticion HTTP.
     * La validacion la hace `UpdateAiConfigRequest`.
     */
    public function update(UpdateAiConfigRequest $request, AiService $ai): RedirectResponse
    {
        $this->authorize('update', AiConfig::class);

        $projectId = $this->save($request->validated());

        return redirect()
            ->route('admin.ai.config.edit', $projectId !== null ? ['project_id' => $projectId] : [])
            ->with('status', 'Configuracion de IA guardada.');
    }

    /**
     * Persiste la configuracion a partir de datos ya
     * validados. Pensado para ser invocado desde el
     * componente Livewire `SettingsForm` (que valida con
     * `validate()`) y desde el test feature.
     *
     * Devuelve el `project_id` resuelto para que el
     * llamador pueda redirigir o actualizar su estado.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): ?int
    {
        $projectId = isset($data['project_id']) && $data['project_id'] !== null
            ? (int) $data['project_id']
            : null;

        $config = $this->resolveConfig(null, $projectId);

        $payload = [
            'project_id' => $projectId,
            'provider' => $data['provider'],
            'model' => $data['model'] ?? null,
            'system_prompt' => $data['system_prompt'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'max_messages_per_hour' => (int) ($data['max_messages_per_hour'] ?? config('ai.default_max_messages_per_hour', 20)),
            'max_sessions_per_day' => (int) ($data['max_sessions_per_day'] ?? config('ai.default_max_sessions_per_day', 10)),
        ];

        // Solo actualizamos api_key si el admin escribio
        // una nueva. Esto evita tener que reescribir la
        // clave completa en cada guardado.
        $apiKey = $data['api_key'] ?? null;
        if (is_string($apiKey) && trim($apiKey) !== '') {
            $payload['api_key'] = trim($apiKey);
        }

        $config->fill($payload)->save();

        return $projectId;
    }

    /**
     * Ejecuta una peticion de prueba contra el provider
     * configurado. Pensado para el boton "Probar conexion".
     */
    public function test(Request $request, AiService $ai): RedirectResponse
    {
        $this->authorize('test', AiConfig::class);

        $projectId = $request->integer('project_id') ?: null;
        $config = $this->resolveConfig(null, $projectId);

        $result = $ai->testConnection($config);

        $flashKey = $result['ok'] ? 'status' : 'ai_test_error';

        return redirect()
            ->route('admin.ai.config.edit', $projectId !== null ? ['project_id' => $projectId] : [])
            ->with($flashKey, $result['message']);
    }

    /**
     * Devuelve la `AiConfig` aplicable. Si no existe la
     * fila (caso habitual en la primera apertura del
     * panel), devuelve una instancia vacia sin persistir
     * nada: el `save()` en `update()` la creara.
     */
    private function resolveConfig(?AiService $ai, ?int $projectId): AiConfig
    {
        $query = AiConfig::query();
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        } else {
            $query->whereNull('project_id');
        }

        $config = $query->first();

        if ($config !== null) {
            return $config;
        }

        // En lugar de devolver una instancia vacia que
        // pudiera parecer "configurada", devolvemos una
        // con valores por defecto razonables. Como
        // `timestamps = true`, Laravel le pone `created_at`
        // y `updated_at` automaticamente al persistir.
        $config = new AiConfig();
        $config->project_id = $projectId;
        $config->provider = \App\Enums\AiProvider::Openai;
        $config->is_active = true;
        $config->max_messages_per_hour = (int) config('ai.default_max_messages_per_hour', 20);
        $config->max_sessions_per_day = (int) config('ai.default_max_sessions_per_day', 10);
        // api_key queda vacia a proposito: el admin debe
        // rellenarla antes de poder probar la conexion.

        return $config;
    }
}
