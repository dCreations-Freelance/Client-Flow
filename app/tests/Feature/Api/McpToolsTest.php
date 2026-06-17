<?php

namespace Tests\Feature\Api;

use App\Models\BoardColumn;
use App\Models\McpMessage;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests de invocacion de tools del MCP server via JSON-RPC.
 */
class McpToolsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, ['mcp:read']);

        return $admin;
    }

    /**
     * Crea una sesion MCP y devuelve su session_id.
     */
    private function createSession(): string
    {
        $this->actingAsAdmin();

        $response = $this->get(route('api.mcp.sse'));
        $content = $response->streamedContent();

        preg_match('/\/api\/mcp\/messages\?session_id=([^\s]+)/', $content, $matches);

        $this->assertNotEmpty($matches[1] ?? null);

        return $matches[1];
    }

    /**
     * Envía un mensaje JSON-RPC a la sesion y devuelve la respuesta
     * encolada por el servidor (recuperada directamente de BD para
     * evitar depender del stream SSE en tests).
     *
     * @param  string  $sessionId
     * @param  string  $method
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function sendMessage(string $sessionId, string $method, array $params = []): array
    {
        $this->postJson(
            route('api.mcp.messages', ['session_id' => $sessionId]),
            [
                'jsonrpc' => '2.0',
                'id' => uniqid(),
                'method' => $method,
                'params' => $params,
            ]
        )->assertOk();

        $session = \App\Models\McpSession::where('session_id', $sessionId)->firstOrFail();

        // Recuperamos el ultimo mensaje encolado para esta sesion.
        $message = McpMessage::query()
            ->where('mcp_session_id', $session->id)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($message, 'No se encolo ninguna respuesta MCP.');

        return $message->payload;
    }

    /**
     * tools/list anuncia las 7 tools esperadas.
     */
    public function test_tools_list_anuncia_las_tools_esperadas(): void
    {
        $sessionId = $this->createSession();

        $data = $this->sendMessage($sessionId, 'tools/list');

        $this->assertArrayHasKey('result', $data);
        $toolNames = array_column($data['result']['tools'], 'name');
        $this->assertContains('list_projects', $toolNames);
        $this->assertContains('get_project', $toolNames);
        $this->assertContains('list_tasks', $toolNames);
        $this->assertContains('get_task', $toolNames);
        $this->assertContains('get_documents', $toolNames);
        $this->assertContains('search_documents', $toolNames);
        $this->assertContains('get_project_status', $toolNames);
    }

    /**
     * list_projects devuelve los proyectos del admin.
     */
    public function test_list_projects_devuelve_proyectos(): void
    {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create();
        $project->organization->members()->attach($admin->id, ['role' => 'owner']);

        $sessionId = $this->createSession();
        $data = $this->sendMessage($sessionId, 'tools/call', [
            'name' => 'list_projects',
            'arguments' => ['limit' => 10],
        ]);

        $result = json_decode($data['result']['content'][0]['text'], true);
        $this->assertSame(1, $result['total']);
        $this->assertSame($project->id, $result['projects'][0]['id']);
    }

    /**
     * get_project devuelve el detalle completo de un proyecto.
     */
    public function test_get_project_devuelve_detalle(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $sessionId = $this->createSession();
        $data = $this->sendMessage($sessionId, 'tools/call', [
            'name' => 'get_project',
            'arguments' => ['project_id' => $project->id],
        ]);

        $result = json_decode($data['result']['content'][0]['text'], true);
        $this->assertSame($project->id, $result['id']);
        $this->assertSame($project->name, $result['name']);
    }

    /**
     * list_tasks devuelve las tareas de un proyecto.
     */
    public function test_list_tasks_devuelve_tareas(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]);

        $sessionId = $this->createSession();
        $data = $this->sendMessage($sessionId, 'tools/call', [
            'name' => 'list_tasks',
            'arguments' => ['project_id' => $project->id],
        ]);

        $result = json_decode($data['result']['content'][0]['text'], true);
        $this->assertSame(3, $result['total']);
    }

    /**
     * get_task devuelve el detalle de una tarea.
     */
    public function test_get_task_devuelve_detalle(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]);

        $sessionId = $this->createSession();
        $data = $this->sendMessage($sessionId, 'tools/call', [
            'name' => 'get_task',
            'arguments' => ['task_id' => $task->id],
        ]);

        $result = json_decode($data['result']['content'][0]['text'], true);
        $this->assertSame($task->id, $result['id']);
        $this->assertSame($task->title, $result['title']);
    }

    /**
     * get_documents incluye documentos privados.
     */
    public function test_get_documents_incluye_privados(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $publicDoc = ProjectDocument::factory()->create([
            'project_id' => $project->id,
            'visibility' => 'public',
        ]);
        $privateDoc = ProjectDocument::factory()->create([
            'project_id' => $project->id,
            'visibility' => 'private',
        ]);

        $sessionId = $this->createSession();
        $data = $this->sendMessage($sessionId, 'tools/call', [
            'name' => 'get_documents',
            'arguments' => ['project_id' => $project->id],
        ]);

        $result = json_decode($data['result']['content'][0]['text'], true);
        $this->assertSame(2, $result['total']);
        $this->assertContains($publicDoc->id, array_column($result['documents'], 'id'));
        $this->assertContains($privateDoc->id, array_column($result['documents'], 'id'));
    }

    /**
     * search_documents encuentra documentos por contenido.
     */
    public function test_search_documents_encuentra_por_contenido(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $document = ProjectDocument::factory()->create([
            'project_id' => $project->id,
            'title' => 'Guia de instalacion',
            'content' => 'Instrucciones detalladas para instalar el sistema.',
        ]);

        $sessionId = $this->createSession();
        $data = $this->sendMessage($sessionId, 'tools/call', [
            'name' => 'search_documents',
            'arguments' => [
                'project_id' => $project->id,
                'query' => 'instalar',
            ],
        ]);

        $result = json_decode($data['result']['content'][0]['text'], true);
        $this->assertSame(1, $result['total']);
        $this->assertSame($document->id, $result['documents'][0]['id']);
    }

    /**
     * get_project_status devuelve el resumen esperado.
     */
    public function test_get_project_status_devuelve_resumen(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        Task::factory()->completed()->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]);

        $sessionId = $this->createSession();
        $data = $this->sendMessage($sessionId, 'tools/call', [
            'name' => 'get_project_status',
            'arguments' => ['project_id' => $project->id],
        ]);

        $result = json_decode($data['result']['content'][0]['text'], true);
        $this->assertSame($project->id, $result['project']['id']);
        $this->assertSame(2, $result['tasks']['total']);
        $this->assertSame(1, $result['tasks']['completed']);
        $this->assertSame(1, $result['tasks']['pending']);
    }
}
