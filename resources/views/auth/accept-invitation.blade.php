@extends('layouts.guest')
@section('title', 'Join the team')

@section('content')
<div class="card">
    <h1>Join {{ $invite->seller->shop?->name ?? $invite->seller->name }}</h1>
    <p class="sub">{{ $invite->seller->name }} invited <strong>{{ $invite->email }}</strong> to work on their BrandBondhu workspace as a collaborator.</p>

    <div class="alert alert-info">
        You will have access to: <strong>{{ $invite->permissionLabels() }}</strong>
    </div>

    <form method="POST" action="{{ route('invitations.accept', request()->route('token')) }}">
        @csrf
        <label for="name">Your name</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">

        <label for="password">Choose a password</label>
        <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">

        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

        <button class="btn block" type="submit">Accept invitation</button>
    </form>
</div>
@endsection
