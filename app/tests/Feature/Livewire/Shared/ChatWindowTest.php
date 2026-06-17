<?php

namespace Tests\Feature\Livewire\Shared;

use App\Livewire\Shared\ChatWindow;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests del componente Livewire `Shared\ChatWindow`.
 *
 * Verifican la interaccion desde el chat: envio de mensajes
 * via `sendMessage`, autorizacion correcta para clientes,
 * generacion de notificaciones y mark-as-read.
 *
 * Estos tests pillan el bug que decia "como cliente me da 403
 * al enviar un mensaje": la policy `create` se resolvia contra
 * `ProjectPolicy::create` (admin-only) en vez de
 * `ProjectMessagePolicy::create` (delegada en `view`).
 */
class ChatWindowTest extends TestCase
{
    use RefreshDatabase;

    private function clientAndProject(): array
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        return [$client, $project];
    }

    public function test_cliente_puede_enviar_mensaje_via_livewire(): void
    {
        [$client, $project] = $this->clientAndProject();

        Livewire::actingAs($client)
            ->test(ChatWindow::class, ['project' => $project])
            ->set('newMessage', 'Hola desde el portal')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'user_id' => $client->id,
            'content' => 'Hola desde el portal',
            'type' => 'text',
        ]);
    }

    public function test_admin_puede_enviar_mensaje_via_livewire(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($admin->id, ['role' => 'owner']);

        Livewire::actingAs($admin)
            ->test(ChatWindow::class, ['project' => $project])
            ->set('newMessage', 'Hola desde el admin')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'content' => 'Hola desde el admin',
        ]);
    }

    public function test_contenido_vacio_muestra_error_sin_persistir(): void
    {
        [$client, $project] = $this->clientAndProject();

        Livewire::actingAs($client)
            ->test(ChatWindow::class, ['project' => $project])
            ->set('newMessage', '   ')
            ->call('sendMessage')
            ->assertHasErrors(['newMessage']);

        $this->assertDatabaseCount('project_messages', 0);
    }

    public function test_contenido_se_trimea_antes_de_persistir(): void
    {
        [$client, $project] = $this->clientAndProject();

        Livewire::actingAs($client)
            ->test(ChatWindow::class, ['project' => $project])
            ->set('newMessage', "  hola  \n  ")
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_messages', [
            'content' => 'hola',
        ]);
    }

    public function test_load_more_incrementa_el_numero_de_mensajes_cargados(): void
    {
        [$client, $project] = $this->clientAndProject();

        // Creamos 60 mensajes de sistema. El componente arranca
        // con loadedCount=50; loadMore() lo sube a 100.
        ProjectMessage::factory()->system()->count(60)->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($client)
            ->test(ChatWindow::class, ['project' => $project]);

        // Inicialmente ve 50.
        $this->assertCount(50, $component->viewData('messages'));

        $component->call('loadMore');
        // Ahora ve los 60.
        $this->assertCount(60, $component->viewData('messages'));
    }

    /**
     * Al montar el componente se marcan como leidos los mensajes
     * existentes para el usuario actual.
     */
    public function test_marcar_como_leidos_al_montar_componente(): void
    {
        [$client, $project] = $this->clientAndProject();
        ProjectMessage::factory()->count(3)->create(['project_id' => $project->id]);

        Livewire::actingAs($client)
            ->test(ChatWindow::class, ['project' => $project]);

        $this->assertDatabaseCount('message_reads', 3);
    }

    /**
     * El emisor no genera un registro de lectura de si mismo al
     * enviar un mensaje; el doble check depende de que otros lo
     * lean.
     */
    public function test_enviar_mensaje_no_marca_lectura_del_emisor(): void
    {
        [$client, $project] = $this->clientAndProject();

        Livewire::actingAs($client)
            ->test(ChatWindow::class, ['project' => $project])
            ->set('newMessage', 'Hola')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('message_reads', 0);
    }

    /**
     * La propiedad computada indica que un mensaje propio ha sido
     * leido por otro usuario.
     */
    public function test_indicador_visto_activo_cuando_otro_usuario_lee(): void
    {
        [$client, $project] = $this->clientAndProject();
        $other = User::factory()->client()->create();
        $project->organization->members()->attach($other->id, ['role' => 'member']);

        $message = ProjectMessage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $client->id,
        ]);

        // Antes de que otro usuario lea, el mensaje no esta visto.
        $component = Livewire::actingAs($client)
            ->test(ChatWindow::class, ['project' => $project]);

        $this->assertFalse($component->viewData('readMessageIds')[$message->id] ?? false);

        // Simulamos que otro usuario abre el chat y lee el mensaje.
        Livewire::actingAs($other)
            ->test(ChatWindow::class, ['project' => $project]);

        // Al re-renderizar como emisor, el mapa refleja la lectura.
        $component->call('refresh');
        $this->assertTrue($component->viewData('readMessageIds')[$message->id] ?? false);
    }
}
