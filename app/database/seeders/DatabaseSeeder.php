<?php

namespace Database\Seeders;

use App\Enums\OrganizationUserRole;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder base para tener un entorno navegable al instalar.
 *
 * Crea un admin (`admin@clientflow.test` / `password`), un cliente
 * (`cliente@clientflow.test` / `password`) y una organizacion donde
 * el cliente es miembro. Asi se puede probar el flujo completo sin
 * tener que invitar manualmente a un usuario.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@clientflow.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ]
        );

        $client = User::firstOrCreate(
            ['email' => 'cliente@clientflow.test'],
            [
                'name' => 'Cliente Demo',
                'password' => Hash::make('password'),
                'role' => UserRole::Client,
            ]
        );

        // Comprobamos primero si la organizacion existe para evitar el
        // camino de `firstOrCreate` con la columna `slug` (que se genera
        // en un evento del modelo y por seguridad la creamos con el slug
        // ya calculado).
        $organization = Organization::where('name', 'Cliente Demo S.L.')->first();

        if ($organization === null) {
            $organization = Organization::create([
                'name' => 'Cliente Demo S.L.',
                'slug' => Organization::generateUniqueSlug('Cliente Demo S.L.'),
                'description' => 'Organizacion de ejemplo para que el cliente pueda explorar el portal.',
                'owner_id' => $admin->id,
            ]);
        }

        if (! $organization->members()->where('users.id', $admin->id)->exists()) {
            $organization->members()->attach($admin->id, [
                'role' => OrganizationUserRole::Owner->value,
            ]);
        }

        if (! $organization->members()->where('users.id', $client->id)->exists()) {
            $organization->members()->attach($client->id, [
                'role' => OrganizationUserRole::Member->value,
            ]);
        }
    }
}
