<?php

namespace Tests\Unit\Models;

use App\Enums\DocumentVisibility;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios del modelo `ProjectDocument`.
 *
 * Cubren: casts, scopes (`public`, `private`, `forProject`, `search`,
 * `recent`), accessors (`rendered_content`, `excerpt`) y helpers de
 * visibilidad.
 */
class ProjectDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_castea_visibility_al_enum(): void
    {
        $document = ProjectDocument::factory()->public()->create();

        $this->assertInstanceOf(DocumentVisibility::class, $document->visibility);
        $this->assertSame(DocumentVisibility::Public, $document->visibility);
    }

    public function test_scope_public_devuelve_solo_documentos_publicos(): void
    {
        ProjectDocument::factory()->public()->count(2)->create();
        ProjectDocument::factory()->private()->count(3)->create();

        $this->assertSame(2, ProjectDocument::public()->count());
    }

    public function test_scope_private_devuelve_solo_documentos_privados(): void
    {
        ProjectDocument::factory()->public()->count(2)->create();
        ProjectDocument::factory()->private()->count(3)->create();

        $this->assertSame(3, ProjectDocument::private()->count());
    }

    public function test_scope_for_project_filtra_por_proyecto(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        ProjectDocument::factory()->count(2)->create(['project_id' => $project1->id]);
        ProjectDocument::factory()->count(3)->create(['project_id' => $project2->id]);

        $this->assertSame(2, ProjectDocument::forProject($project1->id)->count());
        $this->assertSame(3, ProjectDocument::forProject($project2->id)->count());
    }

    public function test_scope_search_busca_en_titulo_y_contenido(): void
    {
        ProjectDocument::factory()->create(['title' => 'Manual de instalacion', 'content' => 'Pasos para instalar.']);
        ProjectDocument::factory()->create(['title' => 'Guia de uso', 'content' => 'Para usar el sistema primero instala.']);
        ProjectDocument::factory()->create(['title' => 'Roadmap', 'content' => 'Sin coincidencias utiles.']);

        // Busqueda por titulo
        $byTitle = ProjectDocument::search('manual')->pluck('title')->all();
        $this->assertContains('Manual de instalacion', $byTitle);
        $this->assertCount(1, $byTitle);

        // Busqueda por contenido
        $byContent = ProjectDocument::search('instala')->pluck('title')->all();
        $this->assertCount(2, $byContent);
        $this->assertContains('Manual de instalacion', $byContent);
        $this->assertContains('Guia de uso', $byContent);
    }

    public function test_scope_search_con_termino_vacio_no_filtra(): void
    {
        ProjectDocument::factory()->count(3)->create();

        $this->assertSame(3, ProjectDocument::search('')->count());
        $this->assertSame(3, ProjectDocument::search('   ')->count());
    }

    public function test_scope_recent_ordena_por_actualizado_descendente(): void
    {
        $old = ProjectDocument::factory()->create(['updated_at' => now()->subDays(5)]);
        $new = ProjectDocument::factory()->create(['updated_at' => now()]);
        $middle = ProjectDocument::factory()->create(['updated_at' => now()->subDays(2)]);

        $ordered = ProjectDocument::recent()->pluck('id')->all();

        $this->assertSame($new->id, $ordered[0]);
        $this->assertSame($middle->id, $ordered[1]);
        $this->assertSame($old->id, $ordered[2]);
    }

    public function test_rendered_content_devuelve_html_del_markdown(): void
    {
        $document = ProjectDocument::factory()->create([
            'content' => "# Hola\n\nEsto es **importante**.",
        ]);

        $html = $document->rendered_content;

        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringContainsString('Hola', $html);
        $this->assertStringContainsString('<strong>', $html);
    }

    public function test_excerpt_trunca_y_omite_markdown(): void
    {
        $document = ProjectDocument::factory()->create([
            'content' => "# Titulo\n\nEste es un parrafo bastante largo que se va a truncar a una longitud concreta para mostrarlo en el listado.",
        ]);

        $excerpt = $document->excerpt(40);

        $this->assertLessThanOrEqual(40, mb_strlen($excerpt));
        $this->assertStringEndsWith('…', $excerpt);
        // El h1 y demas tags se han aplanado a texto plano
        $this->assertStringNotContainsString('# ', $excerpt);
        $this->assertStringContainsString('parrafo', $excerpt);
    }

    public function test_excerpt_devuelve_cadena_vacia_si_no_hay_contenido(): void
    {
        $document = ProjectDocument::factory()->create(['content' => '']);

        $this->assertSame('', $document->excerpt());
    }

    public function test_helpers_is_public_e_is_private(): void
    {
        $public = ProjectDocument::factory()->public()->create();
        $private = ProjectDocument::factory()->private()->create();

        $this->assertTrue($public->isPublic());
        $this->assertFalse($public->isPrivate());
        $this->assertTrue($private->isPrivate());
        $this->assertFalse($private->isPublic());
    }

    public function test_relaciones_project_y_creator(): void
    {
        $project = Project::factory()->create();
        $creator = User::factory()->create();
        $document = ProjectDocument::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
        ]);

        $this->assertSame($project->id, $document->project->id);
        $this->assertSame($creator->id, $document->creator->id);
    }
}
