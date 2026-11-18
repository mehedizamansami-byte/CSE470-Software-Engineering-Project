<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * CONTROLLER layer: login and logout (Feature 1).
 */
class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && ! $user->is_active && Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'This account has been suspended by an administrator.'])
                ->onlyInput('email');
        }

        // Only active accounts may sign in.
        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        // Logging in again reactivates a deactivated account.
        $user = $request->user();
        $wasDeactivated = $user->deactivated_at !== null;
        $user->forceFill(['last_login_at' => now(), 'deactivated_at' => null])->save();

        $redirect = redirect()->intended(route('dashboard'));

        return $wasDeactivated ? $redirect->with('success', 'Welcome back! Your account has been reactivated.') : $redirect;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
