@extends('layouts.app')
@section('title', 'Brand kits')

@section('content')
<div class="page-head">
    <div>
        <h1>Brand identity kits</h1>
        <p class="sub">AI-generated logo, colours, fonts, tagline and brand voice. The active kit is used for captions and style guides.</p>
    </div>
    <a class="btn" href="{{ route('brand-kits.create') }}"><x-icon name="sparkles"/> Generate new kit</a>
</div>

@if ($kits->isEmpty())
    <div class="card">
        <div class="empty">
            <h3>No brand kits yet</h3>
            <p>Answer 5 quick questions and the AI designs a complete brand identity for your shop.</p>
            <a class="btn" href="{{ route('brand-kits.create') }}">Generate my first kit</a>
        </div>
    </div>
@else
    <div class="grid grid-3">
        @foreach ($kits as $kit)
            <div class="card kit-card">
                <div class="top">
                    <x-brand-logo :kit="$kit" :size="64"/>
                    <div style="min-width:0">
                        <strong style="font-size:16px">{{ $kit->name }}</strong>
                        <div class="small muted">{{ $kit->tagline }}</div>
                    </div>
                </div>
                <div class="swatches">
                    @foreach (\App\Models\BrandKit::COLOR_ROLES as $role)
                        <span style="background: {{ $kit->color($role) }}" title="{{ ucfirst($role) }} {{ $kit->color($role) }}"></span>
                    @endforeach
                </div>
                <div class="row">
                    @if ($kit->is_active) <span class="badge badge-green">Active</span> @endif
                    <span class="badge badge-gray">{{ ucfirst($kit->personality) }}</span>
                    @if ($kit->styleGuide) <span class="badge badge-blue">Style guide</span> @endif
                </div>
                <div class="row">
                    <a class="btn btn-sm" href="{{ route('brand-kits.show', $kit) }}">Open</a>
                    @unless ($kit->is_active)
                        <form method="POST" action="{{ route('brand-kits.activate', $kit) }}">
                            @csrf
                            <button class="btn btn-secondary btn-sm" type="submit">Set active</button>
                        </form>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
