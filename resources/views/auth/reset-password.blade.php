@extends('layouts.guest')
@section('title', 'Reset password')

@section('content')
<div class="card">
    <h1>Set a new password</h1>
    <p class="sub">Enter the code from your email and choose a new password.</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">

        <label for="code">Reset code</label>
        <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required autocomplete="one-time-code">

        <label for="password">New password</label>
        <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">

        <label for="password_confirmation">Confirm new password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

        <button class="btn block" type="submit">Reset password</button>
    </form>

    <p class="foot">No code? <a href="{{ route('password.request') }}">Send it again</a></p>
</div>
@endsection
