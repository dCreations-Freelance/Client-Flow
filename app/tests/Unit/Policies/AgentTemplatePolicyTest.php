<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\AgentTemplatePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la policy `AgentTemplatePolicy`.
 *
 * La biblioteca de templates es admin-only: cualquier cliente
 * recibe `false` en todas las acciones. Estos tests blindan
 * esa decision: si alguien intenta relajar la policy, los
 * tests fallan y obligan a actualizar la documentacion.
 */
class AgentTemplatePolicyTest extends TestCase
{
    use RefreshDatabase;

    private AgentTemplatePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AgentTemplatePolicy();
    }

    public function test_admin_puede_hacer_todo(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin));
        $this->assertTrue($this->policy->delete($admin));
    }

    public function test_cliente_no_puede_hacer_nada(): void
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
        $this->assertFalse($this->policy->view($client));
        $this->assertFalse($this->policy->create($client));
        $this->assertFalse($this->policy->update($client));
        $this->assertFalse($this->policy->delete($client));
    }
}
