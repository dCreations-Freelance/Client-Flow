<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClientInvitationController extends Controller
{
    public function create(): View
    {
        return view('admin.clients.invite');
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
        ]);

        $client = Client::firstOrCreate(
            ['email' => $attributes['email']],
            [
                'name' => $attributes['name'],
                'company' => $attributes['company'] ?? null,
                'status' => 'active',
            ]
        );

        $invitation = ClientInvitation::create([
            'client_id' => $client->id,
            'email' => $client->email,
            'token' => Str::random(48),
            'expires_at' => now()->addDays(7),
            'created_by' => $request->user()->id,
        ]);

        $client->update(['invitation_status' => 'sent']);

        return redirect()->route('admin.clients.show', $client)->with(
            'status',
            'Invitacion creada: '.route('invitation.accept', $invitation->token)
        );
    }
}
