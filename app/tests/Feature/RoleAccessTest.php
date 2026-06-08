<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard_only(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/portal/dashboard')->assertForbidden();
    }

    public function test_client_can_access_portal_dashboard_only(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)->get('/portal/dashboard')->assertOk();
        $this->actingAs($client)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->get('/portal/dashboard')->assertRedirect(route('login'));
    }
}
