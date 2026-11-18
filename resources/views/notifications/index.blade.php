@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<div class="page-head">
    <div>
        <h1>Notification center</h1>
        <p class="sub">The bell checks for new notifications every 20 seconds. You have <strong>{{ $unreadCount }}</strong> unread.</p>
    </div>
    @if ($unreadCount)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn btn-secondary" type="submit"><x-icon name="check"/> Mark all as read</button>
        </form>
    @endif
</div>

<div class="card">
    <div class="tabs">
        <a class="tab {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('notifications.index') }}">All</a>
        <a class="tab {{ $filter === 'unread' ? 'active' : '' }}" href="{{ route('notifications.index', ['filter' => 'unread']) }}">Unread <span class="n">{{ $unreadCount }}</span></a>
    </div>

    @php($icons = ['success' => '✅', 'warning' => '⚠️', 'reminder' => '⏰', 'info' => 'ℹ️'])
    <ul class="list">
        @forelse ($notifications as $n)
            <li style="{{ $n->read_at ? '' : 'background:#f8f8ff;margin:0 -22px;padding:12px 22px' }}">
                <div class="row" style="align-items:flex-start;flex-wrap:nowrap">
                    <span style="font-size:18px">{{ $icons[$n->data['level'] ?? 'info'] ?? 'ℹ️' }}</span>
                    <div>
                        <strong>{{ $n->data['title'] ?? 'Notification' }}</strong>
                        @unless ($n->read_at) <span class="badge badge-blue">New</span> @endunless
                        <div class="muted">{{ $n->data['message'] ?? '' }}</div>
                        <div class="small muted">{{ $n->created_at->format('d M Y, h:i A') }} · {{ $n->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                <div class="row" style="flex-wrap:nowrap">
                    @if (! empty($n->data['url']))
                        <a class="btn btn-secondary btn-sm" href="{{ route('notifications.open', $n->id) }}">Open</a>
                    @endif
                    @unless ($n->read_at)
                        <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                            @csrf
                            <button class="btn btn-ghost btn-sm" type="submit">Mark read</button>
                        </form>
                    @endunless
                </div>
            </li>
        @empty
            <li class="muted">{{ $filter === 'unread' ? 'No unread notifications. 🎉' : 'No notifications yet.' }}</li>
        @endforelse
    </ul>
    <div class="mt">{{ $notifications->links('partials.pagination') }}</div>
</div>
@endsection
