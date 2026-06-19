<?php

namespace Tests\Unit\Models;

use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests unitarios del modelo `CalendarEvent`.
 *
 * Cubren: cast del enum, scopes (`forProject`, `betweenDates`,
 * `byType`, `upcoming`, `ordered`), accessors y helpers de estado
 * (`isAllDay`, `isOnDate`, `isPast`, `isUpcoming`,
 * `occursInRange`).
 */
class CalendarEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_castea_type_al_enum(): void
    {
        $event = CalendarEvent::factory()->create(['type' => CalendarEventType::Milestone]);

        $this->assertInstanceOf(CalendarEventType::class, $event->type);
        $this->assertSame(CalendarEventType::Milestone, $event->type);
    }

    public function test_castea_starts_at_y_ends_at_a_carbon(): void
    {
        $event = CalendarEvent::factory()->create([
            'starts_at' => '2026-07-15 10:00:00',
            'ends_at' => '2026-07-15 11:30:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $event->starts_at);
        $this->assertInstanceOf(Carbon::class, $event->ends_at);
    }

    public function test_castea_is_all_day_a_booleano(): void
    {
        $event = CalendarEvent::factory()->allDay()->create();

        $this->assertTrue($event->is_all_day);
        $this->assertTrue($event->isAllDay());
    }

    public function test_scope_for_project_filtra_por_proyecto(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        CalendarEvent::factory()->count(2)->create(['project_id' => $project1->id]);
        CalendarEvent::factory()->count(3)->create(['project_id' => $project2->id]);

        $this->assertSame(2, CalendarEvent::forProject($project1->id)->count());
        $this->assertSame(3, CalendarEvent::forProject($project2->id)->count());
    }

    public function test_scope_between_dates_incluye_eventos_que_solapan_con_el_rango(): void
    {
        $project = Project::factory()->create();

        // Evento dentro del rango
        CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'starts_at' => '2026-07-10 10:00:00',
            'ends_at' => '2026-07-10 11:00:00',
        ]);

        // Evento que empieza antes pero termina dentro del rango
        CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'starts_at' => '2026-07-01 09:00:00',
            'ends_at' => '2026-07-05 18:00:00',
        ]);

        // Evento que empieza dentro pero termina despues del rango
        CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'starts_at' => '2026-07-25 09:00:00',
            'ends_at' => '2026-07-30 18:00:00',
        ]);

        // Evento fuera del rango
        CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'starts_at' => '2026-08-15 10:00:00',
            'ends_at' => '2026-08-15 11:00:00',
        ]);

        $from = Carbon::parse('2026-07-05 00:00:00');
        $to = Carbon::parse('2026-07-25 23:59:59');

        $count = CalendarEvent::forProject($project->id)
            ->betweenDates($from, $to)
            ->count();

        $this->assertSame(3, $count);
    }

    public function test_scope_by_type_filtra_por_tipo(): void
    {
        $project = Project::factory()->create();

        CalendarEvent::factory()->create(['project_id' => $project->id, 'type' => CalendarEventType::Meeting]);
        CalendarEvent::factory()->milestone()->create(['project_id' => $project->id]);
        CalendarEvent::factory()->milestone()->create(['project_id' => $project->id]);

        $this->assertSame(1, CalendarEvent::forProject($project->id)->byType(CalendarEventType::Meeting->value)->count());
        $this->assertSame(2, CalendarEvent::forProject($project->id)->byType(CalendarEventType::Milestone->value)->count());
    }

    public function test_scope_upcoming_devuelve_solo_eventos_futuros(): void
    {
        $project = Project::factory()->create();

        CalendarEvent::factory()->inPast()->create(['project_id' => $project->id]);
        CalendarEvent::factory()->inFuture()->create(['project_id' => $project->id]);
        CalendarEvent::factory()->inFuture()->create(['project_id' => $project->id]);

        $upcoming = CalendarEvent::forProject($project->id)->upcoming(10)->get();

        $this->assertCount(2, $upcoming);
        $this->assertTrue($upcoming->every(fn ($e) => $e->starts_at->isFuture()));
    }

    public function test_scope_ordered_ordena_por_inicio_ascendente(): void
    {
        $project = Project::factory()->create();
        $later = CalendarEvent::factory()->create(['project_id' => $project->id, 'starts_at' => '2026-08-15 10:00:00']);
        $earlier = CalendarEvent::factory()->create(['project_id' => $project->id, 'starts_at' => '2026-07-15 10:00:00']);
        $middle = CalendarEvent::factory()->create(['project_id' => $project->id, 'starts_at' => '2026-08-01 10:00:00']);

        $ordered = CalendarEvent::forProject($project->id)->ordered()->pluck('id')->all();

        $this->assertSame($earlier->id, $ordered[0]);
        $this->assertSame($middle->id, $ordered[1]);
        $this->assertSame($later->id, $ordered[2]);
    }

    public function test_helper_is_on_date_compara_solo_el_dia(): void
    {
        $event = CalendarEvent::factory()->create([
            'starts_at' => '2026-07-15 10:30:00',
        ]);

        $this->assertTrue($event->isOnDate(Carbon::parse('2026-07-15 23:00:00')));
        $this->assertFalse($event->isOnDate(Carbon::parse('2026-07-16 00:00:00')));
    }

    public function test_helper_is_past_devuelve_true_si_y_termino(): void
    {
        $past = CalendarEvent::factory()->inPast()->create();
        $future = CalendarEvent::factory()->inFuture()->create();

        $this->assertTrue($past->isPast());
        $this->assertFalse($future->isPast());
    }

    public function test_helper_is_upcoming_devuelve_true_si_no_ha_empezado(): void
    {
        $past = CalendarEvent::factory()->inPast()->create();
        $future = CalendarEvent::factory()->inFuture()->create();

        $this->assertFalse($past->isUpcoming());
        $this->assertTrue($future->isUpcoming());
    }

    public function test_helper_occurs_in_range_detecta_eventos_multi_dia(): void
    {
        $event = CalendarEvent::factory()->create([
            'starts_at' => '2026-07-15 10:00:00',
            'ends_at' => '2026-07-20 10:00:00',
        ]);

        // El rango cae dentro del evento
        $this->assertTrue($event->occursInRange(
            Carbon::parse('2026-07-16 00:00:00'),
            Carbon::parse('2026-07-18 23:59:59'),
        ));

        // El evento solapa con el rango por la izquierda
        $this->assertTrue($event->occursInRange(
            Carbon::parse('2026-07-10 00:00:00'),
            Carbon::parse('2026-07-16 00:00:00'),
        ));

        // El evento solapa con el rango por la derecha
        $this->assertTrue($event->occursInRange(
            Carbon::parse('2026-07-19 00:00:00'),
            Carbon::parse('2026-07-25 00:00:00'),
        ));

        // El evento esta fuera del rango
        $this->assertFalse($event->occursInRange(
            Carbon::parse('2026-08-01 00:00:00'),
            Carbon::parse('2026-08-10 00:00:00'),
        ));
    }

    public function test_end_for_query_devuelve_ends_at_o_30min_por_defecto(): void
    {
        $withEnd = CalendarEvent::factory()->create([
            'starts_at' => '2026-07-15 10:00:00',
            'ends_at' => '2026-07-15 12:00:00',
        ]);
        $this->assertSame('2026-07-15 12:00:00', $withEnd->end_for_query->format('Y-m-d H:i:s'));

        $withoutEnd = CalendarEvent::factory()->create([
            'starts_at' => '2026-07-15 10:00:00',
            'ends_at' => null,
        ]);
        $this->assertSame('2026-07-15 10:30:00', $withoutEnd->end_for_query->format('Y-m-d H:i:s'));
    }

    public function test_duration_minutes_devuelve_1440_para_all_day(): void
    {
        $event = CalendarEvent::factory()->allDay()->create();

        $this->assertSame(24 * 60, $event->durationMinutes());
    }

    public function test_duration_minutes_calcula_diferencia_correcta(): void
    {
        $event = CalendarEvent::factory()->create([
            'starts_at' => '2026-07-15 10:00:00',
            'ends_at' => '2026-07-15 11:30:00',
        ]);

        $this->assertSame(90, $event->durationMinutes());
    }

    public function test_relaciones_project_creator_y_attendees(): void
    {
        $project = Project::factory()->create();
        $creator = User::factory()->create();
        $attendee1 = User::factory()->create();
        $attendee2 = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
        ]);
        $event->attendees()->attach([$attendee1->id, $attendee2->id]);

        $this->assertSame($project->id, $event->project->id);
        $this->assertSame($creator->id, $event->creator->id);
        $this->assertCount(2, $event->attendees);
    }

    public function test_is_meeting_e_is_milestone_delegan_en_el_enum(): void
    {
        $meeting = CalendarEvent::factory()->create(['type' => CalendarEventType::Meeting]);
        $milestone = CalendarEvent::factory()->milestone()->create();

        $this->assertTrue($meeting->isMeeting());
        $this->assertFalse($meeting->isMilestone());
        $this->assertTrue($milestone->isMilestone());
        $this->assertFalse($milestone->isMeeting());
    }
}
