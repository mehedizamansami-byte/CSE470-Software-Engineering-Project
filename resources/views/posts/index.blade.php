@extends('layouts.app')
@section('title', 'Content manager')

@section('content')
<div class="page-head">
    <div>
        <h1>Content manager</h1>
        <p class="sub">Plan your Facebook and Instagram posts, write captions with AI and get a reminder before it is time to post.</p>
    </div>
    <a class="btn" href="{{ route('posts.create') }}"><x-icon name="plus"/> New post</a>
</div>

<div class="card">
    <div class="tabs">
        @foreach (['all' => 'All', 'scheduled' => 'Scheduled', 'draft' => 'Drafts', 'published' => 'Published'] as $key => $label)
            <a class="tab {{ $tab === $key ? 'active' : '' }}" href="{{ route('posts.index', $key === 'all' ? [] : ['tab' => $key]) }}">{{ $label }} <span class="n">{{ $counts[$key] }}</span></a>
        @endforeach
    </div>

    @if ($posts->isEmpty())
        <div class="empty">
            <h3>No posts here yet</h3>
            <p>Create a post and let the AI write the caption for you.</p>
            <a class="btn" href="{{ route('posts.create') }}">Create a post</a>
        </div>
    @else
        <ul class="list">
            @foreach ($posts as $post)
                <li>
                    <div class="post-row">
                        @if ($post->imageUrl())
                            <img class="post-thumb" src="{{ $post->imageUrl() }}" alt="">
                        @else
                            <span class="post-thumb"><x-icon name="calendar"/></span>
                        @endif
                        <div>
                            <strong>{{ $post->title }}</strong>
                            <span class="badge badge-{{ $post->statusColor() }}">{{ ucfirst($post->status) }}</span>
                            <div class="small muted">
                                {{ $post->platformLabel() }}
                                @if ($post->scheduled_at) · {{ $post->scheduled_at->format('D, d M Y · h:i A') }} @endif
                                @if ($post->status === 'scheduled' && $post->remind_before)
                                    · ⏰ {{ $post->reminder_sent_at ? 'reminder sent' : \App\Models\Post::REMINDERS[$post->remind_before] ?? $post->remind_before.' min before' }}
                                @endif
                                @if ($post->creator) · by {{ $post->creator->name }} @endif
                            </div>
                            <div class="small" style="margin-top:4px">{{ \Illuminate\Support\Str::limit($post->caption, 140) }}</div>
                        </div>
                    </div>
                    <div class="row" style="flex-wrap:nowrap">
                        @if ($post->status !== 'published')
                            <form method="POST" action="{{ route('posts.publish', $post) }}">
                                @csrf
                                <button class="btn btn-secondary btn-sm" type="submit" title="Mark as posted on Facebook/Instagram">Mark published</button>
                            </form>
                        @endif
                        <a class="btn btn-secondary btn-sm" href="{{ route('posts.edit', $post) }}">Edit</a>
                        <form method="POST" action="{{ route('posts.destroy', $post) }}" data-confirm="Delete this post?">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm" type="submit">Delete</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt">{{ $posts->links('partials.pagination') }}</div>
    @endif
</div>
@endsection
