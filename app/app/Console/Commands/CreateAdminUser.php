<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminUser extends Command
{
    protected $signature = 'clientflow:create-admin {--force : Update an existing user without confirmation}';

    protected $description = 'Create a ClientFlow administrator user interactively';

    public function handle(): int
    {
        $this->components->info('Create ClientFlow administrator');

        $name = $this->askRequired('Name');
        $email = $this->askValidEmail();
        $password = $this->askValidPassword();

        $user = User::where('email', $email)->first();

        if ($user && ! $this->option('force') && ! $this->confirm('A user with this email already exists. Convert it to admin and update the password?', false)) {
            $this->components->warn('No changes were made.');

            return self::SUCCESS;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => UserRole::Admin,
            ]
        );

        $this->newLine();
        $this->components->info($user->wasRecentlyCreated ? 'Admin user created.' : 'Admin user updated.');
        $this->line('Email: '.$user->email);
        $this->line('Login: '.url('/login'));

        return self::SUCCESS;
    }

    private function askRequired(string $label): string
    {
        do {
            $value = trim((string) $this->ask($label));

            if ($value !== '') {
                return $value;
            }

            $this->components->error($label.' is required.');
        } while (true);
    }

    private function askValidEmail(): string
    {
        do {
            $email = trim((string) $this->ask('Email'));
            $validator = Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']]);

            if (! $validator->fails()) {
                return $email;
            }

            $this->components->error('Enter a valid email address.');
        } while (true);
    }

    private function askValidPassword(): string
    {
        do {
            $password = (string) $this->secret('Password');
            $confirmation = (string) $this->secret('Confirm password');

            $validator = Validator::make(
                ['password' => $password, 'password_confirmation' => $confirmation],
                ['password' => ['required', 'confirmed', Password::defaults()]]
            );

            if (! $validator->fails()) {
                return $password;
            }

            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }
        } while (true);
    }
}
