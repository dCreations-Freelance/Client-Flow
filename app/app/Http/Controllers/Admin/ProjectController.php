<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::with('client')->latest()->paginate(12);

        return view('admin.projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('admin.projects.create', [
            'project' => new Project(['status' => 'planning', 'progress' => 0, 'is_visible_to_client' => true]),
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validated($request);
        $attributes['slug'] = $this->uniqueSlug($attributes['name']);
        $attributes['is_visible_to_client'] = $request->boolean('is_visible_to_client');

        $project = Project::create($attributes);

        return redirect()->route('admin.projects.show', $project)->with('status', 'Proyecto creado.');
    }

    public function show(Project $project): View
    {
        $project->load('client');

        return view('admin.projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.edit', [
            'project' => $project,
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $attributes = $this->validated($request);
        $attributes['is_visible_to_client'] = $request->boolean('is_visible_to_client');

        if ($project->name !== $attributes['name']) {
            $attributes['slug'] = $this->uniqueSlug($attributes['name'], $project);
        }

        $project->update($attributes);

        return redirect()->route('admin.projects.show', $project)->with('status', 'Proyecto actualizado.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('admin.projects.index')->with('status', 'Proyecto eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'goal' => ['nullable', 'string'],
            'status' => ['required', 'in:planning,in_progress,waiting_client,in_review,completed,paused,archived'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'current_phase' => ['nullable', 'string', 'max:255'],
            'next_milestone' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'estimated_ends_at' => ['nullable', 'date'],
        ]);
    }

    private function uniqueSlug(string $name, ?Project $ignore = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Project::where('slug', $slug)->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
