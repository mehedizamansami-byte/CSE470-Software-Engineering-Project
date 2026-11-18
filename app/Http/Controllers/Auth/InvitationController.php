<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CONTROLLER layer: a staff member accepts a seller's team invitation (Feature 4).
 */
class InvitationController extends Controller
{
    public function show(string $token): View
    {
        return view('auth.accept-invitation', ['invite' => $this->find($token)]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invite = $this->find($token);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (User::where('email', $invite->email)->exists()) {
            return back()->withErrors(['name' => 'An account with this email already exists. Ask the seller to invite a different email.']);
        }

        $user = DB::transaction(function () use ($invite, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $invite->email,
                'password' => $data['password'],
                'role' => User::ROLE_COLLABORATOR,
                'is_active' => true,
            ]);

            // Opening the emailed link proves the email address, so no OTP is needed.
            $user->forceFill(['email_verified_at' => now()])->save();

            $invite->update([
                'user_id' => $user->id,
                'name' => $user->name,
                'status' => TeamMember::STATUS_ACTIVE,
                'token' => null,
                'accepted_at' => now(),
            ]);

            return $user;
        });

        AuditLogger::log($invite->seller, 'team.joined', "{$user->name} accepted the invitation and joined the team", $user);

        $invite->seller->notify(new AppNotification(
            'Invitation accepted',
            "{$user->name} ({$user->email}) joined your team.",
            route('team.index', [], false),
            'success',
        ));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome to the team! You can now work in '.$invite->seller->name.'\'s workspace.');
    }

    private function find(string $token): TeamMember
    {
        return TeamMember::with('seller.shop')
            ->where('token', $token)
            ->where('status', TeamMember::STATUS_PENDING)
            ->firstOrFail();
    }
}
