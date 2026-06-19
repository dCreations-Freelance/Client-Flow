<?php

namespace App\Livewire\Admin\AiConfig;

use App\Enums\AiProvider;
use App\Models\AiConfig;
use App\Models\Project;
use App\Services\Ai\AiService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use RuntimeException;

/**
 * Formulario de configuracion de la IA en el panel admin.
 *
 * Se monta en `/admin/settings/ai` y permite al admin
 * crear/editar la configuracion global o la especifica
 * de un proyecto.
 *
 * Comportamiento:
 * - Carga la config aplicable al `projectId` recibido
 *   como propiedad; si no existe, inicializa con valores
 *   por defecto coherentes con `config/ai.php`.
 * - Envia la peticion `update` por AJAX a la URL del
 *   formulario, sin recargar la pagina.
 * - El boton "Probar conexion" usa `AiService::testConnection()`
 *   y muestra un flash con el resultado.
 *
 * La API key se persiste vacia al renderizar si la config
 * ya tiene una clave: asi el admin ve el formulario sin
 * la clave rellena. Si deja el campo vacio al guardar, se
 * conserva la clave anterior (lo gestiona el
 * controlador).
 */
class SettingsForm extends Component
{
    use AuthorizesRequests;

    /**
     * ID del proyecto al que aplica esta configuracion.
     * `null` = configuracion global.
     *
     * @var int|null
     */
    public ?int $projectId = null;

    /**
     * Provider seleccionado en el formulario.
     *
     * @var string
     */
    public string $provider = 'openai';

    /**
     * API key en texto plano durante la edicion. Se
     * envia vacia si el admin no la quiere cambiar.
     *
     * @var string
     */
    public string $apiKey = '';

    /**
     * Modelo opcional. Vacio = usar el del provider.
     *
     * @var string
     */
    public string $model = '';

    /**
     * System prompt opcional que sobreescribe al
     * autogenerado por `ProjectContextBuilder`.
     *
     * @var string
     */
    public string $systemPrompt = '';

    /**
     * Indica si la configuracion esta activa.
     *
     * @var bool
     */
    public bool $isActive = true;

    /**
     * Limite horario de mensajes.
     *
     * @var int
     */
    public int $maxMessagesPerHour = 20;

    /**
     * Limite diario de sesiones nuevas.
     *
     * @var int
     */
    public int $maxSessionsPerDay = 10;

    /**
     * Resultado del ultimo "Probar conexion" (se muestra
     * como flash al usuario). `null` = sin probar aun.
     *
     * @var array{ok: bool, message: string}|null
     */
    public ?array $testResult = null;

    /**
     * Inicializa el formulario a partir de la URL
     * (`?project_id=`) o de la fila persistida.
     *
     * `projectId = 0` se trata como `null` (config
     * global) porque `request()->integer('project_id')`
     * devuelve `0` cuando el parametro no esta presente
     * y `0` como project_id no matchearia ninguna fila
     * real.
     */
    public function mount(?int $projectId = null): void
    {
        $this->authorize('viewAny', AiConfig::class);

        $this->projectId = ($projectId === null || $projectId === 0) ? null : $projectId;

        $config = $this->loadConfig();
        if ($config !== null) {
            $this->fillFromConfig($config);
        } else {
            $this->fillDefaults();
        }
    }

    /**
     * Carga la `AiConfig` aplicable al `projectId` actual.
     * `null` si no existe fila todavia (caso del primer
     * guardado).
     */
    private function loadConfig(): ?AiConfig
    {
        $query = AiConfig::query();
        if ($this->projectId !== null) {
            $query->where('project_id', $this->projectId);
        } else {
            $query->whereNull('project_id');
        }

        return $query->first();
    }

    /**
     * Hidrata las propiedades del componente desde una
     * fila persistida. La API key no se rellena: nunca
     * debe llegar al frontend.
     */
    private function fillFromConfig(AiConfig $config): void
    {
        $provider = $config->provider;
        $this->provider = $provider instanceof AiProvider
            ? $provider->value
            : (string) ($provider ?? AiProvider::Openai->value);
        $this->apiKey = '';
        $this->model = (string) ($config->model ?? '');
        $this->systemPrompt = (string) ($config->system_prompt ?? '');
        $this->isActive = (bool) $config->is_active;
        $this->maxMessagesPerHour = (int) ($config->max_messages_per_hour ?: 20);
        $this->maxSessionsPerDay = (int) ($config->max_sessions_per_day ?: 10);
    }

    /**
     * Inicializa las propiedades con valores por defecto
     * coherentes con `config/ai.php`.
     */
    private function fillDefaults(): void
    {
        $this->provider = AiProvider::Openai->value;
        $this->apiKey = '';
        $this->model = '';
        $this->systemPrompt = '';
        $this->isActive = true;
        $this->maxMessagesPerHour = (int) config('ai.default_max_messages_per_hour', 20);
        $this->maxSessionsPerDay = (int) config('ai.default_max_sessions_per_day', 10);
    }

    /**
     * Reglas de validacion del formulario. Coinciden con
     * `UpdateAiConfigRequest` para que el rechazo sea el
     * mismo desde Livewire o desde HTTP.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $providerValues = array_map(
            static fn (AiProvider $provider): string => $provider->value,
            AiProvider::cases(),
        );

        return [
            'projectId' => ['nullable', 'integer'],
            'provider' => ['required', 'in:'.implode(',', $providerValues)],
            'apiKey' => ['nullable', 'string', 'min:10', 'max:500'],
            'model' => ['nullable', 'string', 'max:120'],
            'systemPrompt' => ['nullable', 'string', 'max:8000'],
            'isActive' => ['boolean'],
            'maxMessagesPerHour' => ['required', 'integer', 'min:1', 'max:10000'],
            'maxSessionsPerDay' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /**
     * Persiste la configuracion. Delega en el metodo
     * `save()` del controlador, que recibe datos ya
     * validados (los de aqui pasan por `validate()`
     * antes). Asi evitamos construir un `FormRequest`
     * a mano, cosa que Laravel no soporta directamente
     * porque esas clases necesitan el `Request` real
     * con su container y dependencias inyectadas.
     *
     * Despues de guardar, recarga el estado desde la BD
     * para que el formulario refleje los valores
     * persistidos (no los que el usuario tecleo). Asi
     * el admin ve de inmediato lo que se ha guardado.
     */
    public function save(AiService $ai): void
    {
        $this->authorize('update', AiConfig::class);

        $data = $this->validate();

        $controller = app(\App\Http\Controllers\Admin\AiConfigController::class);
        $controller->save([
            'project_id' => $this->projectId,
            'provider' => $this->provider,
            'api_key' => $this->apiKey,
            'model' => $this->model !== '' ? $this->model : null,
            'system_prompt' => $this->systemPrompt !== '' ? $this->systemPrompt : null,
            'is_active' => $this->isActive,
            'max_messages_per_hour' => $this->maxMessagesPerHour,
            'max_sessions_per_day' => $this->maxSessionsPerDay,
        ]);

        // Recargamos el estado del componente desde la BD
        // para que la UI muestre exactamente lo que se ha
        // guardado. Sin esto, el componente mantiene los
        // valores en memoria que el usuario tecleo.
        $fresh = $this->loadConfig();
        if ($fresh !== null) {
            $this->fillFromConfig($fresh);
        }

        $this->dispatch('ai-config-saved');
    }

    /**
     * Lanza una peticion de prueba contra el provider
     * activo. Muestra el resultado en `$testResult` para
     * que la vista lo renderice inline.
     */
    public function testConnection(AiService $ai): void
    {
        $this->authorize('test', AiConfig::class);

        $config = $this->buildInMemoryConfig();

        $this->testResult = $ai->testConnection($config);
    }

    /**
     * Construye una `AiConfig` en memoria con los valores
     * actuales del formulario, sin persistirla. Util para
     * `testConnection()` sin necesidad de guardar antes.
     */
    private function buildInMemoryConfig(): AiConfig
    {
        $config = new AiConfig();
        $config->project_id = $this->projectId;
        $config->provider = AiProvider::from($this->provider);
        $config->api_key = $this->apiKey !== '' ? $this->apiKey : 'sk-placeholder';
        $config->model = $this->model !== '' ? $this->model : null;
        $config->system_prompt = $this->systemPrompt !== '' ? $this->systemPrompt : null;
        $config->is_active = $this->isActive;
        $config->max_messages_per_hour = $this->maxMessagesPerHour;
        $config->max_sessions_per_day = $this->maxSessionsPerDay;

        return $config;
    }

    /**
     * Vista Blade del componente. La vista recoge la
     * lista de providers desde el enum y la lista de
     * proyectos desde la base de datos.
     */
    public function render(): View
    {
        return view('livewire.admin.ai-config.settings-form', [
            'providers' => AiProvider::cases(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
