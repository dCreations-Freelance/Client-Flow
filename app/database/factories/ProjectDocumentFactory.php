<?php

namespace Database\Factories;

use App\Enums\DocumentVisibility;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectDocument>
 */
class ProjectDocumentFactory extends Factory
{
    /**
     * Estado por defecto: documento privado, dentro de un proyecto y
     * creado por un usuario cualquiera. Se usa en seeders y tests.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'content' => $this->fakeMarkdown(),
            'visibility' => DocumentVisibility::Private,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Documento publico. Pensado para listar docs visibles por
     * clientes en el portal.
     *
     * @return static
     */
    public function public(): static
    {
        return $this->state(fn (): array => [
            'visibility' => DocumentVisibility::Public,
        ]);
    }

    /**
     * Documento privado. Equivalente al estado por defecto pero
     * explicito para tests donde se quiere ser claro.
     *
     * @return static
     */
    public function private(): static
    {
        return $this->state(fn (): array => [
            'visibility' => DocumentVisibility::Private,
        ]);
    }

    /**
     * Documento con un contenido markdown mas largo y variado.
     * Util para tests de busqueda por contenido.
     *
     * @return static
     */
    public function withLongContent(): static
    {
        return $this->state(fn (): array => [
            'content' => implode("\n\n", [
                '# '.fake()->sentence(3),
                fake()->paragraph(),
                '## '.fake()->sentence(3),
                fake()->paragraph(),
                '- '.fake()->sentence(),
                '- '.fake()->sentence(),
                '- '.fake()->sentence(),
                '```php',
                '// '.fake()->sentence(),
                'echo "hola";',
                '```',
                fake()->paragraph(),
            ]),
        ]);
    }

    /**
     * Genera un bloque markdown corto pero realista. Usado por el
     * estado por defecto para que los tests puedan asertar contra
     * headings, listas y parrafos sin ser trivial.
     */
    private function fakeMarkdown(): string
    {
        return implode("\n\n", [
            '# '.fake()->sentence(3),
            fake()->paragraph(),
            '## '.fake()->sentence(3),
            fake()->paragraph(),
            '- '.fake()->sentence(),
            '- '.fake()->sentence(),
        ]);
    }
}
