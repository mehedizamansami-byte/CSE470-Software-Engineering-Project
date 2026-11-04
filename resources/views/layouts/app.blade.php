<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'BrandBondhu')</title>
    <link rel="stylesheet" href="{{ asset('css/brandbondhu.css') }}">
</head>
<body>
@auth
    <div class="nav">
        <a class="logo" href="{{ route('dashboard') }}">BrandBondhu</a>
        <div>
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.users') }}">Users</a> &nbsp;
            @endif
            <span class="badge">{{ auth()->user()->role }}</span> &nbsp;
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn small" type="submit">Logout</button>
            </form>
        </div>
    </div>
@endauth

@yield('content')
</body>
</html>
