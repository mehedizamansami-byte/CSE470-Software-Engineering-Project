@extends('layouts.app')
@section('title', 'Dashboard | BrandBondhu')

@section('content')
<div class="container">
    <div class="card wide">
        <h1>Hello, {{ $user->name }}</h1>
        <p class="sub">You are logged in as <span class="badge">{{ $user->role }}</span></p>

        @if ($user->hasRole('seller'))
            <p>Seller workspace: brand kits, content, products and orders will appear here.</p>
        @elseif ($user->hasRole('collaborator'))
            <p>Collaborator workspace: you can work within the permissions your seller grants.</p>
        @elseif ($user->hasRole('designer'))
            <p>Designer workspace: portfolio and marketplace jobs will appear here.</p>
        @else
            <p>Admin workspace: <a href="{{ route('admin.users') }}">manage users</a>.</p>
        @endif
    </div>
</div>
@endsection
