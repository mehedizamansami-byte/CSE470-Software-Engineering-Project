@extends('layouts.app')
@section('title', $designer->name.' · Designer')

@section('content')
<div class="card">
    <div class="row" style="gap:18px;align-items:flex-start">
        <span class="avatar lg">
            @if ($profile->avatarUrl()) <img src="{{ $profile->avatarUrl() }}" alt="{{ $designer->name }}"> @else {{ $designer->initials() }} @endif
        </span>
        <div style="flex:1;min-width:220px">
            <h1>{{ $designer->name }}</h1>
            <p class="muted" style="font-size:16px">{{ $profile->headline }}</p>
            <div class="row">
                @if ($profile->is_available) <span class="badge badge-green">Available for work</span> @else <span class="badge badge-gray">Not available</span> @endif
                @if ($profile->location) <span class="chip">📍 {{ $profile->location }}</span> @endif
                @if ($profile->experience_years !== null) <span class="chip">{{ $profile->experience_years }} years experience</span> @endif
                @if ($profile->starting_price) <span class="chip">From ৳{{ number_format($profile->starting_price) }}</span> @endif
            </div>
        </div>
        @if ($profile->portfolio_url)
            <a class="btn btn-secondary" href="{{ $profile->portfolio_url }}" target="_blank" rel="noopener">External portfolio</a>
        @endif
    </div>
    @if ($profile->bio)
        <hr>
        <p>{{ $profile->bio }}</p>
    @endif
    @if (! empty($profile->skills))
        <div class="chips mt">
            @foreach ($profile->skills as $skill) <span class="chip">{{ $skill }}</span> @endforeach
        </div>
    @endif
</div>

<div class="card mt">
    <h2>Portfolio</h2>
    @if ($items->isEmpty())
        <p class="muted">This designer has not added work yet.</p>
    @else
        <div class="portfolio-grid">
            @foreach ($items as $item)
                <div class="portfolio-item">
                    <div class="thumb">@if ($item->imageUrl()) <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}"> @else {{ $item->category ?: 'Design' }} @endif</div>
                    <div class="body">
                        <strong>{{ $item->title }}</strong>
                        @if ($item->category) <div class="small muted">{{ $item->category }}</div> @endif
                        <p class="small">{{ $item->description }}</p>
                        @if ($item->link) <a class="small" href="{{ $item->link }}" target="_blank" rel="noopener">Open link</a> @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
