@extends('layouts.app')
@section('title', 'Checkout')

@section('content')
@php($method = old('method', $payment->method ?? 'bkash'))
<div class="page-head">
    <div>
        <h1>Checkout</h1>
        <p class="sub">Invoice {{ $payment->invoice_no }}. This is a <strong>simulated</strong> payment for the CSE470 demo: no real money moves.</p>
    </div>
</div>

@if ($payment->status === 'failed')
    <div class="alert alert-error">The last attempt failed: {{ $payment->failure_reason }} You can try again.</div>
@endif

<div class="grid grid-main">
    <div class="card">
        <h2>Choose how to pay</h2>
        <div class="pay-tabs">
            <button type="button" class="pay-tab {{ $method === 'bkash' ? 'active' : '' }}" data-pay-tab="bkash"><span class="logo-bkash">bKash</span></button>
            <button type="button" class="pay-tab {{ $method === 'nagad' ? 'active' : '' }}" data-pay-tab="nagad"><span class="logo-nagad">Nagad</span></button>
            <button type="button" class="pay-tab {{ $method === 'card' ? 'active' : '' }}" data-pay-tab="card"><span class="logo-card">Card</span></button>
        </div>

        @foreach (['bkash' => 'bKash', 'nagad' => 'Nagad'] as $key => $label)
            <form method="POST" action="{{ route('checkout.start', $payment) }}" class="pay-panel" data-pay-panel="{{ $key }}" @if ($method !== $key) hidden @endif>
                @csrf
                <input type="hidden" name="method" value="{{ $key }}">
                <label for="wallet_{{ $key }}">Your {{ $label }} account number</label>
                <input id="wallet_{{ $key }}" type="tel" name="wallet" value="{{ old('method') === $key ? old('wallet') : '' }}" placeholder="01XXXXXXXXX" maxlength="11" required>
                <p class="help">Next you will enter a 6-digit OTP and your {{ config("brandbondhu.payment_methods.$key.pin_length") }}-digit {{ $label }} PIN.</p>
                <div class="actions">
                    <button class="btn btn-{{ $key }}" type="submit">Pay ৳{{ number_format($payment->amount) }} with {{ $label }}</button>
                </div>
            </form>
        @endforeach

        <form method="POST" action="{{ route('checkout.start', $payment) }}" class="pay-panel" data-pay-panel="card" @if ($method !== 'card') hidden @endif>
            @csrf
            <input type="hidden" name="method" value="card">
            <div class="form-grid">
                <div class="field full">
                    <label for="card_name">Name on card</label>
                    <input id="card_name" type="text" name="card_name" value="{{ old('card_name') }}" required autocomplete="cc-name">
                </div>
                <div class="field full">
                    <label for="card_number">Card number</label>
                    <input id="card_number" type="text" name="card_number" inputmode="numeric" placeholder="4242 4242 4242 4242" required autocomplete="cc-number" data-card-number>
                </div>
                <div class="field">
                    <label for="card_expiry">Expiry (MM/YY)</label>
                    <input id="card_expiry" type="text" name="card_expiry" value="{{ old('card_expiry') }}" placeholder="08/29" maxlength="5" required autocomplete="cc-exp">
                </div>
                <div class="field">
                    <label for="card_cvv">CVV</label>
                    <input id="card_cvv" type="password" name="card_cvv" inputmode="numeric" maxlength="4" required autocomplete="cc-csc">
                </div>
            </div>
            <p class="help">Test card: 4242 4242 4242 4242, any future expiry, any 3-digit CVV. Only the last 4 digits are saved.</p>
            <div class="actions"><button class="btn" type="submit">Pay ৳{{ number_format($payment->amount) }} by card</button></div>
        </form>
    </div>

    <div class="card">
        <h2>Order summary</h2>
        <div class="summary-row"><span>{{ $payment->description }}</span><span>৳{{ number_format($payment->amount) }}</span></div>
        <div class="summary-row"><span>VAT</span><span>Included</span></div>
        <div class="summary-row total"><span>Total</span><span>৳{{ number_format($payment->amount) }}</span></div>
        <form method="POST" action="{{ route('checkout.cancel', $payment) }}" class="mt">
            @csrf
            <button class="btn btn-ghost" type="submit" style="width:100%">Cancel payment</button>
        </form>
    </div>
</div>
@endsection
