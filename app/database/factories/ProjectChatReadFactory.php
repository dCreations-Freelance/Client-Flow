<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectChatRead;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectChatRead>
 */
class ProjectChatReadFactory extends Factory
{
    /**
     * Estado por defecto: marcador de lectura recien creado sin
     * mensajes leidos. Se usa en tests cuando se quiere verificar
     * el comportamiento del `unreadCount`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'last_read_message_id' => null,
            'last_email_sent_at' => null,
        ];
    }

    /**
     * Marcador apuntando al ultimo mensaje del proyecto. Pensado
     * para tests de "todo leido" (unreadCount = 0).
     *
     * @return static
     */
    public function upToLastMessage(): static
    {
        return $this->state(function (): array {
            $project = Project::factory()->create();
            $last = ProjectMessage::factory()->create(['project_id' => $project->id]);

            return [
                'project_id' => $project->id,
                'last_read_message_id' => $last->id,
            ];
        });
    }
}
