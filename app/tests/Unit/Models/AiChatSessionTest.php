<?php

namespace Tests\Unit\Models;

use App\Enums\AiChatRole;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiChatSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_project_and_user(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $session = AiChatSession::factory()->forProject($project)->forUser($user)->create();

        $this->assertSame($project->id, $session->project->id);
        $this->assertSame($user->id, $session->user->id);
    }

    public function test_messages_relation_returns_messages_in_chronological_order(): void
    {
        $session = AiChatSession::factory()->create();
        AiChatMessage::factory()->for($session, 'session')->assistant()->create(['created_at' => now()->subMinutes(2)]);
        AiChatMessage::factory()->for($session, 'session')->create(['created_at' => now()->subMinutes(1)]);
        AiChatMessage::factory()->for($session, 'session')->assistant()->create(['created_at' => now()]);

        $orderedIds = $session->messages()->pluck('id')->all();
        $idsAsc = $orderedIds;
        sort($idsAsc);

        $this->assertSame($idsAsc, $orderedIds);
    }

    public function test_display_title_returns_custom_title_when_set(): void
    {
        $session = AiChatSession::factory()->titled('Reunion Kickoff')->create();
        $this->assertSame('Reunion Kickoff', $session->displayTitle());
    }

    public function test_display_title_falls_back_to_first_user_message(): void
    {
        $session = AiChatSession::factory()->create();
        AiChatMessage::factory()->for($session, 'session')->system()->create(['content' => 'system prompt']);
        AiChatMessage::factory()->for($session, 'session')->create(['content' => 'Hola, ¿como va el proyecto?']);

        $this->assertSame('Hola, ¿como va el proyecto?', $session->displayTitle());
    }

    public function test_display_title_truncates_long_messages_to_60_chars(): void
    {
        $session = AiChatSession::factory()->create();
        $long = str_repeat('a', 100);
        AiChatMessage::factory()->for($session, 'session')->create(['content' => $long]);

        $title = $session->displayTitle();
        $this->assertSame(63, mb_strlen($title));
        $this->assertStringEndsWith('...', $title);
    }

    public function test_display_title_returns_generic_text_for_empty_sessions(): void
    {
        $session = AiChatSession::factory()->create();
        $this->assertSame('Nueva conversacion', $session->displayTitle());
    }

    public function test_scopes_filter_correctly(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $own = AiChatSession::factory()->forUser($user)->forProject($project)->create();
        $other = AiChatSession::factory()->create();

        $this->assertSame([$own->id], AiChatSession::forUser($user->id)->pluck('id')->all());
        $this->assertSame([$own->id], AiChatSession::forProject($project->id)->pluck('id')->all());
        $this->assertSame([$own->id], AiChatSession::forUserInProject($user->id, $project->id)->pluck('id')->all());
        $this->assertNotContains($other->id, AiChatSession::forUserInProject($user->id, $project->id)->pluck('id')->all());
    }
}
