<?php

namespace Tests\Unit\Policies;

use App\Models\NotificationPreference;
use App\Models\User;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de `NotificationPreferencePolicy`.
 *
 * Las preferencias son estrictamente personales: cada usuario solo
 * puede ver/editar/borrar las suyas. Estos tests blindan esa
 * invariante para que un cambio futuro no la rompa sin querer.
 */
class NotificationPreferencePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_puede_ver_su_propia_preferencia(): void
    {
        $user = User::factory()->create();
        $pref = NotificationPreferenceFactory::new()->forUser($user)->create();

        $this->assertTrue($user->can('view', $pref));
    }

    public function test_usuario_no_puede_ver_preferencia_ajena(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $pref = NotificationPreferenceFactory::new()->forUser($owner)->create();

        $this->assertFalse($other->can('view', $pref));
    }

    public function test_usuario_puede_editar_su_propia_preferencia(): void
    {
        $user = User::factory()->create();
        $pref = NotificationPreferenceFactory::new()->forUser($user)->create();

        $this->assertTrue($user->can('update', $pref));
    }

    public function test_usuario_no_puede_editar_preferencia_ajena(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $pref = NotificationPreferenceFactory::new()->forUser($owner)->create();

        $this->assertFalse($other->can('update', $pref));
    }

    public function test_usuario_puede_borrar_su_propia_preferencia(): void
    {
        $user = User::factory()->create();
        $pref = NotificationPreferenceFactory::new()->forUser($user)->create();

        $this->assertTrue($user->can('delete', $pref));
    }

    public function test_usuario_no_puede_borrar_preferencia_ajena(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $pref = NotificationPreferenceFactory::new()->forUser($owner)->create();

        $this->assertFalse($other->can('delete', $pref));
    }

    public function test_view_any_devuelve_true_para_usuario_autenticado(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('viewAny', NotificationPreference::class));
    }
}
