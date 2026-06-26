<?php

namespace Tests\Feature\Api;

use App\Models\McpSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Verifica que un admin no puede usar el `session_id` de otro admin
 * para inyectar mensajes en su stream SSE (auditoria H-01).
 */
class McpSessionOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_no_puede_usar_session_id_de_otro_admin(): void
    {
        $owner = User::factory()->admin()->create();
        $attacker = User::factory()->admin()->create();

        $session = McpSession::create([
            'user_id' => $owner->id,
            'session_id' => 'session-de-owner',
            'last_activity_at' => now(),
        ]);

        Sanctum::actingAs($attacker, ['mcp:read']);

        // Mismo endpoint, session_id ajeno: 404 generico.
        $this->postJson(
            route('api.mcp.messages').'?session_id='.$session->session_id,
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']
        )->assertNotFound();

        // La sesion del owner sigue existiendo y no se ha encolado
        // ningun mensaje del atacante.
        $this->assertDatabaseHas('mcp_sessions', [
            'id' => $session->id,
            'user_id' => $owner->id,
        ]);
        $this->assertDatabaseCount('mcp_messages', 0);
    }

    public function test_admin_si_puede_usar_su_propio_session_id(): void
    {
        $admin = User::factory()->admin()->create();

        $session = McpSession::create([
            'user_id' => $admin->id,
            'session_id' => 'session-de-admin',
            'last_activity_at' => now(),
        ]);

        Sanctum::actingAs($admin, ['mcp:read']);

        $this->postJson(
            route('api.mcp.messages').'?session_id='.$session->session_id,
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']
        )->assertOk();
    }
}
