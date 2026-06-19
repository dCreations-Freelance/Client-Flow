<?php

namespace Tests\Unit\Services\Calendar;

use App\Enums\OrganizationUserRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Calendar\CalendarQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests unitarios del servicio `CalendarQueryService`.
 *
 * Cubren: consulta de eventos en rango, generacion de deadlines
 * virtuales, payload completo para el componente Livewire y
 * resolucion de asistentes disponibles.
 */
class CalendarQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalendarQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CalendarQueryService::class);
    }

    public function test_get_events_for_period_incluye_eventos_que_solapan_con_el_rango(): void
    {
        $project = Project::factory()->create();

        \App\Models\CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'starts_at' => '2026-07-10 10:00:00',
            'ends_at' => '2026-07-10 11:00:00',
        ]);

        \App\Models\CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'starts_at' => '2026-07-25 10:00:00',
            'ends_at' => '2026-07-25 11:00:00',
        ]);

        $events = $this->service->getEventsForPeriod(
            $project,
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-31')->endOfDay(),
        );

        $this->assertCount(2, $events);
    }

    public function test_get_events_for_period_excluye_eventos_de_otros_proyectos(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        \App\Models\CalendarEvent::factory()->create(['project_id' => $project1->id]);
        \App\Models\CalendarEvent::factory()->create(['project_id' => $project2->id]);

        $events = $this->service->getEventsForPeriod(
            $project1,
            Carbon::now()->subMonth(),
            Carbon::now()->addMonth(),
        );

        $this->assertCount(1, $events);
    }

    public function test_get_virtual_deadlines_sintetiza_entradas_desde_tareas_con_due_date(): void
    {
        $project = Project::factory()->create();
        $column = $project->columns()->first() ?? $project->columns()->create([
            'name' => 'Por hacer',
            'slug' => 'todo',
            'position' => 1,
        ]);

        Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Tarea con deadline',
            'due_date' => '2026-07-15',
            'created_by' => User::factory()->create()->id,
        ]);

        Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Tarea sin deadline',
            'due_date' => null,
            'created_by' => User::factory()->create()->id,
        ]);

        $deadlines = $this->service->getVirtualDeadlines(
            $project,
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-31')->endOfDay(),
        );

        $this->assertCount(1, $deadlines);
        $this->assertSame('Tarea con deadline', $deadlines->first()->title);
        $this->assertTrue($deadlines->first()->is_virtual);
        $this->assertTrue($deadlines->first()->is_all_day);
        $this->assertSame(\App\Enums\CalendarEventType::Deadline, $deadlines->first()->type);
    }

    public function test_get_calendar_data_incluye_dias_eventos_y_deadlines(): void
    {
        $project = Project::factory()->create();
        $column = $project->columns()->first() ?? $project->columns()->create([
            'name' => 'Por hacer',
            'slug' => 'todo',
            'position' => 1,
        ]);

        \App\Models\CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'starts_at' => '2026-07-15 10:00:00',
            'ends_at' => '2026-07-15 11:00:00',
        ]);

        Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Deadline',
            'due_date' => '2026-07-20',
            'created_by' => User::factory()->create()->id,
        ]);

        $data = $this->service->getCalendarData(
            $project,
            Carbon::parse('2026-07-15'),
            'month',
        );

        $this->assertSame('month', $data['view']);
        // Julio 2026 empieza en miercoles y termina en viernes, asi
        // que la grilla necesita 5 semanas (35 dias). Verificamos
        // que es multiplo de 7 sin asumir que siempre es 42.
        $this->assertGreaterThanOrEqual(28, count($data['days']));
        $this->assertSame(0, count($data['days']) % 7);
        $this->assertCount(1, $data['events']);
        $this->assertCount(1, $data['deadlines']);
        $this->assertArrayHasKey('2026-07-15', $data['events_by_day']);
        $this->assertArrayHasKey('2026-07-20', $data['deadlines_by_day']);
    }

    public function test_get_calendar_data_en_modo_semana_devuelve_7_dias(): void
    {
        $project = Project::factory()->create();

        $data = $this->service->getCalendarData(
            $project,
            Carbon::parse('2026-07-15'),
            'week',
        );

        $this->assertSame('week', $data['view']);
        $this->assertCount(7, $data['days']);
    }

    public function test_get_month_range_comienza_en_lunes_y_termina_en_domingo(): void
    {
        // Julio 2026 empieza en miercoles. El rango debe extenderse
        // al lunes previo y al domingo final del mes.
        $range = $this->service->getMonthRange(Carbon::parse('2026-07-15'));

        $this->assertSame(Carbon::MONDAY, $range['from']->dayOfWeek);
        $this->assertSame(Carbon::SUNDAY, $range['to']->dayOfWeek);
        // El rango cubre el mes actual + el overflow de los meses
        // colindantes. Verificamos que el `from` es del mes 6 o 7
        // y que el `to` es del mes 7 u 8.
        $this->assertContains($range['from']->month, [6, 7]);
        $this->assertContains($range['to']->month, [7, 8]);
    }

    public function test_build_month_days_genera_grilla_completa_multiplo_de_7(): void
    {
        $days = $this->service->buildMonthDays(Carbon::parse('2026-07-15'));

        // Julio 2026 encaja en 5 semanas (35 dias). Verificamos que
        // la grilla es multiplo de 7 sin asumir 42 fijo.
        $this->assertGreaterThanOrEqual(28, count($days));
        $this->assertSame(0, count($days) % 7);
    }

    public function test_get_week_range_comienza_en_lunes(): void
    {
        $range = $this->service->getWeekRange(Carbon::parse('2026-07-15'));

        $this->assertSame(Carbon::MONDAY, $range['from']->dayOfWeek);
        $this->assertSame(Carbon::SUNDAY, $range['to']->dayOfWeek);
    }

    public function test_group_by_day_agrupa_eventos_por_clave_de_dia(): void
    {
        $project = Project::factory()->create();
        \App\Models\CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'starts_at' => '2026-07-15 10:00:00',
        ]);
        \App\Models\CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'starts_at' => '2026-07-15 14:00:00',
        ]);
        \App\Models\CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'starts_at' => '2026-07-20 10:00:00',
        ]);

        $events = $this->service->getEventsForPeriod(
            $project,
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-31')->endOfDay(),
        );

        $grouped = $this->service->groupByDay($events);

        $this->assertCount(2, $grouped['2026-07-15']);
        $this->assertCount(1, $grouped['2026-07-20']);
    }

    public function test_available_attendees_devuelve_union_de_miembros_del_proyecto_y_org(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['organization_id' => $admin->organizations()->first()?->id ?? Project::factory()->create()->organization_id]);

        // Miembro directo del proyecto
        $projectMember = User::factory()->client()->create();
        $project->members()->attach($projectMember->id);

        // Miembro de la org pero no del proyecto
        $orgMember = User::factory()->client()->create();
        $project->organization->members()->attach($orgMember->id, [
            'role' => OrganizationUserRole::Member->value,
        ]);

        // Usuario ajeno: no debe aparecer
        User::factory()->client()->create();

        $attendees = $this->service->availableAttendees($project);

        $this->assertCount(2, $attendees);
        $this->assertTrue($attendees->contains('id', $projectMember->id));
        $this->assertTrue($attendees->contains('id', $orgMember->id));
    }

    public function test_available_attendees_puede_excluir_un_usuario(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $other = User::factory()->client()->create();
        $project->members()->attach($other->id);

        $attendees = $this->service->availableAttendees($project, $other->id);

        $this->assertCount(0, $attendees);
    }
}
