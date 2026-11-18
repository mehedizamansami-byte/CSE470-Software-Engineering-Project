@extends('layouts.guest')
@section('title', 'Forgot password')

@section('content')
<div class="card">
    <h1>Forgot your password?</h1>
    <p class="sub">Enter your email. We will send you a 6-digit code to reset your password.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        <button class="btn block" type="submit">Send reset code</button>
    </form>

    <p class="foot"><a href="{{ route('login') }}">Back to log in</a></p>
</div>
@endsection
