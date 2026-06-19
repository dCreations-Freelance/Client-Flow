<?php

namespace Tests\Unit\Enums;

use App\Enums\CalendarEventType;
use Tests\TestCase;

/**
 * Tests unitarios del enum `CalendarEventType`.
 *
 * Cubren: etiquetas legibles, mapeo de colores, clases CSS
 * completas para badges y helpers de tipo.
 */
class CalendarEventTypeTest extends TestCase
{
    public function test_label_devuelve_etiqueta_en_castellano(): void
    {
        $this->assertSame('Reunion', CalendarEventType::Meeting->label());
        $this->assertSame('Hito', CalendarEventType::Milestone->label());
        $this->assertSame('Fecha limite', CalendarEventType::Deadline->label());
    }

    public function test_color_devuelve_color_semantico(): void
    {
        $this->assertSame('blue', CalendarEventType::Meeting->color());
        $this->assertSame('green', CalendarEventType::Milestone->color());
        $this->assertSame('orange', CalendarEventType::Deadline->color());
    }

    public function test_badge_classes_contiene_las_clases_css_del_color(): void
    {
        $this->assertStringContainsString('#2563EB', CalendarEventType::Meeting->badgeClasses());
        $this->assertStringContainsString('#16A34A', CalendarEventType::Milestone->badgeClasses());
        $this->assertStringContainsString('#D97706', CalendarEventType::Deadline->badgeClasses());
    }

    public function test_helpers_de_tipo(): void
    {
        $this->assertTrue(CalendarEventType::Meeting->isMeeting());
        $this->assertFalse(CalendarEventType::Meeting->isMilestone());
        $this->assertFalse(CalendarEventType::Meeting->isDeadline());

        $this->assertTrue(CalendarEventType::Milestone->isMilestone());
        $this->assertFalse(CalendarEventType::Milestone->isMeeting());

        $this->assertTrue(CalendarEventType::Deadline->isDeadline());
        $this->assertFalse(CalendarEventType::Deadline->isMeeting());
    }

    public function test_value_devuelve_el_string_persistente(): void
    {
        $this->assertSame('meeting', CalendarEventType::Meeting->value);
        $this->assertSame('milestone', CalendarEventType::Milestone->value);
        $this->assertSame('deadline', CalendarEventType::Deadline->value);
    }
}
