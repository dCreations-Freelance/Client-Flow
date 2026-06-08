<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_user_interactively(): void
    {
        $this->artisan('clientflow:create-admin')
            ->expectsQuestion('Name', 'Admin User')
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Password', 'password')
            ->expectsQuestion('Confirm password', 'password')
            ->expectsOutputToContain('Admin user created.')
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($user->isAdmin());
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_it_can_convert_an_existing_user_to_admin(): void
    {
        $user = User::factory()->client()->create([
            'email' => 'client@example.com',
            'password' => 'old-password',
        ]);

        $this->artisan('clientflow:create-admin')
            ->expectsQuestion('Name', 'Admin Converted')
            ->expectsQuestion('Email', 'client@example.com')
            ->expectsQuestion('Password', 'new-password')
            ->expectsQuestion('Confirm password', 'new-password')
            ->expectsConfirmation('A user with this email already exists. Convert it to admin and update the password?', 'yes')
            ->expectsOutputToContain('Admin user updated.')
            ->assertSuccessful();

        $user->refresh();

        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertSame('Admin Converted', $user->name);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }
}
