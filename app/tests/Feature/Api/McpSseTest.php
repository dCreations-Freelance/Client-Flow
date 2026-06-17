<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests del handshake SSE del MCP server.
 */
class McpSseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El handshake SSE anuncia el endpoint de mensajes y envia la
     * respuesta de inicializacion del servidor.
     */
    public function test_sse_handshake_anuncia_endpoint_de_mensajes(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, ['mcp:read']);

        $response = $this->get(route('api.mcp.sse'));
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('event: endpoint', $content);
        $this->assertStringContainsString('/api/mcp/messages?session_id=', $content);
        $this->assertStringContainsString('event: message', $content);
        $this->assertStringContainsString('clientflow-mcp', $content);
    }
}
