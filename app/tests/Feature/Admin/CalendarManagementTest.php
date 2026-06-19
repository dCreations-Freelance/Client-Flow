<?php

namespace Tests\Feature\Admin;

use App\Enums\CalendarEventType;
use App\Enums\ProjectStatus;
use App\Livewire\Shared\CalendarView;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests feature del calendario de proyecto en el panel admin.
 *
 * Cubre: visualizacion del calendario, creacion, edicion y
 * eliminacion de eventos via Livewire, validacion, notificaciones
 * a asistentes, mensajes de sistema en el chat y aislamiento
 * respecto a clientes.
 */
class CalendarManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminAndProject(): array
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'is_visible_to_client' => true,
        ]);

        return [$admin, $project];
    }

    public function test_admin_puede_ver_calendario(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->get(route('admin.projects.calendar', $project))
            ->assertOk()
            ->assertSeeLivewire(CalendarView::class);
    }

    public function test_admin_puede_crear_evento_meeting_via_livewire(): void
    {
        Notification::fake();
        [$admin, $project] = $this->adminAndProject();
        $client = User::factory()->client()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openCreateForm', now()->format('Y-m-d'))
            ->set('eventForm.title', 'Reunion de kickoff')
            ->set('eventForm.type', CalendarEventType::Meeting->value)
            ->set('eventForm.starts_at', '2026-07-15 10:00')
            ->set('eventForm.ends_at', '2026-07-15 11:00')
            ->set('attendeeIds', [$client->id])
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('calendar_events', [
            'project_id' => $project->id,
            'title' => 'Reunion de kickoff',
            'type' => CalendarEventType::Meeting->value,
            'is_all_day' => false,
        ]);
    }

    public function test_admin_puede_crear_milestone_all_day_via_livewire(): void
    {
        Notification::fake();
        [$admin, $project] = $this->adminAndProject();

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openCreateForm', '2026-08-01')
            ->set('eventForm.title', 'Entrega de fase 1')
            ->set('eventForm.type', CalendarEventType::Milestone->value)
            ->set('eventForm.is_all_day', true)
            ->set('eventForm.starts_at', '2026-08-01')
            ->set('eventForm.ends_at', '2026-08-01')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $event = CalendarEvent::where('title', 'Entrega de fase 1')->first();
        $this->assertNotNull($event);
        $this->assertSame(CalendarEventType::Milestone, $event->type);
        $this->assertTrue($event->is_all_day);
    }

    public function test_all_day_normaliza_las_horas_a_00_y_23(): void
    {
        Notification::fake();
        [$admin, $project] = $this->adminAndProject();

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openCreateForm', '2026-08-15')
            ->set('eventForm.title', 'Hito')
            ->set('eventForm.type', CalendarEventType::Milestone->value)
            ->set('eventForm.is_all_day', true)
            ->set('eventForm.starts_at', '2026-08-15 10:30')
            ->set('eventForm.ends_at', '2026-08-15 14:00')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $event = CalendarEvent::where('title', 'Hito')->first();
        $this->assertSame('00:00:00', $event->starts_at->format('H:i:s'));
        $this->assertSame('23:59:59', $event->ends_at->format('H:i:s'));
    }

    public function test_admin_puede_editar_evento(): void
    {
        Notification::fake();
        [$admin, $project] = $this->adminAndProject();
        $event = CalendarEvent::factory()->forProject($project)->createdBy($admin)->create();

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openEditForm', $event->id)
            ->set('eventForm.title', 'Titulo editado')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $event->refresh();
        $this->assertSame('Titulo editado', $event->title);
    }

    public function test_admin_puede_eliminar_evento(): void
    {
        Notification::fake();
        [$admin, $project] = $this->adminAndProject();
        $event = CalendarEvent::factory()->forProject($project)->createdBy($admin)->create();

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('deleteEvent', $event->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
    }

    public function test_validacion_rechaza_titulo_vacio(): void
    {
        [$admin, $project] = $this->adminAndProject();

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openCreateForm', '2026-07-15')
            ->set('eventForm.title', '')
            ->set('eventForm.type', CalendarEventType::Meeting->value)
            ->set('eventForm.starts_at', '2026-07-15 10:00')
            ->call('saveEvent')
            ->assertHasErrors(['eventForm.title']);

        $this->assertDatabaseCount('calendar_events', 0);
    }

    public function test_validacion_rechaza_tipo_invalido(): void
    {
        [$admin, $project] = $this->adminAndProject();

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openCreateForm', '2026-07-15')
            ->set('eventForm.title', 'Algo')
            ->set('eventForm.type', 'deadline')
            ->set('eventForm.starts_at', '2026-07-15 10:00')
            ->call('saveEvent')
            ->assertHasErrors(['eventForm.type']);
    }

    public function test_validacion_rechaza_ends_at_anterior_a_starts_at(): void
    {
        [$admin, $project] = $this->adminAndProject();

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openCreateForm', '2026-07-15')
            ->set('eventForm.title', 'Algo')
            ->set('eventForm.type', CalendarEventType::Meeting->value)
            ->set('eventForm.starts_at', '2026-07-15 11:00')
            ->set('eventForm.ends_at', '2026-07-15 10:00')
            ->call('saveEvent')
            ->assertHasErrors(['eventForm.ends_at']);
    }

    public function test_crear_evento_genera_mensaje_de_sistema_en_el_chat(): void
    {
        Notification::fake();
        [$admin, $project] = $this->adminAndProject();

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openCreateForm', '2026-07-15')
            ->set('eventForm.title', 'Reunion importante')
            ->set('eventForm.type', CalendarEventType::Meeting->value)
            ->set('eventForm.starts_at', '2026-07-15 10:00')
            ->set('eventForm.ends_at', '2026-07-15 11:00')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'type' => 'system',
        ]);
    }

    public function test_notificacion_a_asistentes_al_crear_evento(): void
    {
        Notification::fake();
        [$admin, $project] = $this->adminAndProject();
        $client = User::factory()->client()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openCreateForm', '2026-07-15')
            ->set('eventForm.title', 'Reunion')
            ->set('eventForm.type', CalendarEventType::Meeting->value)
            ->set('eventForm.starts_at', '2026-07-15 10:00')
            ->set('attendeeIds', [$client->id])
            ->call('saveEvent')
            ->assertHasNoErrors();

        Notification::assertSentTo(
            $client,
            \App\Notifications\CalendarEventInvitation::class,
        );
    }

    public function test_no_notifica_al_emisor_del_evento(): void
    {
        Notification::fake();
        [$admin, $project] = $this->adminAndProject();

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openCreateForm', '2026-07-15')
            ->set('eventForm.title', 'Reunion')
            ->set('eventForm.type', CalendarEventType::Meeting->value)
            ->set('eventForm.starts_at', '2026-07-15 10:00')
            ->set('attendeeIds', [$admin->id])
            ->call('saveEvent')
            ->assertHasNoErrors();

        Notification::assertNotSentTo(
            $admin,
            \App\Notifications\CalendarEventInvitation::class,
        );
    }

    public function test_resolve_attendees_filtra_usuarios_ajenos_al_proyecto(): void
    {
        Notification::fake();
        [$admin, $project] = $this->adminAndProject();
        $outsider = User::factory()->client()->create();

        Livewire::actingAs($admin)
            ->test(CalendarView::class, ['project' => $project])
            ->call('openCreateForm', '2026-07-15')
            ->set('eventForm.title', 'Reunion')
            ->set('eventForm.type', CalendarEventType::Meeting->value)
            ->set('eventForm.starts_at', '2026-07-15 10:00')
            ->set('attendeeIds', [$outsider->id])
            ->call('saveEvent')
            ->assertHasNoErrors();

        $event = CalendarEvent::first();
        $this->assertCount(0, $event->attendees);
    }

    public function test_cliente_no_puede_acceder_a_calendario_admin(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('admin.projects.calendar', $project))
            ->assertRedirect(route('portal.dashboard'));
    }
}
