@extends('layouts.app')
@section('title', 'Confirm payment')

@section('content')
<div class="grid grid-main">
    <div class="card">
        <h1>Confirm your {{ $payment->methodLabel() }} payment</h1>
        <p class="sub">We sent a 6-digit verification code (OTP) to <strong>{{ $payment->account }}</strong>. It expires in 5 minutes.</p>

        <form method="POST" action="{{ route('checkout.confirm', $payment) }}">
            @csrf
            <label for="otp">Verification code (OTP)</label>
            <input id="otp" type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required autofocus autocomplete="one-time-code" style="font-size:22px;letter-spacing:8px;max-width:260px">

            @if ($pinLength)
                <label for="pin">{{ $payment->methodLabel() }} PIN ({{ $pinLength }} digits)</label>
                <input id="pin" type="password" name="pin" inputmode="numeric" pattern="{{ '[0-9]{'.$pinLength.'}' }}" maxlength="{{ $pinLength }}" required style="font-size:22px;letter-spacing:8px;max-width:260px">
                <p class="help">Demo: any {{ $pinLength }}-digit PIN is accepted. Never share your real PIN with anyone.</p>
            @endif

            <div class="actions">
                <button class="btn {{ $payment->method === 'bkash' ? 'btn-bkash' : ($payment->method === 'nagad' ? 'btn-nagad' : '') }}" type="submit">Confirm and pay ৳{{ number_format($payment->amount) }}</button>
            </div>
        </form>
        <form method="POST" action="{{ route('checkout.cancel', $payment) }}" class="mt">
            @csrf
            <button class="link-btn" type="submit">Cancel this payment</button>
        </form>
    </div>

    <div class="card">
        <h2>Order summary</h2>
        <div class="summary-row"><span>Invoice</span><span>{{ $payment->invoice_no }}</span></div>
        <div class="summary-row"><span>Item</span><span>{{ $payment->description }}</span></div>
        <div class="summary-row"><span>Paying with</span><span>{{ $payment->methodLabel() }}</span></div>
        <div class="summary-row total"><span>Total</span><span>৳{{ number_format($payment->amount) }}</span></div>
        <p class="help mt">Wrong OTP three times cancels the payment for your safety.</p>
    </div>
</div>
@endsection
