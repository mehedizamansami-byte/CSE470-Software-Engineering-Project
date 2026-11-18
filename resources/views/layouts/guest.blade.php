<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Welcome') | BrandBondhu</title>
    <link rel="stylesheet" href="{{ asset('css/brandbondhu.css') }}">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-box">
        <a class="auth-logo" href="{{ route('login') }}"><span class="mark">B</span> BrandBondhu</a>
        @include('partials.flash')
        @yield('content')
    </div>
</div>
</body>
</html>
