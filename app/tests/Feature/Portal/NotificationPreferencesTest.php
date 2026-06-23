<?php

namespace Tests\Feature\Portal;

use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\User;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests feature de la pagina de preferencias del cliente.
 *
 * Misma cobertura que el test del admin pero con el gemelo del
 * portal. Se valida que el cliente puede editar SUS preferencias
 * y que un admin no puede entrar a la pagina del portal (el
 * middleware `client` lo redirige).
 */
class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_puede_ver_su_pagina_de_preferencias(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('portal.notifications.preferences'))
            ->assertOk()
            ->assertSee('Preferencias de notificaciones');
    }

    public function test_admin_es_redirigido_al_panel_desde_la_pagina_portal(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('portal.notifications.preferences'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_cliente_puede_actualizar_sus_preferencias(): void
    {
        $client = User::factory()->client()->create();

        $payload = [
            'preferences' => [
                ['event' => 'new_message', 'in_app' => 0, 'email' => 0],
            ],
        ];

        $this->actingAs($client)
            ->put(route('portal.notifications.preferences.update'), $payload)
            ->assertRedirect();

        $row = NotificationPreference::query()
            ->where('user_id', $client->id)
            ->where('event', 'new_message')
            ->first();

        $this->assertFalse((bool) $row->in_app);
        $this->assertFalse((bool) $row->email);
    }

    public function test_pagina_siembra_las_seis_filas_de_preferencias_para_el_cliente(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('portal.notifications.preferences'))
            ->assertOk();

        $this->assertSame(6, NotificationPreference::query()->where('user_id', $client->id)->count());
    }

    public function test_validacion_requiere_evento_en_cada_entrada(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->put(route('portal.notifications.preferences.update'), [
                'preferences' => [
                    ['in_app' => 1, 'email' => 1],
                ],
            ])
            ->assertSessionHasErrors('preferences.0.event');
    }
}
