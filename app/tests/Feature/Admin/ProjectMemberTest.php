<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_anadir_un_miembro_de_la_organizacion(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();
        $member = User::factory()->create();
        $org->members()->attach($member->id, ['role' => 'member']);
        $project = Project::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($admin)
            ->post(route('admin.projects.members.store', $project), [
                'user_id' => $member->id,
            ])->assertRedirect();

        $this->assertTrue(
            $project->members()->where('users.id', $member->id)->exists()
        );
    }

    public function test_admin_no_puede_anadir_un_usuario_ajeno_a_la_organizacion(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $org->id]);
        $outsider = User::factory()->create(); // no es miembro de la org

        $this->actingAs($admin)
            ->post(route('admin.projects.members.store', $project), [
                'user_id' => $outsider->id,
            ])->assertSessionHasErrors('user_id');

        $this->assertFalse(
            $project->members()->where('users.id', $outsider->id)->exists()
        );
    }

    public function test_no_se_puede_anadir_al_mismo_usuario_dos_veces(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();
        $member = User::factory()->create();
        $org->members()->attach($member->id, ['role' => 'member']);
        $project = Project::factory()->create(['organization_id' => $org->id]);

        $project->members()->attach($member->id);

        $this->actingAs($admin)
            ->post(route('admin.projects.members.store', $project), [
                'user_id' => $member->id,
            ])->assertRedirect();

        $this->assertSame(1, $project->members()->where('users.id', $member->id)->count());
    }

    public function test_admin_puede_quitar_a_un_miembro_del_proyecto(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $member = User::factory()->create();
        $project->members()->attach($member->id);

        $this->actingAs($admin)
            ->delete(route('admin.projects.members.destroy', [$project, $member->id]))
            ->assertRedirect();

        $this->assertFalse(
            $project->members()->where('users.id', $member->id)->exists()
        );
    }

    public function test_cliente_no_puede_anadir_ni_quitar_miembros(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $member = User::factory()->create();
        $project->organization->members()->attach([$client->id, $member->id], ['role' => 'member']);

        $this->actingAs($client)
            ->post(route('admin.projects.members.store', $project), [
                'user_id' => $member->id,
            ])->assertRedirect(route('portal.dashboard'));

        $this->actingAs($client)
            ->delete(route('admin.projects.members.destroy', [$project, $member->id]))
            ->assertRedirect(route('portal.dashboard'));
    }
}
