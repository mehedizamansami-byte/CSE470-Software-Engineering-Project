<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SERVICE layer: simulated bKash, Nagad and card checkout (Feature 6).
 * No real money moves. The OTP is shown on screen in demo mode and the PIN is only checked for length.
 */
class PaymentService
{
    public const MAX_OTP_ATTEMPTS = 3;

    public function __construct(
        private SubscriptionService $subscriptions,
        private CreditService $credits,
        private OtpService $otp,
    ) {}

    public function createForPlan(User $user, Plan $plan): Payment
    {
        return $this->create($user, [
            'purpose' => Payment::PURPOSE_PLAN,
            'plan_id' => $plan->id,
            'description' => "{$plan->name} plan, 1 month ({$plan->monthly_credits} AI credits)",
            'amount' => $plan->price,
        ]);
    }

    public function createForCredits(User $user, string $pack): Payment
    {
        $data = config("brandbondhu.credit_packs.{$pack}");

        return $this->create($user, [
            'purpose' => Payment::PURPOSE_CREDITS,
            'credits' => $data['credits'],
            'description' => "AI credit pack: {$data['credits']} credits",
            'amount' => $data['price'],
        ]);
    }

    private function create(User $user, array $attributes): Payment
    {
        return $user->payments()->create($attributes + [
            'invoice_no' => 'BB-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    /** Step 1: customer picked a method and entered a wallet number or card. Returns the OTP. */
    public function start(Payment $payment, string $method, array $data): string
    {
        $code = $this->otp->generate();

        $payment->update([
            'method' => $method,
            'account' => $method === 'card' ? $this->maskCard($data['card_number']) : $this->maskWallet($data['wallet']),
            'status' => Payment::STATUS_AWAITING_OTP,
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'failure_reason' => null,
        ]);

        // Simulated SMS: the "gateway" message goes to storage/logs/laravel.log.
        Log::info("[Simulated {$payment->methodLabel()} SMS] OTP {$code} for invoice {$payment->invoice_no} ({$payment->account}).");

        return $code;
    }

    /** Step 2: check the OTP (and the wallet PIN). Returns an error message, or null on success. */
    public function verify(Payment $payment, string $code, ?string $pin): ?string
    {
        if ($payment->otp_expires_at?->isPast()) {
            $this->fail($payment, 'The OTP expired before it was entered.');

            return 'The OTP has expired. Please start the payment again.';
        }

        if (! Hash::check($code, (string) $payment->otp_code)) {
            $payment->increment('attempts');

            if ($payment->attempts >= self::MAX_OTP_ATTEMPTS) {
                $this->fail($payment, 'Too many wrong OTP attempts.');

                return 'Too many wrong OTP attempts. The payment was cancelled for your safety.';
            }

            $left = self::MAX_OTP_ATTEMPTS - $payment->attempts;

            return "Wrong OTP. You have {$left} attempt(s) left.";
        }

        $pinLength = config("brandbondhu.payment_methods.{$payment->method}.pin_length");

        if ($pinLength && ! preg_match('/^\d{'.$pinLength.'}$/', (string) $pin)) {
            return "Enter your {$pinLength}-digit {$payment->methodLabel()} PIN.";
        }

        $this->complete($payment);

        return null;
    }

    /** Mark as paid and deliver what was bought. */
    public function complete(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => Payment::STATUS_PAID,
                'paid_at' => now(),
                'transaction_id' => $this->transactionId($payment->method),
                'otp_code' => null,
                'otp_expires_at' => null,
            ]);

            if ($payment->purpose === Payment::PURPOSE_PLAN && $payment->plan) {
                $this->subscriptions->activate($payment->user, $payment->plan);
            } elseif ($payment->purpose === Payment::PURPOSE_CREDITS) {
                $this->credits->add($payment->user, (int) $payment->credits, "Bought {$payment->credits} credits ({$payment->invoice_no})");
            }
        });

        $payment->user->notify(new AppNotification(
            'Payment successful',
            "Paid ৳".number_format($payment->amount)." with {$payment->methodLabel()} for {$payment->description}.",
            route('invoices.show', $payment, false),
            'success',
        ));

        AuditLogger::log($payment->user, 'billing.paid', "Paid ৳{$payment->amount} via {$payment->methodLabel()} ({$payment->invoice_no})", $payment->user);
    }

    public function fail(Payment $payment, string $reason): void
    {
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'failure_reason' => $reason,
            'otp_code' => null,
        ]);

        $payment->user->notify(new AppNotification(
            'Payment failed',
            "Your payment {$payment->invoice_no} failed: {$reason}",
            route('billing.transactions', [], false),
            'warning',
        ));
    }

    public static function luhn(string $digits): bool
    {
        $sum = 0;
        $double = false;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($double) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $double = ! $double;
        }

        return $sum % 10 === 0;
    }

    private function maskWallet(string $number): string
    {
        return substr($number, 0, 3).'****'.substr($number, -4);
    }

    private function maskCard(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number);

        $brand = match (true) {
            str_starts_with($digits, '4') => 'Visa',
            (bool) preg_match('/^(5[1-5]|2[2-7])/', $digits) => 'Mastercard',
            (bool) preg_match('/^3[47]/', $digits) => 'Amex',
            default => 'Card',
        };

        return $brand.' **** '.substr($digits, -4);
    }

    private function transactionId(string $method): string
    {
        $prefix = ['bkash' => 'BK', 'nagad' => 'NG', 'card' => 'CD'][$method] ?? 'TX';

        return $prefix.Str::upper(Str::random(10));
    }
}
