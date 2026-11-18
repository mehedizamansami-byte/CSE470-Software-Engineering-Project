@extends('layouts.guest')
@section('title', 'Log in')

@section('content')
<div class="card">
    <h1>Welcome back</h1>
    <p class="sub">Log in to your BrandBondhu account.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">

        <label for="password">Password</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">

        <div class="row between" style="margin-top:12px">
            <label class="check" style="margin:0"><input type="checkbox" name="remember" value="1"> Remember me</label>
            <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
        </div>

        <button class="btn block" type="submit">Log in</button>
    </form>

    <p class="foot">New here? <a href="{{ route('register') }}">Create an account</a></p>
</div>

@if (config('brandbondhu.show_demo_codes'))
    <div class="demo-box">
        <strong>Demo accounts</strong> (password <code>password123</code>, created by <code>php artisan migrate --seed</code>)
        <table>
            <tr><td>Seller</td><td>seller@brandbondhu.test</td></tr>
            <tr><td>Collaborator</td><td>collaborator@brandbondhu.test</td></tr>
            <tr><td>Designer</td><td>designer@brandbondhu.test</td></tr>
            <tr><td>Admin</td><td>admin@brandbondhu.test</td></tr>
        </table>
    </div>
@endif
@endsection
