<?php

namespace Tests\Feature\Admin;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_listar_organizaciones(): void
    {
        $admin = User::factory()->admin()->create();
        Organization::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('admin.organizations.index'))
            ->assertOk()
            ->assertSee('Organizaciones');
    }

    public function test_cliente_no_puede_listar_organizaciones(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('admin.organizations.index'))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_admin_puede_crear_una_organizacion(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.organizations.store'), [
            'name' => 'Mi Cliente',
            'description' => 'Una descripcion',
        ]);

        $org = Organization::where('name', 'Mi Cliente')->first();

        $this->assertNotNull($org);
        $this->assertSame('mi-cliente', $org->slug);
        $response->assertRedirect(route('admin.organizations.show', $org));

        // El admin actual queda como owner.
        $this->assertTrue(
            $org->members()
                ->where('users.id', $admin->id)
                ->wherePivot('role', OrganizationUserRole::Owner->value)
                ->exists()
        );
    }

    public function test_cliente_no_puede_crear_organizaciones(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->post(route('admin.organizations.store'), [
                'name' => 'Hack',
            ])->assertRedirect(route('portal.dashboard'));
    }

    public function test_admin_puede_editar_una_organizacion(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create(['name' => 'Antigua']);

        $this->actingAs($admin)
            ->put(route('admin.organizations.update', $org), [
                'name' => 'Nueva',
                'description' => 'Actualizada',
                'status' => 'inactive',
            ])->assertRedirect(route('admin.organizations.show', $org));

        $org->refresh();
        $this->assertSame('Nueva', $org->name);
        $this->assertSame('inactive', $org->status->value);
    }

    public function test_validacion_falla_con_nombre_muy_corto(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.store'), [
                'name' => 'a',
            ])->assertSessionHasErrors('name');
    }

    public function test_validacion_falla_con_status_invalido(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.organizations.update', $org), [
                'name' => 'X',
                'status' => 'no-existe',
            ])->assertSessionHasErrors('status');
    }

    public function test_admin_puede_ver_detalle_de_una_organizacion(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.organizations.show', $org))
            ->assertOk()
            ->assertSee($org->name);
    }

    public function test_cliente_no_puede_ver_organizaciones_a_traves_del_panel_admin(): void
    {
        $client = User::factory()->client()->create();
        $own = Organization::factory()->create();
        $own->members()->attach($client->id, ['role' => 'member']);

        // El cliente es expulsado al portal cuando intenta entrar al panel
        // admin, sea la organizacion suya o no. La vista de organizaciones
        // para el cliente vivira en una ruta portal, fuera del scope de
        // esta fase.
        $this->actingAs($client)
            ->get(route('admin.organizations.show', $own))
            ->assertRedirect(route('portal.dashboard'));
    }
}
