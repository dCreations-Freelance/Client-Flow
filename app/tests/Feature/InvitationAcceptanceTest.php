<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_accept_a_valid_client_invitation(): void
    {
        $client = Client::factory()->create([
            'email' => 'cliente@example.com',
            'invitation_status' => 'sent',
        ]);
        $invitation = ClientInvitation::factory()->create([
            'client_id' => $client->id,
            'email' => 'cliente@example.com',
            'created_by' => User::factory()->admin()->create()->id,
        ]);

        $response = $this->post(route('invitation.store', $invitation->token), [
            'name' => 'Cliente Activado',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'cliente@example.com',
            'role' => 'client',
        ]);
        $this->assertSame('accepted', $client->fresh()->invitation_status);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }
}
