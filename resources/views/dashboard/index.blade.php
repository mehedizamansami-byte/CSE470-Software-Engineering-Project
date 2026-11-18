@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="page-head">
    <div>
        <h1>Hello, {{ $user->name }} 👋</h1>
        <p class="sub">You are logged in as <span class="badge">{{ ucfirst($user->role) }}</span>
            @isset($owner)
                @if ($owner->id !== $user->id) working in <strong>{{ $owner->shop?->name ?? $owner->name }}</strong>'s workspace @endif
            @endisset
        </p>
    </div>
    @can('brand_kit')
        <a class="btn" href="{{ route('brand-kits.create') }}"><x-icon name="sparkles"/> New AI brand kit</a>
    @endcan
</div>

@isset($owner)
    {{-- Seller and collaborator workspace --}}
    <div class="grid grid-4">
        <div class="card stat">
            <span class="label">AI credits</span>
            <span class="value">{{ $owner->ai_credits }}</span>
            <span class="hint">{{ $subscription->plan->name }} plan @if ($subscription->ends_at) · renews {{ $subscription->ends_at->format('d M') }} @endif</span>
        </div>
        <div class="card stat">
            <span class="label">Brand kits</span>
            <span class="value">{{ $stats['kits'] }}</span>
            <span class="hint">{{ $activeKit ? 'Active: '.$activeKit->name : 'No active kit yet' }}</span>
        </div>
        <div class="card stat">
            <span class="label">Scheduled posts</span>
            <span class="value">{{ $stats['scheduled'] }}</span>
            <span class="hint">{{ $stats['drafts'] }} draft(s)</span>
        </div>
        <div class="card stat">
            <span class="label">Team members</span>
            <span class="value">{{ $stats['team'] }}</span>
            <span class="hint">Collaborators with access</span>
        </div>
    </div>

    @if ($owner->ai_credits < config('brandbondhu.low_credit_threshold'))
        <div class="alert alert-warn mt">
            ⚠️ Your workspace has only <strong>{{ $owner->ai_credits }}</strong> AI credits left.
            @can('billing') <a href="{{ route('billing.index') }}">Upgrade or buy credits</a>. @else Ask your shop owner to top up. @endcan
        </div>
    @endif

    <div class="grid grid-main mt">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <h2>Active brand kit</h2>
                    @can('brand_kit') <a href="{{ route('brand-kits.index') }}">All kits</a> @endcan
                </div>
                @if ($activeKit)
                    <div class="kit-hero">
                        <x-brand-logo :kit="$activeKit" :size="84"/>
                        <div style="flex:1;min-width:200px">
                            <div class="name" style="font-size:22px">{{ $activeKit->business_name }}</div>
                            <div class="tagline">{{ $activeKit->tagline }}</div>
                            <div class="swatches mt" style="max-width:320px">
                                @foreach (\App\Models\BrandKit::COLOR_ROLES as $role)
                                    <span style="background: {{ $activeKit->color($role) }}" title="{{ $role }}"></span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        @can('brand_kit') <a class="btn btn-secondary btn-sm" href="{{ route('brand-kits.show', $activeKit) }}">Open kit</a> @endcan
                        @can('style_guide')
                            @if ($activeKit->styleGuide)
                                <a class="btn btn-secondary btn-sm" href="{{ route('style-guides.show', $activeKit) }}">Style guide</a>
                            @else
                                <a class="btn btn-secondary btn-sm" href="{{ route('style-guides.index') }}">Create style guide</a>
                            @endif
                        @endcan
                    </div>
                @else
                    <div class="empty">
                        <h3>No brand kit yet</h3>
                        <p>Let the AI design your logo, colours, fonts and brand voice in seconds.</p>
                        @can('brand_kit') <a class="btn" href="{{ route('brand-kits.create') }}">Generate a brand kit</a> @endcan
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-head">
                    <h2>Upcoming posts</h2>
                    @can('content') <a href="{{ route('posts.create') }}">+ New post</a> @endcan
                </div>
                @forelse ($upcoming as $post)
                    <ul class="list">
                        <li>
                            <div>
                                <strong>{{ $post->title }}</strong>
                                <div class="small muted">{{ $post->platformLabel() }} · {{ $post->scheduled_at->format('D, d M · h:i A') }}</div>
                            </div>
                            <span class="badge badge-blue">{{ $post->scheduled_at->diffForHumans() }}</span>
                        </li>
                    </ul>
                @empty
                    <p class="muted">Nothing scheduled. Plan your next post in the content manager.</p>
                @endforelse
            </div>
        </div>

        <div class="stack">
            @if ($user->id === $owner->id)
                <div class="card">
                    <h2>Getting started</h2>
                    <ul class="list">
                        <li><span>{{ $shop ? '✅' : '⬜' }} Set up your shop profile</span> <a href="{{ route('shop.edit') }}">Open</a></li>
                        <li><span>{{ $stats['kits'] ? '✅' : '⬜' }} Generate an AI brand kit</span> <a href="{{ route('brand-kits.create') }}">Open</a></li>
                        <li><span>{{ $activeKit?->styleGuide ? '✅' : '⬜' }} Create a style guide</span> <a href="{{ route('style-guides.index') }}">Open</a></li>
                        <li><span>{{ $stats['scheduled'] ? '✅' : '⬜' }} Schedule a post</span> <a href="{{ route('posts.create') }}">Open</a></li>
                        <li><span>{{ $stats['team'] ? '✅' : '⬜' }} Invite a team member</span> <a href="{{ route('team.index') }}">Open</a></li>
                    </ul>
                </div>
            @else
                <div class="card">
                    <h2>Your access</h2>
                    <p class="muted">Your seller gave you access to:</p>
                    <div class="chips">
                        @forelse ($user->permissionList() as $permission)
                            <span class="chip">{{ config("brandbondhu.workspace_permissions.$permission", $permission) }}</span>
                        @empty
                            <span class="muted">No features yet. Ask your seller for access.</span>
                        @endforelse
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-head">
                    <h2>Recent activity</h2>
                    @can('team') <a href="{{ route('team.audit') }}">Audit log</a> @endcan
                </div>
                <ul class="list">
                    @forelse ($activity as $log)
                        <li><span><strong>{{ $log->actor_name }}</strong> <span class="muted">{{ $log->description }}</span></span><span class="small muted nowrap">{{ $log->created_at->diffForHumans(short: true) }}</span></li>
                    @empty
                        <li class="muted">No activity yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@elseif ($user->hasRole('collaborator'))
    <div class="card">
        <div class="empty">
            <h3>You are not part of a team right now</h3>
            <p>Your seller removed your access, or the invitation is not complete. Ask your seller for a new invitation.</p>
        </div>
    </div>
@elseif ($user->hasRole('designer'))
    <div class="grid grid-3">
        <div class="card stat">
            <span class="label">Portfolio complete</span>
            <span class="value">{{ $profile ? $profile->completeness() : 0 }}%</span>
            <div class="meter mt"><span style="width: {{ $profile ? $profile->completeness() : 0 }}%"></span></div>
        </div>
        <div class="card stat">
            <span class="label">Portfolio items</span>
            <span class="value">{{ $profile?->items_count ?? 0 }}</span>
            <span class="hint">Show at least 3 of your best works</span>
        </div>
        <div class="card stat">
            <span class="label">Availability</span>
            <span class="value" style="font-size:18px">{{ $profile && $profile->is_available ? 'Open for work' : 'Not available' }}</span>
            <span class="hint">Sellers see this on your profile</span>
        </div>
    </div>
    <div class="card mt">
        <h2>Your designer workspace</h2>
        <p class="muted">Keep your portfolio fresh. The marketplace (job posts, chat and reviews) arrives in a later sprint.</p>
        <div class="actions">
            <a class="btn" href="{{ route('portfolio.edit') }}">Edit portfolio</a>
            @if ($profile) <a class="btn btn-secondary" href="{{ route('designers.show', $user) }}">View public profile</a> @endif
        </div>
    </div>
@else
    {{-- Admin --}}
    <div class="grid grid-4">
        @foreach (['seller', 'collaborator', 'designer', 'admin'] as $role)
            <div class="card stat">
                <span class="label">{{ ucfirst($role) }}s</span>
                <span class="value">{{ $roleCounts[$role] ?? 0 }}</span>
            </div>
        @endforeach
    </div>
    <div class="grid grid-2 mt">
        <div class="card stat">
            <span class="label">Simulated revenue</span>
            <span class="value">৳{{ number_format($revenue) }}</span>
            <span class="hint">{{ $paidCount }} paid transaction(s)</span>
        </div>
        <div class="card">
            <h2>Newest users</h2>
            <ul class="list">
                @foreach ($latestUsers as $u)
                    <li><span>{{ $u->name }} <span class="muted small">{{ $u->email }}</span></span><span class="badge">{{ ucfirst($u->role) }}</span></li>
                @endforeach
            </ul>
            <div class="actions"><a class="btn btn-secondary btn-sm" href="{{ route('admin.users') }}">Manage users</a> <a class="btn btn-secondary btn-sm" href="{{ route('admin.roles') }}">Roles &amp; permissions</a></div>
        </div>
    </div>
@endisset

<div class="card mt">
    <div class="card-head">
        <h2>Latest notifications</h2>
        <a href="{{ route('notifications.index') }}">See all</a>
    </div>
    <ul class="list">
        @forelse ($notifications as $n)
            <li>
                <span><strong>{{ $n->data['title'] ?? 'Notification' }}</strong> <span class="muted">{{ $n->data['message'] ?? '' }}</span></span>
                <span class="small muted nowrap">{{ $n->created_at->diffForHumans(short: true) }}</span>
            </li>
        @empty
            <li class="muted">You have no notifications.</li>
        @endforelse
    </ul>
</div>
@endsection
