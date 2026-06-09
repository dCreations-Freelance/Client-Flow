<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\VisualEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisualDiaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_visual_entry(): void
    {
        Storage::fake();

        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.projects.visual-entries.store', $project), [
            'title' => 'Captura del panel cliente',
            'description' => 'Primer avance visual del portal.',
            'type' => 'annotated_capture',
            'visibility' => 'public',
            'media' => UploadedFile::fake()->createWithContent('panel.jpg', "\xff\xd8\xff\xd9"),
        ]);

        $entry = VisualEntry::where('title', 'Captura del panel cliente')->firstOrFail();

        $response->assertRedirect(route('admin.visual-entries.show', $entry));
        $this->assertDatabaseHas('visual_entries', [
            'project_id' => $project->id,
            'author_id' => $admin->id,
            'visibility' => VisualEntry::VISIBILITY_PUBLIC,
            'media_file_name' => 'panel.jpg',
        ]);
        Storage::assertExists($entry->media_path);
    }

    public function test_client_visual_diary_only_shows_public_entries(): void
    {
        $user = User::factory()->client()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['client_id' => $client->id, 'is_visible_to_client' => true]);

        VisualEntry::factory()->create([
            'project_id' => $project->id,
            'title' => 'Demo visible',
            'visibility' => VisualEntry::VISIBILITY_PUBLIC,
        ]);
        VisualEntry::factory()->internal()->create([
            'project_id' => $project->id,
            'title' => 'Nota interna visual',
        ]);

        $response = $this->actingAs($user)->get(route('portal.projects.visual-entries.index', $project));

        $response->assertOk();
        $response->assertSee('Demo visible');
        $response->assertDontSee('Nota interna visual');
    }

    public function test_client_cannot_open_another_clients_visual_diary(): void
    {
        $user = User::factory()->client()->create();
        Client::factory()->create(['user_id' => $user->id]);
        $otherProject = Project::factory()->create(['is_visible_to_client' => true]);

        $response = $this->actingAs($user)->get(route('portal.projects.visual-entries.index', $otherProject));

        $response->assertNotFound();
    }

    public function test_client_cannot_access_internal_visual_media(): void
    {
        Storage::fake();

        $user = User::factory()->client()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['client_id' => $client->id, 'is_visible_to_client' => true]);
        $entry = VisualEntry::factory()->internal()->create([
            'project_id' => $project->id,
            'media_path' => 'clientflow/projects/'.$project->id.'/visual/internal.jpg',
        ]);

        Storage::put($entry->media_path, 'private media');

        $response = $this->actingAs($user)->get(route('visual-entries.media', $entry));

        $response->assertNotFound();
    }

    public function test_client_can_access_public_visual_media_for_their_project(): void
    {
        Storage::fake();

        $user = User::factory()->client()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['client_id' => $client->id, 'is_visible_to_client' => true]);
        $entry = VisualEntry::factory()->create([
            'project_id' => $project->id,
            'media_path' => 'clientflow/projects/'.$project->id.'/visual/public.jpg',
            'media_mime_type' => 'image/jpeg',
            'media_file_name' => 'public.jpg',
        ]);

        Storage::put($entry->media_path, 'public media');

        $response = $this->actingAs($user)->get(route('visual-entries.media', $entry));

        $response->assertOk();
    }
}
