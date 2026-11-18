@extends('layouts.app')
@section('title', 'Edit post')

@section('content')
<div class="page-head">
    <div>
        <h1>Edit post</h1>
        <p class="sub">
            <span class="badge badge-{{ $post->statusColor() }}">{{ ucfirst($post->status) }}</span>
            @if ($post->reminder_sent_at) Reminder sent {{ $post->reminder_sent_at->diffForHumans() }}. @endif
            Created {{ $post->created_at->format('d M Y') }}@if ($post->creator) by {{ $post->creator->name }}@endif.
        </p>
    </div>
</div>

<form method="POST" action="{{ route('posts.update', $post) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    @include('posts._form')
</form>
@endsection
