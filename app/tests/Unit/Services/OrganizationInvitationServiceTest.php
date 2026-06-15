<?php

namespace Tests\Unit\Services;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\OrganizationInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganizationInvitationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_genera_y_hashea_el_token(): void
    {
        $org = Organization::factory()->create();
        $inviter = User::factory()->admin()->create();
        $service = app(OrganizationInvitationService::class);

        [$invitation, $rawToken] = $service->create(
            $org,
            'invitado@example.com',
            OrganizationUserRole::Member,
            $inviter,
        );

        $this->assertNotSame($rawToken, $invitation->token);
        $this->assertTrue(Hash::check($rawToken, $invitation->token));
        $this->assertSame('invitado@example.com', $invitation->email);
    }

    public function test_find_by_raw_token_devuelve_la_invitacion_correcta(): void
    {
        $org = Organization::factory()->create();
        $inviter = User::factory()->admin()->create();
        $service = app(OrganizationInvitationService::class);

        [$invitation, $rawToken] = $service->create(
            $org,
            'a@example.com',
            OrganizationUserRole::Member,
            $inviter,
        );

        $found = $service->findByRawToken($rawToken);

        $this->assertNotNull($found);
        $this->assertTrue($found->is($invitation));
    }

    public function test_find_by_raw_token_devuelve_null_si_token_no_coincide(): void
    {
        $org = Organization::factory()->create();
        $service = app(OrganizationInvitationService::class);

        $service->create(
            $org,
            'a@example.com',
            OrganizationUserRole::Member,
            User::factory()->admin()->create(),
        );

        $this->assertNull($service->findByRawToken('token-que-no-existe'));
    }

    public function test_accept_asocia_al_usuario_y_marca_aceptada(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $service = app(OrganizationInvitationService::class);

        [$invitation, ] = $service->create(
            $org,
            $user->email,
            OrganizationUserRole::Owner,
            User::factory()->admin()->create(),
        );

        $service->accept($invitation, $user);

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
        $this->assertTrue(
            $org->members()
                ->where('users.id', $user->id)
                ->wherePivot('role', OrganizationUserRole::Owner->value)
                ->exists()
        );
    }

    public function test_accept_no_duplica_si_usuario_ya_es_miembro(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $org->members()->attach($user->id, ['role' => 'member']);

        $service = app(OrganizationInvitationService::class);
        [$invitation, ] = $service->create(
            $org,
            $user->email,
            OrganizationUserRole::Owner,
            User::factory()->admin()->create(),
        );

        $service->accept($invitation, $user);

        $this->assertSame(1, $org->members()->where('users.id', $user->id)->count());
    }

    public function test_accept_es_idempotente_sobre_invitacion_ya_aceptada(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $service = app(OrganizationInvitationService::class);

        [$invitation, ] = $service->create(
            $org,
            $user->email,
            OrganizationUserRole::Member,
            User::factory()->admin()->create(),
        );

        $service->accept($invitation, $user);
        $service->accept($invitation, $user); // segunda vez

        $this->assertSame(1, $org->members()->where('users.id', $user->id)->count());
    }
}
