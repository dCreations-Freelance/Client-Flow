<?php

namespace Database\Factories;

use App\Models\MessageAttachment;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends Factory<MessageAttachment>
 */
class MessageAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalName = fake()->word().'.pdf';
        $filename = MessageAttachment::generateFilename($originalName);

        return [
            'message_id' => ProjectMessage::factory(),
            'user_id' => User::factory(),
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 5_242_880),
        ];
    }

    /**
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
     * @return static
     */
    public function large(): static
    {
        return $this->state(fn (): array => [
            'size' => 9_500_000,
        ]);
    }

    /**
     * @return static
     */
    public function fromUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Devuelve un `UploadedFile` falso para tests de subida.
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
