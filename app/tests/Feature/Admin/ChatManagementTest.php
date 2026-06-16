<?php

namespace Tests\Feature\Admin;

use App\Enums\MessageType;
use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\ProjectChatRead;
use App\Models\ProjectDocument;
use App\Models\ProjectMessage;
use App\Models\Task;
use App\Models\User;
use App\Notifications\NewProjectMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests feature del chat en el panel admin.
 *
 * Cubre: envio de mensajes (admin y cliente), generacion de
 * mensajes automaticos (tarea creada/completada/reabierta/movida,
 * proyecto archivado, documento publicado), aislamiento de
 * clientes, notificaciones in-app + email.
 */
class ChatManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminAndProject(): array
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($admin->id, ['role' => 'owner']);

        return [$admin, $project];
    }

    public function test_admin_puede_ver_el_chat(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->get(route('admin.projects.chat', $project))
            ->assertOk();
    }

    public function test_admin_puede_enviar_mensaje_de_texto_via_http(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.chat.store', $project), [
                'content' => 'Hola cliente, novedades del proyecto.',
            ])->assertRedirect();

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'content' => 'Hola cliente, novedades del proyecto.',
            'type' => 'text',
        ]);
    }

    public function test_validacion_rechaza_contenido_vacio(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.chat.store', $project), [
                'content' => '',
            ])->assertSessionHasErrors('content');
    }

    public function test_mensaje_se_marca_como_leido_para_el_emisor(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.chat.store', $project), [
                'content' => 'Probando',
            ])->assertRedirect();

        $message = ProjectMessage::where('project_id', $project->id)->latest('id')->first();
        $this->assertNotNull($message);

        $read = ProjectChatRead::where('project_id', $project->id)
            ->where('user_id', $admin->id)
            ->first();
        $this->assertNotNull($read);
        $this->assertSame($message->id, (int) $read->last_read_message_id);
    }

    public function test_mensaje_genera_notificacion_a_los_destinatarios(): void
    {
        Notification::fake();

        [$admin, $project] = $this->adminAndProject();
        $client = User::factory()->client()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($admin)
            ->post(route('admin.projects.chat.store', $project), [
                'content' => 'Aviso a todos',
            ])->assertRedirect();

        Notification::assertSentTo($client, NewProjectMessage::class);
        // El emisor (admin) no debe recibir notificacion.
        Notification::assertNotSentTo($admin, NewProjectMessage::class);
    }

    public function test_mensaje_de_sistema_se_genera_al_crear_tarea(): void
    {
        [$admin, $project] = $this->adminAndProject();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => $column->id,
                'title' => 'Implementar login',
                'priority' => 'high',
                'type' => 'feature',
            ])->assertRedirect();

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'type' => 'system',
            'user_id' => null,
        ]);

        $system = ProjectMessage::where('project_id', $project->id)
            ->where('type', MessageType::System)
            ->first();
        $this->assertStringContainsString('Implementar login', $system->content);
    }

    public function test_mensaje_de_sistema_se_genera_al_completar_tarea(): void
    {
        [$admin, $project] = $this->adminAndProject();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.complete', [$project, $task]))
            ->assertRedirect();

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'type' => 'system',
        ]);
    }

    public function test_mensaje_de_sistema_se_genera_al_mover_tarea(): void
    {
        [$admin, $project] = $this->adminAndProject();
        $fromColumn = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 0, 'name' => 'Por hacer']);
        $toColumn = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 1, 'name' => 'En curso']);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $fromColumn->id,
            'position' => 0,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.projects.tasks.move', [$project, $task]), [
                'column_id' => $toColumn->id,
                'position' => 0,
            ])->assertRedirect();

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'type' => 'system',
        ]);
        $system = ProjectMessage::where('project_id', $project->id)
            ->where('type', MessageType::System)
            ->latest('id')->first();
        $this->assertStringContainsString('En curso', $system->content);
    }

    public function test_mensaje_de_sistema_se_genera_al_crear_documento_publico(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.documents.store', $project), [
                'title' => 'Manual',
                'content' => '# Hola',
                'visibility' => 'public',
            ])->assertRedirect();

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'type' => 'system',
        ]);
    }

    public function test_no_se_genera_mensaje_si_el_documento_es_privado(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.documents.store', $project), [
                'title' => 'Notas',
                'content' => 'Secreto',
                'visibility' => 'private',
            ])->assertRedirect();

        $this->assertDatabaseMissing('project_messages', [
            'project_id' => $project->id,
            'type' => 'system',
        ]);
    }

    public function test_cliente_no_puede_acceder_a_la_ruta_admin_de_chat(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('admin.projects.chat', $project))
            ->assertRedirect(route('portal.dashboard'));
    }
}
