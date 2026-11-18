@extends('layouts.app')
@section('title', 'Account settings')

@section('content')
<div class="page-head">
    <div>
        <h1>Account settings</h1>
        <p class="sub">Manage your login details, password and account.</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2>Profile details</h2>
        <form method="POST" action="{{ route('account.update') }}">
            @csrf @method('PUT')
            <div class="field">
                <label for="name">Full name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                <p class="help">If you change your email you must verify the new one with an OTP.</p>
            </div>
            <div class="field">
                <label for="phone">Mobile number</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="01712345678">
            </div>
            <div class="actions"><button class="btn" type="submit">Save details</button></div>
        </form>
    </div>

    <div class="card">
        <h2>Change password</h2>
        <form method="POST" action="{{ route('account.password') }}">
            @csrf @method('PUT')
            <div class="field">
                <label for="current_password">Current password</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="field">
                <label for="password">New password</label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <div class="actions"><button class="btn" type="submit">Change password</button></div>
        </form>
    </div>
</div>

<div class="card mt">
    <h2>Account information</h2>
    <div class="grid grid-3">
        <div class="stat"><span class="label">Role</span><span><span class="badge">{{ ucfirst($user->role) }}</span></span></div>
        <div class="stat"><span class="label">Verified</span><span>{{ $user->email_verified_at?->format('d M Y, h:i A') ?? 'Not verified' }}</span></div>
        <div class="stat"><span class="label">Last login</span><span>{{ $user->last_login_at?->diffForHumans() ?? 'This session' }}</span></div>
    </div>
    <hr>
    <h3>API access (JWT)</h3>
    <p class="muted small">Mobile apps and tools can log in through the API and get a JSON Web Token:
        <code>POST {{ url('/api/login') }}</code> with your email and password, then call
        <code>GET {{ url('/api/me') }}</code> with the header <code>Authorization: Bearer &lt;token&gt;</code>.</p>
</div>

<div class="card mt" style="border-color:#fecdca">
    <h2 style="color:var(--err)">Danger zone</h2>
    <div class="grid grid-2">
        <form method="POST" action="{{ route('account.deactivate') }}" data-confirm="Deactivate your account? You can come back by logging in again.">
            @csrf
            <h3>Deactivate account</h3>
            <p class="muted small">Your account is paused and you are logged out. Log in again any time to reactivate it.</p>
            <label for="deactivate_password">Your password</label>
            <input id="deactivate_password" type="password" name="deactivate_password" required autocomplete="current-password">
            <div class="actions"><button class="btn btn-secondary" type="submit">Deactivate</button></div>
        </form>

        <form method="POST" action="{{ route('account.destroy') }}" data-confirm="Permanently delete your account and all its data? This cannot be undone.">
            @csrf @method('DELETE')
            <h3>Delete account</h3>
            <p class="muted small">Deletes your account, shop, brand kits, posts and payments forever. @if ($user->hasRole('seller')) Your collaborators' accounts are deleted too. @endif</p>
            <label for="delete_password">Your password</label>
            <input id="delete_password" type="password" name="delete_password" required autocomplete="current-password">
            <label for="confirm_text">Type DELETE to confirm</label>
            <input id="confirm_text" type="text" name="confirm_text" required placeholder="DELETE">
            <div class="actions"><button class="btn btn-danger" type="submit">Delete my account</button></div>
        </form>
    </div>
</div>
@endsection
