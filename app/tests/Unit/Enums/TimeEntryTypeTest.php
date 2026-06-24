<?php

namespace Tests\Unit\Enums;

use App\Enums\TimeEntryType;
use Tests\TestCase;

/**
 * Tests unitarios del enum `TimeEntryType`.
 *
 * Cubren: etiquetas legibles en castellano, mapeo de
 * colores semanticos, helpers de tipo y consistencia
 * del valor persistente.
 */
class TimeEntryTypeTest extends TestCase
{
    public function test_label_devuelve_etiqueta_en_castellano(): void
    {
        $this->assertSame('Manual', TimeEntryType::Manual->label());
        $this->assertSame('Cronometro', TimeEntryType::Timer->label());
    }

    public function test_color_devuelve_color_semantico(): void
    {
        $this->assertSame('gray', TimeEntryType::Manual->color());
        $this->assertSame('blue', TimeEntryType::Timer->color());
    }

    public function test_helpers_de_tipo(): void
    {
        $this->assertTrue(TimeEntryType::Manual->isManual());
        $this->assertFalse(TimeEntryType::Manual->isTimer());

        $this->assertTrue(TimeEntryType::Timer->isTimer());
        $this->assertFalse(TimeEntryType::Timer->isManual());
    }

    public function test_value_devuelve_el_string_persistente(): void
    {
        $this->assertSame('manual', TimeEntryType::Manual->value);
        $this->assertSame('timer', TimeEntryType::Timer->value);
    }
}
