@extends('layouts.guest')
@section('title', 'Verify your account')

@section('content')
<div class="card">
    <h1>Check your email</h1>
    <p class="sub">Enter the 6-digit code we sent to <strong>{{ $email }}</strong>. The code expires in {{ config('brandbondhu.otp.expires_minutes') }} minutes.</p>

    <form method="POST" action="{{ route('otp.verify') }}">
        @csrf
        <label for="code">Verification code</label>
        <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required autofocus autocomplete="one-time-code" style="font-size:22px;letter-spacing:8px;text-align:center">
        <button class="btn block" type="submit">Verify account</button>
    </form>

    <div class="row between" style="margin-top:16px">
        <form method="POST" action="{{ route('otp.resend') }}">
            @csrf
            <button class="link-btn" type="submit">Send a new code</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="link-btn" type="submit">Log out</button>
        </form>
    </div>
</div>
@endsection
