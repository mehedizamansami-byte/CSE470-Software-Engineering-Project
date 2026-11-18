<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTROLLER layer: account settings, password, deactivate and delete (Feature 1).
 */
class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.edit', ['user' => $request->user()]);
    }

    public function update(Request $request, OtpService $otp): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
        ], [
            'phone.regex' => 'Enter an 11-digit Bangladeshi mobile number, for example 01712345678.',
        ]);

        $emailChanged = $data['email'] !== $user->email;
        $user->fill($data);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $code = $otp->issueForUser($user);

            return redirect()->route('otp.show')
                ->with('success', 'Please verify your new email address.')
                ->with('demo_code', $this->demo($code));
        }

        return back()->with('success', 'Account details saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => $request->input('password')]);
        $request->user()->notify(new AppNotification('Password changed', 'Your account password was changed.', null, 'info'));

        return back()->with('success', 'Your password has been changed.');
    }

    public function deactivate(Request $request): RedirectResponse
    {
        $request->validate(['deactivate_password' => ['required', 'current_password']], [
            'deactivate_password.current_password' => 'The password is incorrect.',
        ]);

        $request->user()->forceFill(['deactivated_at' => now()])->save();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Your account is deactivated. Log in again any time to reactivate it.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'delete_password' => ['required', 'current_password'],
            'confirm_text' => ['required', 'in:DELETE'],
        ], [
            'delete_password.current_password' => 'The password is incorrect.',
            'confirm_text.in' => 'Type DELETE in capital letters to confirm.',
        ]);

        $user = $request->user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::transaction(function () use ($user) {
            // A seller's collaborator accounts only exist for that seller's team.
            if ($user->role === User::ROLE_SELLER) {
                $collaboratorIds = $user->teamMembers()->whereNotNull('user_id')->pluck('user_id');
                User::whereIn('id', $collaboratorIds)->get()->each(function (User $collaborator) {
                    $collaborator->notifications()->delete();
                    $collaborator->delete();
                });
            }

            $user->notifications()->delete();
            $user->delete();
        });

        return redirect()->route('login')->with('success', 'Your account and its data have been permanently deleted.');
    }
}
