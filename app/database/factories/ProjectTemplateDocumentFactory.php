<?php

namespace Database\Factories;

use App\Enums\DocumentVisibility;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTemplateDocument>
 */
class ProjectTemplateDocumentFactory extends Factory
{
    /**
     * Estado por defecto: documento privado con
     * titulo y contenido markdown realistas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->randomElement([
            'README del proyecto',
            'Convenciones de codigo',
            'Onboarding del equipo',
            'Politica de seguridad',
            'Guia de despliegue',
        ]);

        return [
            'template_id' => ProjectTemplate::factory(),
            'title' => $title,
            // Siempre con contenido: el modelo `ProjectDocument`
            // tiene `content` NOT NULL, asi que copiar un doc
            // con `content = null` fallaria. La factory
            // garantiza que el doc sea copiable.
            'content' => fake()->paragraphs(2, true),
            'visibility' => DocumentVisibility::Private,
            'position' => 0,
        ];
    }

    /**
     * Fija la plantilla a la que pertenece el
     * documento.
     *
     * @return static
     */
    public function forTemplate(ProjectTemplate $template): static
    {
        return $this->state(fn (): array => [
            'template_id' => $template->id,
        ]);
    }

    /**
     * Documento publico (visible por el cliente del
     * portal tras aplicar la plantilla).
     *
     * @return static
     */
    public function public(): static
    {
        return $this->state(fn (): array => [
            'visibility' => DocumentVisibility::Public,
        ]);
    }
}
