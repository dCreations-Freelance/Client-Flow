<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends Factory<TaskAttachment>
 */
class TaskAttachmentFactory extends Factory
{
    /**
     * Estado por defecto: crea un archivo de prueba minimo en
     * `storage/framework/testing/` y registra la fila apuntando
     * a el. El nombre interno y el original se generan
     * deterministamente para que los tests sean reproducibles.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalName = fake()->word().'.pdf';
        $filename = TaskAttachment::generateFilename($originalName);

        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 5_242_880),
        ];
    }

    /**
     * Estado que representa un adjunto de imagen. Cambia mime y
     * tamano para que la vista renderice el icono correcto.
     *
     * @return static
     */
    public function image(): static
    {
        return $this->state(fn (): array => [
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(50_000, 1_500_000),
            'original_name' => fake()->word().'.png',
        ]);
    }

    /**
     * Adjunto de tamano grande (cerca del limite de 10 MB). Pensado
     * para tests de validacion.
     *
     * @return static
     */
    public function large(): static
    {
        return $this->state(fn (): array => [
            'size' => 9_500_000,
        ]);
    }

    /**
     * Adjunto de un usuario concreto (autor conocido en tests).
     *
     * @return static
     */
    public function fromUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Devuelve un `UploadedFile` falso que se puede usar en
     * tests de subida. Lo expone el factory sin tocar la fila
     * todavia, para que el test lo inyecte en la peticion
     * multipart.
     */
    public static function fakeUploadedFile(?string $name = null, ?string $mime = null): UploadedFile
    {
        $name ??= fake()->word().'.pdf';
        $mime ??= 'application/pdf';

        return UploadedFile::fake()->createWithContent(
            $name,
            'contenido de prueba para '.$name,
        );
    }
}
