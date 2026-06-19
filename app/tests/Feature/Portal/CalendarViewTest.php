<?php

namespace Tests\Feature\Portal;

use App\Enums\CalendarEventType;
use App\Livewire\Shared\CalendarView;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests feature del calendario de proyecto en el portal cliente.
 *
 * Cubre: visualizacion, modo read-only (sin botones de
 * creacion/edicion), aislamiento respecto a otros proyectos y
 * visualizacion de deadlines virtuales derivados de tareas.
 */
class CalendarViewTest extends TestCase
{
    use RefreshDatabase;

    private function clientAndProject(): array
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create(['is_visible_to_client' => true]);
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        return [$client, $project];
    }

    public function test_cliente_puede_ver_calendario(): void
    {
        [$client, $project] = $this->clientAndProject();

        $this->actingAs($client)
            ->get(route('portal.projects.calendar', $project))
            ->assertOk()
            ->assertSeeLivewire(CalendarView::class);
    }

    public function test_componente_se_monta_en_modo_read_only(): void
    {
        [$client, $project] = $this->clientAndProject();

        \Livewire\Livewire::actingAs($client)
            ->test(CalendarView::class, ['project' => $project, 'readOnly' => true])
            ->assertSet('readOnly', true);
    }

    public function test_cliente_puede_ver_evento_del_proyecto(): void
    {
        [$client, $project] = $this->clientAndProject();
        // Fijamos el evento a un dia concreto del mes actual para
        // evitar problemas con la paginacion del calendario cuando
        // la fecha del factory cae en otro mes.
        CalendarEvent::factory()->forProject($project)->create([
            'title' => 'Reunion publica',
            'starts_at' => now()->addDays(3)->setTime(10, 0),
            'ends_at' => now()->addDays(3)->setTime(11, 0),
        ]);

        \Livewire\Livewire::actingAs($client)
            ->test(CalendarView::class, ['project' => $project, 'readOnly' => true])
            ->assertSee('Reunion publica');
    }

    public function test_cliente_no_puede_crear_evento_via_livewire(): void
    {
        [$client, $project] = $this->clientAndProject();

        \Livewire\Livewire::actingAs($client)
            ->test(CalendarView::class, ['project' => $project, 'readOnly' => true])
            ->call('openCreateForm', '2026-07-15')
            ->assertForbidden();
    }

    public function test_cliente_no_puede_editar_evento_via_livewire(): void
    {
        [$client, $project] = $this->clientAndProject();
        $event = CalendarEvent::factory()->forProject($project)->create();

        \Livewire\Livewire::actingAs($client)
            ->test(CalendarView::class, ['project' => $project, 'readOnly' => true])
            ->call('openEditForm', $event->id)
            ->assertForbidden();
    }

    public function test_cliente_no_puede_eliminar_evento_via_livewire(): void
    {
        [$client, $project] = $this->clientAndProject();
        $event = CalendarEvent::factory()->forProject($project)->create();

        \Livewire\Livewire::actingAs($client)
            ->test(CalendarView::class, ['project' => $project, 'readOnly' => true])
            ->call('deleteEvent', $event->id)
            ->assertForbidden();
    }

    public function test_calendario_muestra_deadlines_virtuales_de_tareas(): void
    {
        [$client, $project] = $this->clientAndProject();
        $column = $project->columns()->first() ?? $project->columns()->create([
            'name' => 'Por hacer',
            'slug' => 'todo',
            'position' => 1,
        ]);

        Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Tarea con deadline visible',
            'due_date' => now()->addDays(3),
            'created_by' => User::factory()->create()->id,
        ]);

        \Livewire\Livewire::actingAs($client)
            ->test(CalendarView::class, ['project' => $project, 'readOnly' => true])
            ->assertSee('Tarea con deadline visible');
    }

    public function test_cliente_no_puede_acceder_a_calendario_de_otro_proyecto(): void
    {
        $client = User::factory()->client()->create();
        $project1 = Project::factory()->create(['is_visible_to_client' => true]);
        $project1->organization->members()->attach($client->id, ['role' => 'member']);

        $otherProject = Project::factory()->create();
        $otherProject->organization->members()->attach(User::factory()->client()->create(), ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('portal.projects.calendar', $otherProject))
            ->assertForbidden();
    }

    public function test_cliente_no_puede_acceder_a_calendario_de_proyecto_archivado(): void
    {
        [$client, $project] = $this->clientAndProject();
        $project->update(['archived_at' => now()]);

        $this->actingAs($client)
            ->get(route('portal.projects.calendar', $project))
            ->assertForbidden();
    }
}
