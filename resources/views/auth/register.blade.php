@extends('layouts.app')
@section('title', 'Register | BrandBondhu')

@section('content')
<div class="auth-wrap">
    <div class="card">
        <h1>Create your account</h1>
        <p class="sub">Build your brand without an agency.</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <label for="name">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
            @error('name') <div class="error">{{ $message }}</div> @enderror

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label for="role">I am a</label>
            <select id="role" name="role" required>
                <option value="seller" @selected(old('role') === 'seller')>Seller</option>
                <option value="designer" @selected(old('role') === 'designer')>Designer</option>
            </select>
            @error('role') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>

            <button class="btn" type="submit">Register</button>
        </form>

        <p class="foot">Already registered? <a href="{{ route('login') }}">Log in</a></p>
    </div>
</div>
@endsection
