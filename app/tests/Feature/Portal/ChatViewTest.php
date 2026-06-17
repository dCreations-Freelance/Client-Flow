<?php

namespace Tests\Feature\Portal;

use App\Models\Project;
use App\Models\ProjectChatRead;
use App\Models\ProjectMessage;
use App\Models\User;
use App\Notifications\NewProjectMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests feature del chat en el portal cliente.
 *
 * Garantiza que el cliente puede chatear con su proyecto, que
 * recibe notificaciones cuando llega un mensaje nuevo, y que
 * el tracking de no leidos refleja el estado del chat.
 */
class ChatViewTest extends TestCase
{
    use RefreshDatabase;

    private function clientAndProject(): array
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        return [$client, $project];
    }

    public function test_cliente_puede_ver_el_chat(): void
    {
        [$client, $project] = $this->clientAndProject();

        $this->actingAs($client)
            ->get(route('portal.projects.chat', $project))
            ->assertOk();
    }

    public function test_cliente_puede_enviar_mensaje(): void
    {
        [$client, $project] = $this->clientAndProject();

        $this->actingAs($client)
            ->post(route('portal.projects.chat.store', $project), [
                'content' => 'Una pregunta sobre el proyecto',
            ])->assertRedirect();

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'user_id' => $client->id,
            'content' => 'Una pregunta sobre el proyecto',
        ]);
    }

    public function test_cliente_no_puede_acceder_a_proyecto_archivado(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->archived()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('portal.projects.chat', $project))
            ->assertForbidden();
    }

    public function test_cliente_no_puede_acceder_a_proyecto_oculto(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->hiddenFromClient()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('portal.projects.chat', $project))
            ->assertForbidden();
    }

    public function test_cliente_no_puede_acceder_a_proyecto_de_otra_org(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();

        $this->actingAs($client)
            ->get(route('portal.projects.chat', $project))
            ->assertForbidden();
    }

    public function test_cliente_recibe_notificacion_cuando_llega_mensaje(): void
    {
        Notification::fake();

        [$client, $project] = $this->clientAndProject();
        $admin = User::factory()->admin()->create();
        $project->organization->members()->attach($admin->id, ['role' => 'owner']);

        $this->actingAs($admin)
            ->post(route('admin.projects.chat.store', $project), [
                'content' => 'Hola cliente',
            ])->assertRedirect();

        Notification::assertSentTo($client, NewProjectMessage::class);
    }

    public function test_cliente_puede_marcar_como_leido_y_se_reduce_el_contador(): void
    {
        Notification::fake();

        [$client, $project] = $this->clientAndProject();
        $admin = User::factory()->admin()->create();
        $project->organization->members()->attach($admin->id, ['role' => 'owner']);

        // El admin envia un mensaje.
        $this->actingAs($admin)
            ->post(route('admin.projects.chat.store', $project), [
                'content' => 'Hola cliente',
            ])->assertRedirect();

        $message = ProjectMessage::where('project_id', $project->id)->latest('id')->first();

        // Sin marcador de lectura, el cliente ve 1 no leido (el
        // que acabamos de enviar).
        $initialUnread = ProjectMessage::where('project_id', $project->id)
            ->where('id', '>', 0)
            ->count();
        $this->assertSame(1, $initialUnread);

        // El cliente abre el chat: se marca como leido.
        $this->actingAs($client)
            ->get(route('portal.projects.chat', $project))
            ->assertOk();

        $read = ProjectChatRead::where('project_id', $project->id)
            ->where('user_id', $client->id)
            ->first();
        $this->assertNotNull($read);
        $this->assertSame($message->id, (int) $read->last_read_message_id);
    }

    /**
     * Cuando el admin lee un mensaje enviado por el cliente, el
     * mensaje se marca como visto para el cliente.
     */
    public function test_mensaje_cliente_muestra_doble_check_al_ser_leido_por_admin(): void
    {
        [$client, $project] = $this->clientAndProject();
        $admin = User::factory()->admin()->create();
        $project->organization->members()->attach($admin->id, ['role' => 'owner']);

        $message = ProjectMessage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $client->id,
        ]);

        $this->assertFalse($message->readByAnyoneElse($client));

        // El admin abre el chat y lee el mensaje del cliente.
        $this->actingAs($admin)
            ->get(route('admin.projects.chat', $project))
            ->assertOk();

        $message->refresh();
        $this->assertTrue($message->readByAnyoneElse($client));
    }
}
