@extends('layouts.guest')
@section('title', 'Create account')

@section('content')
<div class="card">
    <h1>Create your account</h1>
    <p class="sub">Build your brand without an agency. We will send a 6-digit code to verify your email.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label for="name">Full name</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">

        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">

        <label for="phone">Mobile number <span class="muted">(optional)</span></label>
        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="01712345678" autocomplete="tel">

        <label for="role">I am a</label>
        <select id="role" name="role" required>
            <option value="seller" @selected(old('role', 'seller') === 'seller')>Seller: I run a shop or Facebook page</option>
            <option value="designer" @selected(old('role') === 'designer')>Designer: I offer design services</option>
        </select>
        <p class="help">Collaborators join through an invitation from their seller.</p>

        <label for="password">Password</label>
        <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
        <p class="help">At least 8 characters.</p>

        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

        <button class="btn block" type="submit">Create account</button>
    </form>

    <p class="foot">Already registered? <a href="{{ route('login') }}">Log in</a></p>
</div>
@endsection
