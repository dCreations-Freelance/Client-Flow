<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\VisualEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisualEntryController extends Controller
{
    public function index(Request $request, Project $project): View
    {
        $this->authorizeProject($request, $project);

        $entries = $project->visualEntries()
            ->public()
            ->with('author')
            ->latest('published_at')
            ->latest()
            ->get();

        return view('portal.visual-entries.index', [
            'project' => $project->load('client'),
            'entries' => $entries,
        ]);
    }

    public function show(Request $request, Project $project, VisualEntry $visualEntry): View
    {
        $this->authorizeProject($request, $project);
        abort_unless($visualEntry->project_id === $project->id && $visualEntry->visibility === VisualEntry::VISIBILITY_PUBLIC, 404);

        return view('portal.visual-entries.show', [
            'project' => $project->load('client'),
            'entry' => $visualEntry->load('author'),
        ]);
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        abort_unless(
            $project->is_visible_to_client && $request->user()->client?->is($project->client),
            404
        );
    }
}
