<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\VisualEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VisualEntryController extends Controller
{
    public function index(Request $request): View
    {
        $entries = VisualEntry::with(['project.client', 'author'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('visibility'), fn ($query) => $query->where('visibility', $request->string('visibility')))
            ->latest('published_at')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.visual-entries.index', [
            'entries' => $entries,
            'types' => VisualEntry::TYPES,
        ]);
    }

    public function create(Project $project): View
    {
        $project->load('client');

        return view('admin.visual-entries.create', [
            'project' => $project,
            'types' => VisualEntry::TYPES,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:'.implode(',', array_keys(VisualEntry::TYPES))],
            'visibility' => ['required', 'in:public,internal'],
            'media' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm,audio/mpeg,audio/mp4,audio/wav,audio/ogg', 'max:51200'],
        ]);

        $file = $request->file('media');
        $storedName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs("clientflow/projects/{$project->id}/visual", $storedName);

        $entry = $project->visualEntries()->create([
            ...$attributes,
            'author_id' => $request->user()->id,
            'media_path' => $path,
            'media_file_name' => $file->getClientOriginalName(),
            'media_mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'media_size' => $file->getSize(),
            'published_at' => now(),
        ]);

        return redirect()->route('admin.visual-entries.show', $entry)->with('status', 'Entrada visual publicada.');
    }

    public function show(VisualEntry $visualEntry): View
    {
        $visualEntry->load(['project.client', 'author']);

        return view('admin.visual-entries.show', ['entry' => $visualEntry]);
    }
}
