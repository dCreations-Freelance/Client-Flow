<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory del modelo `ActivityLog`.
 *
 * Crea entradas realistas del feed para tests y seeders. El
 * estado por defecto genera un `TaskCreated` con autor y
 * proyecto. Se exponen estados especificos por tipo (`taskCreated`,
 * `documentCreated`, etc.) para que los tests puedan sembrar
 * un feed con un mix concreto de eventos.
 *
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Estado por defecto: una entrada de "Tarea creada" en un
     * proyecto con autor aleatorio. Es el caso mas comun en el
     * feed y sirve para tests que no necesitan un tipo concreto.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'type' => ActivityType::TaskCreated,
            'description' => fake()->sentence(6),
            'subject_type' => null,
            'subject_id' => null,
            'properties' => null,
        ];
    }

    /**
     * Marca el evento como del tipo `TaskCreated`. Pensado para
     * tests que necesitan un feed poblado rapidamente.
     *
     * @return static
     */
    public function taskCreated(): static
    {
        return $this->state(fn (): array => [
            'type' => ActivityType::TaskCreated,
            'description' => 'Daniel creo la tarea Login',
        ]);
    }

    /**
     * Marca el evento como del tipo `TaskCompleted`.
     *
     * @return static
     */
    public function taskCompleted(): static
    {
        return $this->state(fn (): array => [
            'type' => ActivityType::TaskCompleted,
            'description' => 'Lucia completo la tarea Login',
        ]);
    }

    /**
     * Marca el evento como del tipo `TaskMoved`.
     *
     * @return static
     */
    public function taskMoved(): static
    {
        return $this->state(fn (): array => [
            'type' => ActivityType::TaskMoved,
            'description' => 'Daniel movio Login a En curso',
            'properties' => [
                'from_column' => 'todo',
                'to_column' => 'in_progress',
            ],
        ]);
    }

    /**
     * Marca el evento como del tipo `DocumentCreated` con
     * visibilidad publica. Pensado para tests del portal.
     *
     * @return static
     */
    public function publicDocumentCreated(): static
    {
        return $this->state(fn (): array => [
            'type' => ActivityType::DocumentCreated,
            'description' => 'Daniel creo el documento Manual',
            'properties' => [
                'visibility' => 'public',
            ],
        ]);
    }

    /**
     * Marca el evento como del tipo `DocumentCreated` con
     * visibilidad privada. Pensado para tests que verifican
     * que el portal no ve documentos privados.
     *
     * @return static
     */
    public function privateDocumentCreated(): static
    {
        return $this->state(fn (): array => [
            'type' => ActivityType::DocumentCreated,
            'description' => 'Daniel creo el documento Notas internas',
            'properties' => [
                'visibility' => 'private',
            ],
        ]);
    }

    /**
     * Marca el evento como del tipo `MessageSent`. Pensado para
     * tests del feed de chat humano.
     *
     * @return static
     */
    public function messageSent(): static
    {
        return $this->state(fn (): array => [
            'type' => ActivityType::MessageSent,
            'description' => 'Daniel envio un mensaje',
        ]);
    }

    /**
     * Marca el evento como del tipo `MemberAdded`. Privado
     * (admin-only). Pensado para tests que verifican la
     * exclusion del portal.
     *
     * @return static
     */
    public function memberAdded(): static
    {
        return $this->state(fn (): array => [
            'type' => ActivityType::MemberAdded,
            'description' => 'Daniel anadio a Lucia al proyecto',
        ]);
    }

    /**
     * Fija el proyecto del evento y ajusta la `organization_id`
     * para que coincida. Pensado para tests que siembran un
     * feed completo de un solo proyecto.
     *
     * @param  Project  $project
     * @return static
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (): array => [
            'project_id' => $project->id,
            'organization_id' => $project->organization_id,
        ]);
    }

    /**
     * Fija el autor del evento. Pensado para tests que verifican
     * la atribucion.
     *
     * @param  User  $user
     * @return static
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }
}
