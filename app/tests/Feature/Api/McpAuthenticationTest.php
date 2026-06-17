<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests de autenticacion del MCP server.
 *
 * Verifican que solo los tokens validos de administradores pueden
 * acceder a los endpoints MCP, y que clientes o tokens invalidos
 * reciben 401/403.
 */
class McpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un token invalido recibe 401 en el endpoint SSE.
     */
    public function test_token_invalido_recibe_401_en_sse(): void
    {
        $this->getJson(route('api.mcp.sse'), [
            'Authorization' => 'Bearer token-falso',
        ])->assertUnauthorized();
    }

    /**
     * Un token invalido recibe 401 en el endpoint messages.
     */
    public function test_token_invalido_recibe_401_en_messages(): void
    {
        $this->postJson(route('api.mcp.messages'), [], [
            'Authorization' => 'Bearer token-falso',
        ])->assertUnauthorized();
    }

    /**
     * Un cliente autenticado no puede usar el MCP server.
     */
    public function test_cliente_autenticado_recibe_403(): void
    {
        $client = User::factory()->client()->create();
        Sanctum::actingAs($client, ['mcp:read']);

        $this->getJson(route('api.mcp.sse'))->assertForbidden();
        $this->postJson(route('api.mcp.messages'))->assertForbidden();
    }

    /**
     * Un admin autenticado puede acceder al endpoint SSE.
     */
    public function test_admin_autenticado_puede_acceder_a_sse(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, ['mcp:read']);

        $response = $this->get(route('api.mcp.sse'));
        $response->assertOk();
        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
    }
}
