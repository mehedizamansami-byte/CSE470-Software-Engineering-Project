<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\OtpService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTROLLER layer: registration with OTP verification (Feature 1).
 */
class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function register(Request $request, OtpService $otp, SubscriptionService $subscriptions): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Admins and collaborators cannot self-register.
            'role' => ['required', Rule::in([User::ROLE_SELLER, User::ROLE_DESIGNER])],
        ], [
            'phone.regex' => 'Enter an 11-digit Bangladeshi mobile number, for example 01712345678.',
        ]);

        $user = User::create($data);

        if ($user->role === User::ROLE_SELLER) {
            // Every new seller starts on the Free plan with its monthly credits.
            $subscriptions->startFree($user, grantCredits: true);
        }

        $user->notify(new AppNotification(
            'Welcome to BrandBondhu!',
            $user->role === User::ROLE_SELLER
                ? 'Start by setting up your shop profile, then generate your first AI brand kit.'
                : 'Set up your portfolio so sellers can see your best work.',
            $user->role === User::ROLE_SELLER ? route('shop.edit', [], false) : route('portfolio.edit', [], false),
            'success',
        ));

        Auth::login($user);
        $request->session()->regenerate();

        $code = $otp->issueForUser($user);

        return redirect()->route('otp.show')
            ->with('success', 'Account created! We sent a 6-digit code to '.$user->email.'.')
            ->with('demo_code', $this->demo($code));
    }
}
