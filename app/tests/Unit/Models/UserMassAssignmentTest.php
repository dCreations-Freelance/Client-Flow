<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Verifica que `password` no es asignable en masa en el modelo User
 * (auditoria L-04). El objetivo es que un `User::create([...])` con
 * un input no controlado no pueda fijar contrasena en claro.
 */
class UserMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_no_se_asigna_via_mass_assignment(): void
    {
        // `User::make` no persiste: simula la asignacion masiva que
        // haria `User::create($request->all())`. `password` debe
        // quedar como null porque ya no esta en `$fillable`.
        $user = User::make([
            'name' => 'Sin Password',
            'email' => 'sin@example.com',
            'password' => 'valor-que-debe-ignorarse',
        ]);

        $this->assertNull($user->password);
    }

    public function test_password_se_puede_asignar_y_hashear_individualmente(): void
    {
        $user = User::make([
            'name' => 'Con Password',
            'email' => 'con@example.com',
        ]);

        $user->password = 'contrasena-plana';
        $user->save();

        $user->refresh();

        $this->assertNotNull($user->password);
        $this->assertNotSame('contrasena-plana', $user->password);
        $this->assertTrue(Hash::check('contrasena-plana', $user->password));
    }
}
