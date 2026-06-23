<?php

namespace Tests\Feature\Admin;

use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\User;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests feature de la pagina de preferencias del admin.
 *
 * Cubre: acceso del admin y bloqueo del cliente, render del
 * formulario con los defaults, persistencia del PUT, validacion
 * de eventos invalidos.
 */
class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_ver_su_pagina_de_preferencias(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.notifications.preferences'))
            ->assertOk()
            ->assertSee('Preferencias de notificaciones');
    }

    public function test_cliente_es_redirigido_al_portal_desde_la_pagina_admin(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('admin.notifications.preferences'))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_pagina_siembra_las_seis_filas_de_preferencias_para_el_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.notifications.preferences'))
            ->assertOk();

        $this->assertSame(6, NotificationPreference::query()->where('user_id', $admin->id)->count());
    }

    public function test_pagina_respeta_las_personalizaciones_existentes(): void
    {
        $admin = User::factory()->admin()->create();
        NotificationPreferenceFactory::new()
            ->forUser($admin)
            ->forEvent(NotificationEvent::TaskAssigned)
            ->disabled()
            ->create();

        $this->actingAs($admin)
            ->get(route('admin.notifications.preferences'))
            ->assertOk()
            ->assertSee('value="0"', false); // checkbox desmarcado en HTML
    }

    public function test_admin_puede_actualizar_sus_preferencias(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = [
            'preferences' => [
                ['event' => 'new_message', 'in_app' => 1, 'email' => 1],
                ['event' => 'task_assigned', 'in_app' => 0, 'email' => 1],
                ['event' => 'task_due_soon', 'in_app' => 1, 'email' => 0],
                ['event' => 'event_invitation', 'in_app' => 1, 'email' => 1],
                ['event' => 'organization_invitation', 'in_app' => 0, 'email' => 1],
                ['event' => 'daily_digest', 'in_app' => 0, 'email' => 0],
            ],
        ];

        $this->actingAs($admin)
            ->put(route('admin.notifications.preferences.update'), $payload)
            ->assertRedirect();

        $row = NotificationPreference::query()
            ->where('user_id', $admin->id)
            ->where('event', 'task_assigned')
            ->first();

        $this->assertFalse((bool) $row->in_app);
        $this->assertTrue((bool) $row->email);
    }

    public function test_validacion_rechaza_evento_invalido(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.notifications.preferences.update'), [
                'preferences' => [
                    ['event' => 'invent', 'in_app' => 1, 'email' => 1],
                ],
            ])
            ->assertSessionHasErrors('preferences.0.event');
    }

    public function test_validacion_requiere_el_array_de_preferencias(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.notifications.preferences.update'), [])
            ->assertSessionHasErrors('preferences');
    }
}
