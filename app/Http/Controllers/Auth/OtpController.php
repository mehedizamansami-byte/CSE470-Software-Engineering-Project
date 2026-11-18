<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CONTROLLER layer: OTP account verification (Feature 1).
 */
class OtpController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->isVerified()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-otp', ['email' => $request->user()->email]);
    }

    public function verify(Request $request, OtpService $otp): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $error = $otp->verifyUserCode($request->user(), $request->input('code'));

        if ($error) {
            return back()->withErrors(['code' => $error]);
        }

        return redirect()->route('dashboard')->with('success', 'Your account is verified. Welcome to BrandBondhu!');
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        if ($request->user()->isVerified()) {
            return redirect()->route('dashboard');
        }

        $code = $otp->issueForUser($request->user());

        return back()
            ->with('success', 'A new code has been sent to '.$request->user()->email.'.')
            ->with('demo_code', $this->demo($code));
    }
}
