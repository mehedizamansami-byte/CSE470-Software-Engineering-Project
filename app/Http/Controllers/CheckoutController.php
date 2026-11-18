<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTROLLER layer: simulated bKash / Nagad / card checkout with OTP and PIN (Feature 6).
 */
class CheckoutController extends Controller
{
    public function show(Request $request, Payment $payment): View|RedirectResponse
    {
        $this->ownPayment($request, $payment);

        return match ($payment->status) {
            Payment::STATUS_PAID => redirect()->route('invoices.show', $payment),
            Payment::STATUS_AWAITING_OTP => redirect()->route('checkout.verify', $payment),
            Payment::STATUS_CANCELLED => redirect()->route('billing.index')->with('error', 'That payment was cancelled.'),
            default => view('billing.checkout', ['payment' => $payment]),
        };
    }

    public function start(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        $this->ownPayment($request, $payment);
        abort_unless(in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_FAILED], true), 409, 'This payment can no longer be changed.');

        $method = $request->validate([
            'method' => ['required', Rule::in(array_keys(config('brandbondhu.payment_methods')))],
        ])['method'];

        if ($method === 'card') {
            $data = $request->validate([
                'card_name' => ['required', 'string', 'max:80'],
                'card_number' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                    $digits = preg_replace('/\D/', '', (string) $value);
                    if (strlen($digits) < 12 || strlen($digits) > 19 || ! PaymentService::luhn($digits)) {
                        $fail('Enter a valid card number.');
                    }
                }],
                'card_expiry' => ['bail', 'required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/', function (string $attribute, mixed $value, \Closure $fail) {
                    [$month, $year] = explode('/', $value);
                    if (Carbon::create(2000 + (int) $year, (int) $month, 1)->endOfMonth()->isPast()) {
                        $fail('This card has expired.');
                    }
                }],
                'card_cvv' => ['required', 'digits_between:3,4'],
            ], [
                'card_expiry.regex' => 'Use the MM/YY format, for example 08/29.',
            ]);
        } else {
            $data = $request->validate([
                'wallet' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            ], [
                'wallet.regex' => 'Enter your 11-digit wallet number, for example 01712345678.',
            ]);
        }

        // Only the masked number is stored. The CVV is never saved.
        $code = $payments->start($payment, $method, $data);

        return redirect()->route('checkout.verify', $payment)->with('demo_code', $this->demo($code));
    }

    public function verifyForm(Request $request, Payment $payment): View|RedirectResponse
    {
        $this->ownPayment($request, $payment);

        if ($payment->status !== Payment::STATUS_AWAITING_OTP) {
            return redirect()->route('checkout.show', $payment);
        }

        return view('billing.verify', [
            'payment' => $payment,
            'pinLength' => config("brandbondhu.payment_methods.{$payment->method}.pin_length"),
        ]);
    }

    public function verify(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        $this->ownPayment($request, $payment);

        if ($payment->status !== Payment::STATUS_AWAITING_OTP) {
            return redirect()->route('checkout.show', $payment);
        }

        $pinLength = config("brandbondhu.payment_methods.{$payment->method}.pin_length");
        $rules = ['otp' => ['required', 'digits:6']];

        if ($pinLength) {
            $rules['pin'] = ['required', "digits:{$pinLength}"];
        }

        $data = $request->validate($rules);
        $error = $payments->verify($payment, $data['otp'], $data['pin'] ?? null);

        if ($error) {
            return $payment->status === Payment::STATUS_FAILED
                ? redirect()->route('checkout.show', $payment)->with('error', $error)
                : back()->withErrors(['otp' => $error]);
        }

        return redirect()->route('invoices.show', $payment)
            ->with('success', 'Payment successful! Transaction ID: '.$payment->transaction_id);
    }

    public function cancel(Request $request, Payment $payment): RedirectResponse
    {
        $this->ownPayment($request, $payment);

        if ($payment->isPaid()) {
            return back()->with('error', 'A paid payment cannot be cancelled.');
        }

        $payment->update(['status' => Payment::STATUS_CANCELLED, 'otp_code' => null]);

        return redirect()->route('billing.index')->with('success', 'Payment cancelled. No money was taken.');
    }

    private function ownPayment(Request $request, Payment $payment): void
    {
        abort_unless((int) $payment->user_id === $request->user()->id, 404);
    }
}
