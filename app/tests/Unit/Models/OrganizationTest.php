<?php

namespace Tests\Unit\Models;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_slug_unico_a_partir_del_nombre(): void
    {
        $org = Organization::factory()->create(['name' => 'Acme SL']);

        $this->assertSame('acme-sl', $org->slug);
    }

    public function test_anade_sufijo_numerico_si_el_slug_ya_existe(): void
    {
        Organization::factory()->create(['name' => 'Repetida']);

        $org = Organization::factory()->create(['name' => 'Repetida']);

        $this->assertSame('repetida-1', $org->slug);
    }

    public function test_relacion_owner_apunta_al_usuario_creador(): void
    {
        $owner = User::factory()->admin()->create();
        $org = Organization::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($org->owner->is($owner));
    }

    public function test_relacion_members_devuelve_usuarios_asociados(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->admin()->create();
        $member = User::factory()->client()->create();

        $org->members()->attach($owner->id, ['role' => 'owner']);
        $org->members()->attach($member->id, ['role' => 'member']);

        $this->assertCount(2, $org->members);
        $this->assertTrue($org->members->contains($owner));
        $this->assertTrue($org->members->contains($member));
    }

    public function test_scope_active_solo_devuelve_organizaciones_activas(): void
    {
        $active = Organization::factory()->create();
        $inactive = Organization::factory()->inactive()->create();

        $ids = Organization::active()->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    public function test_scope_for_user_solo_devuelve_orgs_donde_es_miembro(): void
    {
        $user = User::factory()->client()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($user->id, ['role' => 'member']);

        Organization::factory()->create(); // org ajena

        $ids = Organization::forUser($user)->pluck('id')->all();

        $this->assertSame([$org->id], $ids);
    }

    public function test_castea_status_a_enum(): void
    {
        $org = Organization::factory()->inactive()->create();

        $this->assertInstanceOf(OrganizationStatus::class, $org->status);
        $this->assertSame(OrganizationStatus::Inactive, $org->status);
    }

    public function test_pending_invitations_solo_devuelve_invitaciones_vigentes(): void
    {
        $org = Organization::factory()->create();

        $valid = \App\Models\OrganizationInvitation::factory()->create(['organization_id' => $org->id]);
        $expired = \App\Models\OrganizationInvitation::factory()->expired()->create(['organization_id' => $org->id]);
        $accepted = \App\Models\OrganizationInvitation::factory()->accepted()->create(['organization_id' => $org->id]);

        $pending = $org->pendingInvitations;

        $this->assertTrue($pending->contains($valid));
        $this->assertFalse($pending->contains($expired));
        $this->assertFalse($pending->contains($accepted));
    }

    public function test_owners_devuelve_solo_miembros_con_rol_owner(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->admin()->create();
        $member = User::factory()->client()->create();

        $org->members()->attach($owner->id, ['role' => OrganizationUserRole::Owner->value]);
        $org->members()->attach($member->id, ['role' => OrganizationUserRole::Member->value]);

        $this->assertCount(1, $org->owners);
        $this->assertTrue($org->owners->first()->is($owner));
    }
}
