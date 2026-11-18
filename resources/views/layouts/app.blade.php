<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="notifications-feed" content="{{ route('notifications.feed') }}">
    <title>@yield('title', 'Dashboard') | BrandBondhu</title>
    <link rel="stylesheet" href="{{ asset('css/brandbondhu.css') }}">
    @stack('head')
    <script src="{{ asset('js/brandbondhu.js') }}" defer></script>
</head>
<body>
@php($me = auth()->user())
<div class="shell">
    <aside class="sidebar">
        <a class="brand-logo" href="{{ route('dashboard') }}"><span class="mark">B</span> BrandBondhu</a>

        <nav class="nav-group">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><x-icon name="home"/> Dashboard</a>
            <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                <x-icon name="bell"/> Notifications <span class="count" data-notif-side-count hidden>0</span>
            </a>
        </nav>

        @if ($me->hasPermission('brand_kit') || $me->hasPermission('style_guide') || $me->hasPermission('content'))
            <nav class="nav-group">
                <div class="nav-label">Branding</div>
                @can('brand_kit')
                    <a class="nav-link {{ request()->routeIs('brand-kits.*') ? 'active' : '' }}" href="{{ route('brand-kits.index') }}"><x-icon name="palette"/> Brand kits</a>
                @endcan
                @can('style_guide')
                    <a class="nav-link {{ request()->routeIs('style-guides.*') ? 'active' : '' }}" href="{{ route('style-guides.index') }}"><x-icon name="book"/> Style guide</a>
                @endcan
                @can('content')
                    <a class="nav-link {{ request()->routeIs('posts.*') ? 'active' : '' }}" href="{{ route('posts.index') }}"><x-icon name="calendar"/> Content manager</a>
                @endcan
            </nav>
        @endif

        <nav class="nav-group">
            <div class="nav-label">Workspace</div>
            @can('shop')
                <a class="nav-link {{ request()->routeIs('shop.*') ? 'active' : '' }}" href="{{ route('shop.edit') }}"><x-icon name="store"/> Shop profile</a>
            @endcan
            @can('portfolio')
                <a class="nav-link {{ request()->routeIs('portfolio.*') ? 'active' : '' }}" href="{{ route('portfolio.edit') }}"><x-icon name="briefcase"/> My portfolio</a>
            @endcan
            @can('team')
                <a class="nav-link {{ request()->routeIs('team.*') ? 'active' : '' }}" href="{{ route('team.index') }}"><x-icon name="users"/> Team &amp; audit log</a>
            @endcan
            @can('billing')
                <a class="nav-link {{ request()->routeIs('billing.index', 'checkout.*') ? 'active' : '' }}" href="{{ route('billing.index') }}"><x-icon name="card"/> Plans &amp; credits</a>
                <a class="nav-link {{ request()->routeIs('billing.transactions', 'invoices.*') ? 'active' : '' }}" href="{{ route('billing.transactions') }}"><x-icon name="receipt"/> Transactions</a>
            @endcan
            <a class="nav-link {{ request()->routeIs('account.*') ? 'active' : '' }}" href="{{ route('account.edit') }}"><x-icon name="user"/> Account</a>
        </nav>

        @if ($me->hasRole('admin'))
            <nav class="nav-group">
                <div class="nav-label">Admin</div>
                <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}"><x-icon name="users"/> Users</a>
                <a class="nav-link {{ request()->routeIs('admin.roles') ? 'active' : '' }}" href="{{ route('admin.roles') }}"><x-icon name="shield"/> Roles &amp; permissions</a>
            </nav>
        @endif

        @if ($creditMeter)
            <div class="sidebar-foot">
                <div class="meter-label"><span>AI credits</span><strong data-sidebar-credits>{{ $creditMeter['credits'] }}</strong></div>
                <div class="meter {{ $creditMeter['low'] ? 'low' : '' }}"><span style="width: {{ $creditMeter['percent'] }}%"></span></div>
                <div class="meter-label" style="margin-top:6px"><span>{{ $creditMeter['plan'] }} plan</span>
                    @can('billing')<a href="{{ route('billing.index') }}" style="color:#a5b4fc">Top up</a>@endcan
                </div>
            </div>
        @endif
    </aside>

    <div class="main">
        <header class="topbar">
            <button type="button" class="icon-btn menu-btn" data-toggle-sidebar aria-label="Open menu"><x-icon name="menu"/></button>
            <div class="title">@yield('title', 'Dashboard')</div>

            <div class="notif" data-notif>
                <button type="button" class="icon-btn" data-notif-toggle aria-label="Notifications">
                    <x-icon name="bell"/><span class="dot" data-notif-count hidden>0</span>
                </button>
                <div class="notif-panel" data-notif-panel hidden>
                    <div class="notif-head">
                        <strong>Notifications</strong>
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button class="link-btn" type="submit">Mark all read</button>
                        </form>
                    </div>
                    <div data-notif-list><p class="muted" style="padding:14px;margin:0">Loading...</p></div>
                    <a class="notif-foot" href="{{ route('notifications.index') }}">View all notifications</a>
                </div>
            </div>

            <a class="user-chip" href="{{ route('account.edit') }}">
                <span class="avatar">{{ $me->initials() }}</span>
                <span><strong>{{ $me->name }}</strong><small>{{ $me->role }}</small></span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost btn-sm" type="submit"><x-icon name="logout"/> Log out</button>
            </form>
        </header>

        <main class="page">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
<div class="toast-wrap" data-toasts></div>
</body>
</html>
