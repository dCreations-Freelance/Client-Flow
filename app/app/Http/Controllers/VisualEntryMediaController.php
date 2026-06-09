<?php

namespace App\Http\Controllers;

use App\Models\VisualEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisualEntryMediaController extends Controller
{
    public function show(Request $request, VisualEntry $visualEntry): StreamedResponse
    {
        $visualEntry->load('project.client');

        abort_unless($this->canView($request, $visualEntry), 404);
        abort_unless(Storage::exists($visualEntry->media_path), 404);

        return Storage::response($visualEntry->media_path, $visualEntry->media_file_name, [
            'Content-Type' => $visualEntry->media_mime_type,
        ]);
    }

    private function canView(Request $request, VisualEntry $visualEntry): bool
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return true;
        }

        return $user->client?->is($visualEntry->project->client)
            && $visualEntry->project->is_visible_to_client
            && $visualEntry->visibility === VisualEntry::VISIBILITY_PUBLIC;
    }
}
