<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Notifications\ProjectUpdatePublished;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class ProjectUpdateController extends Controller
{
    public function index(Project $project): View
    {
        $project->load('client');
        $updates = $project->updates()->with('author')->latest('published_at')->latest()->get();

        return view('admin.projects.updates.index', compact('project', 'updates'));
    }

    public function create(Project $project): View
    {
        $project->load('client');

        return view('admin.projects.updates.create', compact('project'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'visibility' => ['required', 'in:public,internal'],
            'notify_client' => ['nullable', 'boolean'],
        ]);

        $update = $project->updates()->create([
            ...$attributes,
            'author_id' => $request->user()->id,
            'type' => 'update',
            'notify_client' => $request->boolean('notify_client') && $attributes['visibility'] === ProjectUpdate::VISIBILITY_PUBLIC,
            'published_at' => now(),
        ]);

        if ($update->notify_client && $project->is_visible_to_client && $project->client?->email) {
            Notification::route('mail', $project->client->email)
                ->notify(new ProjectUpdatePublished($update->loadMissing('project.client')));
        }

        return redirect()->route('admin.projects.timeline', $project)->with('status', 'Actualizacion publicada.');
    }
}
