<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectTimelineController extends Controller
{
    public function show(Request $request, Project $project): View
    {
        abort_unless(
            $project->is_visible_to_client && $request->user()->client?->is($project->client),
            404
        );

        $project->load('client');
        $updates = $project->updates()->public()->with('author')->latest('published_at')->latest()->get();

        return view('portal.projects.timeline', compact('project', 'updates'));
    }
}
