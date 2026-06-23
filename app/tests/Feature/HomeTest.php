<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_muestra_la_landing_a_visitantes_anonimos(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('ClientFlow')
            ->assertSee('Tus clientes entienden su proyecto en');
    }
}
