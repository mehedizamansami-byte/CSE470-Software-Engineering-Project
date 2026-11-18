<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CONTROLLER layer: forgot and reset password with a 6-digit code (Feature 1).
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $code = $otp->issuePasswordReset($data['email']);

        // The same message is shown either way, so nobody can test which emails have accounts.
        return redirect()->route('password.reset', ['email' => $data['email']])
            ->with('success', 'If an account exists for that email, we sent it a 6-digit reset code.')
            ->with('demo_code', $this->demo($code));
    }

    public function edit(Request $request): View
    {
        return view('auth.reset-password', ['email' => $request->query('email', old('email'))]);
    }

    public function update(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $otp->checkPasswordReset($data['email'], $data['code'])) {
            return back()->withErrors(['code' => 'The code is wrong or has expired.'])->onlyInput('email');
        }

        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $otp->clearPasswordReset($data['email']);

        $user->notify(new AppNotification(
            'Your password was changed',
            'Your password was reset with a one-time code. If this was not you, contact support right away.',
            null,
            'warning',
        ));

        return redirect()->route('login')->with('success', 'Password updated. You can log in with your new password.');
    }
}
