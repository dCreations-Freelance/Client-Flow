<?php

namespace Tests\Unit\Models;

use App\Enums\MessageType;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios del modelo `ProjectMessage`.
 *
 * Cubren: casts, scopes (`text`, `system`, `before`, `after`,
 * `chronological`, `recent`) y helpers (`isText`, `isSystem`,
 * `isFile`, `isFromUserId`).
 */
class ProjectMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_castea_type_al_enum(): void
    {
        $message = ProjectMessage::factory()->system()->create();

        $this->assertInstanceOf(MessageType::class, $message->type);
        $this->assertSame(MessageType::System, $message->type);
    }

    public function test_scope_text_devuelve_solo_mensajes_de_texto(): void
    {
        ProjectMessage::factory()->count(2)->create();
        ProjectMessage::factory()->system()->count(3)->create();

        $this->assertSame(2, ProjectMessage::text()->count());
    }

    public function test_scope_system_devuelve_solo_mensajes_de_sistema(): void
    {
        ProjectMessage::factory()->count(2)->create();
        ProjectMessage::factory()->system()->count(3)->create();

        $this->assertSame(3, ProjectMessage::system()->count());
    }

    public function test_scope_before_y_after(): void
    {
        $m1 = ProjectMessage::factory()->create();
        $m2 = ProjectMessage::factory()->create();
        $m3 = ProjectMessage::factory()->create();

        $this->assertSame([$m1->id, $m2->id], ProjectMessage::before($m3->id)->pluck('id')->all());
        $this->assertSame([$m3->id], ProjectMessage::after($m2->id)->pluck('id')->all());
    }

    public function test_scope_recent_devuelve_los_n_ultimos_en_orden_cronologico(): void
    {
        $old = ProjectMessage::factory()->create();
        $mid = ProjectMessage::factory()->create();
        $new = ProjectMessage::factory()->create();

        $ids = ProjectMessage::recent(2)->pluck('id')->all();

        // `recent(N)` hace orderByDesc(id)->limit(N), asi que devuelve
        // los dos ultimos por id; luego la vista los reordena con
        // `chronological` si quiere.
        $this->assertSame([$new->id, $mid->id], $ids);
    }

    public function test_scope_chronological_devuelve_orden_ascendente(): void
    {
        $old = ProjectMessage::factory()->create();
        $mid = ProjectMessage::factory()->create();
        $new = ProjectMessage::factory()->create();

        $ids = ProjectMessage::chronological()->pluck('id')->all();

        $this->assertSame([$old->id, $mid->id, $new->id], $ids);
    }

    public function test_helpers_de_tipo(): void
    {
        $text = ProjectMessage::factory()->create();
        $system = ProjectMessage::factory()->system()->create();

        $this->assertTrue($text->isText());
        $this->assertFalse($text->isSystem());
        $this->assertFalse($text->isFile());

        $this->assertTrue($system->isSystem());
        $this->assertFalse($system->isText());
    }

    public function test_is_from_user_id_devuelve_true_solo_si_coincide(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $message = ProjectMessage::factory()->fromUser($user)->create();

        $this->assertTrue($message->isFromUserId($user->id));
        $this->assertFalse($message->isFromUserId($other->id));
        $this->assertFalse($message->isFromUserId(null));
    }

    public function test_relaciones_project_y_user(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $message = ProjectMessage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $this->assertSame($project->id, $message->project->id);
        $this->assertSame($user->id, $message->user->id);
    }
}
