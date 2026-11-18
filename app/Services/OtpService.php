<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * SERVICE layer: one-time codes for account verification and password reset (Feature 1).
 * Codes are stored hashed, expire after a few minutes and allow a limited number of tries.
 */
class OtpService
{
    public function __construct(private EmailService $email) {}

    public function generate(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function ttl(): int
    {
        return (int) config('brandbondhu.otp.expires_minutes', 10);
    }

    // ----- Account verification -----

    public function issueForUser(User $user): string
    {
        $code = $this->generate();

        $user->forceFill([
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes($this->ttl()),
            'otp_attempts' => 0,
        ])->save();

        $this->email->send(
            $user->email,
            'Your BrandBondhu verification code',
            "Hello {$user->name},\n\nYour BrandBondhu verification code is {$code}. It expires in {$this->ttl()} minutes.\n\nIf you did not create an account, ignore this email.",
        );

        return $code;
    }

    /** Returns an error message, or null when the code is correct. */
    public function verifyUserCode(User $user, string $code): ?string
    {
        if (! $user->otp_code || ! $user->otp_expires_at) {
            return 'There is no active code. Please request a new one.';
        }

        if ($user->otp_attempts >= (int) config('brandbondhu.otp.max_attempts', 5)) {
            return 'Too many wrong attempts. Please request a new code.';
        }

        if ($user->otp_expires_at->isPast()) {
            return 'This code has expired. Please request a new one.';
        }

        if (! Hash::check($code, $user->otp_code)) {
            $user->increment('otp_attempts');

            return 'The code is not correct. Please try again.';
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
        ])->save();

        return null;
    }

    // ----- Password reset -----

    /** Returns the code, or null when no account uses this email. */
    public function issuePasswordReset(string $email): ?string
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return null;
        }

        $code = $this->generate();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'created_at' => now()],
        );

        $this->email->send(
            $email,
            'Reset your BrandBondhu password',
            "Hello {$user->name},\n\nYour password reset code is {$code}. It expires in {$this->ttl()} minutes.\n\nIf you did not ask for this, ignore this email.",
        );

        return $code;
    }

    public function checkPasswordReset(string $email, string $code): bool
    {
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $row || Carbon::parse($row->created_at)->addMinutes($this->ttl())->isPast()) {
            return false;
        }

        return Hash::check($code, $row->token);
    }

    public function clearPasswordReset(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }
}
