@extends('layouts.app')
@section('title', $kit->name)

@push('head')
    <link rel="stylesheet" href="{{ $kit->googleFontsUrl() }}">
@endpush

@section('content')
@php
    $regen = function (string $asset, string $label) use ($kit, $assetCost) {
        return '<form class="regen" method="POST" action="'.e(route('brand-kits.regenerate', $kit)).'">'
            .csrf_field()
            .'<input type="hidden" name="asset" value="'.e($asset).'">'
            .'<button class="btn btn-ghost btn-sm" type="submit" title="Uses '.$assetCost.' credit">↻ '.e($label).'</button></form>';
    };
    $heading = $kit->headingFont();
    $body = $kit->bodyFont();
@endphp

<div class="page-head">
    <div>
        <h1>{{ $kit->name }} @if ($kit->is_active) <span class="badge badge-green">Active</span> @endif</h1>
        <p class="sub">{{ $kit->industry }} · {{ ucfirst($kit->personality) }} personality · created {{ $kit->created_at->format('d M Y') }}@if ($kit->creator) by {{ $kit->creator->name }}@endif</p>
    </div>
    <div class="row">
        @unless ($kit->is_active)
            <form method="POST" action="{{ route('brand-kits.activate', $kit) }}">
                @csrf
                <button class="btn" type="submit"><x-icon name="check"/> Set as active kit</button>
            </form>
        @endunless
        @can('style_guide')
            @if ($kit->styleGuide)
                <a class="btn btn-secondary" href="{{ route('style-guides.show', $kit) }}"><x-icon name="book"/> Style guide</a>
            @else
                <form method="POST" action="{{ route('style-guides.generate', $kit) }}">
                    @csrf
                    <button class="btn btn-secondary" type="submit"><x-icon name="book"/> Create style guide</button>
                </form>
            @endif
        @endcan
        <a class="btn btn-secondary" href="{{ route('brand-kits.edit', $kit) }}">Edit</a>
        <form method="POST" action="{{ route('brand-kits.destroy', $kit) }}" data-confirm="Delete this brand kit and its style guide?">
            @csrf @method('DELETE')
            <button class="btn btn-ghost" type="submit">Delete</button>
        </form>
    </div>
</div>

<div class="grid grid-main">
    <div class="stack">
        <div class="card">
            <div class="card-head"><h2>Logo and tagline</h2><div class="row">{!! $regen('logo', 'New logo') !!} {!! $regen('tagline', 'New tagline') !!}</div></div>
            <div class="kit-hero">
                <div style="background: {{ $kit->color('background') }};padding:18px;border-radius:16px;border:1px solid var(--line)">
                    <x-brand-logo :kit="$kit" :size="120"/>
                </div>
                <div style="flex:1;min-width:220px">
                    <div class="name" style="font-family: '{{ $heading }}', sans-serif; color: {{ $kit->color('neutral') }}">{{ $kit->business_name }}</div>
                    <div class="tagline" style="font-family: '{{ $body }}', sans-serif">“{{ $kit->tagline }}”</div>
                    <div class="row mt">
                        <span class="preset-pill">Shape: {{ $kit->logo['shape'] ?? 'circle' }}</span>
                        <span class="preset-pill">Style: {{ $kit->logo['style'] ?? 'solid' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Colour palette</h2>{!! $regen('colors', 'New colours') !!}</div>
            <div class="swatch-grid">
                @foreach (\App\Models\BrandKit::COLOR_ROLES as $role)
                    @php($hex = $kit->color($role))
                    <div class="swatch">
                        <div class="color" style="background: {{ $hex }}; color: {{ \App\Support\Color::textOn($hex) }}">{{ $hex }}</div>
                        <div class="meta"><strong>{{ $role }}</strong><button type="button" class="link-btn small" data-copy="{{ $hex }}">Copy</button></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Typography</h2>{!! $regen('typography', 'New fonts') !!}</div>
            <div class="grid grid-2">
                <div class="font-preview">
                    <div class="small muted">Heading · {{ $heading }}</div>
                    <div class="big" style="font-family: '{{ $heading }}', sans-serif; font-weight:700">Aa Bb Cc 123</div>
                    <div style="font-family: '{{ $heading }}', sans-serif; font-size:20px; font-weight:700">New Eid Collection</div>
                </div>
                <div class="font-preview">
                    <div class="small muted">Body · {{ $body }}</div>
                    <div class="big" style="font-family: '{{ $body }}', sans-serif">Aa Bb Cc 123</div>
                    <div style="font-family: '{{ $body }}', sans-serif">Soft cotton, hand-finished and delivered anywhere in Bangladesh within 3 days.</div>
                </div>
            </div>
            <p class="help mt">Fonts are free on Google Fonts. The preview needs an internet connection.</p>
        </div>

        <div class="card">
            <div class="card-head"><h2>Brand voice and mission</h2>{!! $regen('voice', 'New voice') !!}</div>
            <h3>Voice</h3>
            <p>{{ $kit->voice }}</p>
            <h3>Mission</h3>
            <p>{{ $kit->mission }}</p>
            <h3>Keywords</h3>
            <div class="chips">@foreach ($kit->keywords ?? [] as $word) <span class="chip">{{ $word }}</span> @endforeach</div>
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <h2>Preview on social media</h2>
            <div class="mock-post">
                <div class="mp-head">
                    <x-brand-logo :kit="$kit" :size="36"/>
                    <div><strong style="font-size:13px">{{ $kit->business_name }}</strong><div class="small muted">Sponsored · 🌐</div></div>
                </div>
                <div class="mp-img" style="background: {{ $kit->color('primary') }}; color: {{ \App\Support\Color::textOn($kit->color('primary')) }}">
                    <div style="font-family: '{{ $heading }}', sans-serif; font-size:26px; font-weight:800">NEW ARRIVALS</div>
                    <div style="font-family: '{{ $body }}', sans-serif; margin-top:6px">{{ $kit->tagline }}</div>
                    <span style="margin-top:12px;background: {{ $kit->color('accent') }}; color: {{ \App\Support\Color::textOn($kit->color('accent')) }}; padding:6px 14px; border-radius:999px; font-weight:700; font-size:13px">Shop now</span>
                </div>
                <div class="mp-body" style="font-family: '{{ $body }}', sans-serif">✨ Fresh styles just landed. Inbox us to order! #{{ \Illuminate\Support\Str::studly($kit->business_name) }}</div>
            </div>
        </div>
        <div class="card">
            <h2>Kit details</h2>
            <ul class="list">
                <li><span class="muted">Audience</span><span>{{ $kit->audience ?: '—' }}</span></li>
                <li><span class="muted">Personality</span><span>{{ ucfirst($kit->personality) }}</span></li>
                <li><span class="muted">Last updated</span><span>{{ $kit->updated_at->diffForHumans() }}</span></li>
                <li><span class="muted">Regenerate cost</span><span>{{ $assetCost }} credit each</span></li>
            </ul>
        </div>
    </div>
</div>
@endsection
