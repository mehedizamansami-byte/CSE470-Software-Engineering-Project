<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

/**
 * SERVICE layer: sends simple text emails (OTP codes, invitations).
 * With MAIL_MAILER=log the email is written to storage/logs/laravel.log.
 */
class EmailService
{
    public function send(string $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, fn ($message) => $message->to($to)->subject($subject));
        } catch (\Throwable $e) {
            // A broken mail setup must not block registration or payments in the demo.
            report($e);
        }
    }
}
