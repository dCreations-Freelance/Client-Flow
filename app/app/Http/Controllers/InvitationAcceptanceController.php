<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ClientInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InvitationAcceptanceController extends Controller
{
    public function create(string $token): View
    {
        $invitation = ClientInvitation::where('token', $token)->firstOrFail();

        abort_unless($invitation->isAcceptable(), 403);

        return view('auth.invitation', compact('invitation'));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invitation = ClientInvitation::with('client')->where('token', $token)->firstOrFail();

        abort_unless($invitation->isAcceptable(), 403);

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $attributes['name'],
            'email' => $invitation->email,
            'password' => $attributes['password'],
            'role' => UserRole::Client,
        ]);

        $invitation->client->update([
            'user_id' => $user->id,
            'name' => $attributes['name'],
            'invitation_status' => 'accepted',
        ]);

        $invitation->update(['accepted_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }
}
