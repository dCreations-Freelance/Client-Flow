<?php

namespace Tests\Unit\Policies;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la policy `ActivityLogPolicy`.
 *
 * Verifica la regla basica: el usuario puede ver una entrada
 * del feed si y solo si puede ver el proyecto asociado (o si
 * es admin y la entrada no tiene proyecto). La policy no
 * filtra por visibilidad de eventos: eso lo hace el scope
 * `public()` del modelo.
 */
class ActivityLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_ver_cualquier_entrada(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $entry = ActivityLog::factory()->forProject($project)->create();

        $this->assertTrue($admin->can('view', $entry));
    }

    public function test_cliente_puede_ver_entrada_de_su_proyecto(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $entry = ActivityLog::factory()->forProject($project)->create();

        $this->assertTrue($client->can('view', $entry));
    }

    public function test_cliente_no_puede_ver_entrada_de_otra_organizacion(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $entry = ActivityLog::factory()->forProject($project)->create();

        $this->assertFalse($client->can('view', $entry));
    }

    public function test_cliente_no_puede_ver_entrada_de_proyecto_archivado(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create(['archived_at' => now()]);
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $entry = ActivityLog::factory()->forProject($project)->create();

        $this->assertFalse($client->can('view', $entry));
    }

    public function test_admin_puede_ver_entrada_sin_proyecto(): void
    {
        $admin = User::factory()->admin()->create();
        $entry = ActivityLog::factory()->create([
            'project_id' => null,
            'organization_id' => null,
        ]);

        $this->assertTrue($admin->can('view', $entry));
    }

    public function test_cliente_no_puede_ver_entrada_sin_proyecto(): void
    {
        $client = User::factory()->client()->create();
        $entry = ActivityLog::factory()->create([
            'project_id' => null,
            'organization_id' => null,
        ]);

        $this->assertFalse($client->can('view', $entry));
    }
}
