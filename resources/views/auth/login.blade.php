@extends('layouts.app')
@section('title', 'Login | BrandBondhu')

@section('content')
<div class="auth-wrap">
    <div class="card">
        <h1>Welcome back</h1>
        <p class="sub">Log in to your BrandBondhu account.</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            @error('email')
                <div class="alert">{{ $message }}</div>
            @enderror

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <div class="check">
                <input id="remember" type="checkbox" name="remember" value="1">
                <label for="remember" style="margin:0;font-weight:400">Remember me</label>
            </div>

            <button class="btn" type="submit">Log in</button>
        </form>

        <p class="foot">New here? <a href="{{ route('register') }}">Create an account</a></p>
    </div>
</div>
@endsection
