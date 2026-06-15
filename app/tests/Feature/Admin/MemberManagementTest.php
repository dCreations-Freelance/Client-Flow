<?php

namespace Tests\Feature\Admin;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_invitar_a_un_nuevo_miembro(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.members.store', $org), [
                'email' => 'nuevo@example.com',
                'role' => 'member',
            ])->assertRedirect();

        $this->assertDatabaseHas('organization_invitations', [
            'organization_id' => $org->id,
            'email' => 'nuevo@example.com',
        ]);
    }

    public function test_normaliza_email_a_minusculas(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.members.store', $org), [
                'email' => 'PERSONA@Example.COM',
                'role' => 'member',
            ])->assertRedirect();

        $this->assertDatabaseHas('organization_invitations', [
            'organization_id' => $org->id,
            'email' => 'persona@example.com',
        ]);
    }

    public function test_validacion_rechaza_email_invalido(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.members.store', $org), [
                'email' => 'no-es-email',
                'role' => 'member',
            ])->assertSessionHasErrors('email');
    }

    public function test_admin_puede_eliminar_un_miembro(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();
        $member = User::factory()->client()->create();
        $org->members()->attach($member->id, ['role' => 'member']);

        $this->actingAs($admin)
            ->delete(route('admin.organizations.members.destroy', [$org, $member->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_no_se_puede_eliminar_al_unico_owner(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($admin->id, ['role' => 'owner']);

        $this->actingAs($admin)
            ->delete(route('admin.organizations.members.destroy', [$org, $admin->id]))
            ->assertRedirect()
            ->assertSessionHasErrors('members');

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_se_puede_eliminar_a_un_owner_si_hay_otro(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $org = Organization::factory()->create();

        $org->members()->attach($admin->id, ['role' => 'owner']);
        $org->members()->attach($otherAdmin->id, ['role' => 'owner']);

        $this->actingAs($admin)
            ->delete(route('admin.organizations.members.destroy', [$org, $otherAdmin->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $otherAdmin->id,
        ]);
    }
}
