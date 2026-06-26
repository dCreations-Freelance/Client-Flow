<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Crea un usuario administrador o convierte uno existente en admin.
 *
 * El registro publico solo genera cuentas con rol `client`, por lo que
 * este comando es la unica via para crear el primer administrador y
 * para promover a clientes a admin en entornos pequenos. Trabaja en
 * modo interactivo por defecto para minimizar errores de tecleo en
 * consola.
 */
class CreateAdminUser extends Command
{
    /**
     * Nombre y firma del comando. Se invoca con `php artisan clientflow:create-admin`.
     *
     * @var string
     */
    protected $signature = 'clientflow:create-admin';

    /**
     * Descripcion que aparece en `php artisan list`.
     *
     * @var string
     */
    protected $description = 'Crea un usuario administrador o convierte uno existente en admin';

    /**
     * Logica principal. Pide los datos por consola, valida, y crea o
     * actualiza la cuenta. El control de errores se hace con `$this->error`
     * y `$this->info` para mantener la salida coherente con el resto de
     * comandos de Laravel.
     */
    public function handle(): int
    {
        $name = (string) $this->ask('Name');
        $email = (string) $this->ask('Email');
        $password = (string) $this->secret('Password');
        $confirmation = (string) $this->secret('Confirm password');

        if ($password !== $confirmation) {
            $this->error('Las contrasenas no coinciden.');

            return self::INVALID;
        }

        $validator = validator([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:160'],
            'password' => ['required', Password::min(8)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::INVALID;
        }

        $existing = User::where('email', $email)->first();

        if ($existing !== null) {
            $shouldConvert = (bool) $this->confirm(
                'A user with this email already exists. Convert it to admin and update the password?',
                false
            );

            if (! $shouldConvert) {
                $this->info('Operacion cancelada.');

                return self::SUCCESS;
            }

            $existing->forceFill([
                'name' => $name,
                'password' => $password,
                'role' => UserRole::Admin,
            ])->save();

            $this->info('Admin user updated.');

            return self::SUCCESS;
        }

        $user =         // `new User()` + `forceFill` + `save` (no `User::create`) porque
        // `password` ya no esta en `$fillable` (auditoria L-04) y un
        // `create` intermedio violaria la restriccion NOT NULL. El cast
        // `'hashed'` se encarga de hashear al persistir.
        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'role' => UserRole::Admin,
            'password' => $password,
        ]);
        $user->save();

        $this->info('Admin user created.');

        return self::SUCCESS;
    }
}
