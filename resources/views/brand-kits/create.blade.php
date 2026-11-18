@extends('layouts.app')
@section('title', 'Generate a brand kit')

@section('content')
<div class="page-head">
    <div>
        <h1>Generate an AI brand kit</h1>
        <p class="sub">Tell us about your business. The AI creates a logo, colour palette, fonts, tagline, mission and brand voice.</p>
    </div>
    <span class="badge badge-blue">Costs {{ $cost }} credits · you have {{ $credits }}</span>
</div>

<div class="grid grid-main">
    <div class="card">
        <form method="POST" action="{{ route('brand-kits.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="business_name">Business name</label>
                    <input id="business_name" type="text" name="business_name" value="{{ old('business_name', $shop?->name) }}" required placeholder="Rongdhonu Fashion">
                </div>
                <div class="field">
                    <label for="industry">Industry</label>
                    <select id="industry" name="industry" required>
                        @foreach ($industries as $industry)
                            <option value="{{ $industry }}" @selected(old('industry', $shop?->category) === $industry)>{{ $industry }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field full">
                    <label for="audience">Who are your customers?</label>
                    <input id="audience" type="text" name="audience" value="{{ old('audience') }}" placeholder="Young women in Dhaka who love modern ethnic wear">
                </div>
                <div class="field full">
                    <label>Brand personality</label>
                    <div class="checks">
                        @foreach ($personalities as $key => $label)
                            <label><input type="radio" name="personality" value="{{ $key }}" @checked(old('personality', 'friendly') === $key)> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label for="preferred_color">Favourite colour <span class="muted">(optional)</span></label>
                    <div class="row" style="flex-wrap:nowrap">
                        <input type="color" value="{{ old('preferred_color', '#4F46E5') }}" data-color-input="preferred">
                        <input id="preferred_color" type="text" name="preferred_color" value="{{ old('preferred_color') }}" placeholder="Leave empty to let the AI choose" data-color-text="preferred">
                    </div>
                    <p class="help">If you pick one, the palette is built around it.</p>
                </div>
                <div class="field">
                    <label for="name">Kit name <span class="muted">(optional)</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Eid 2026 look">
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit"><x-icon name="sparkles"/> Generate brand kit ({{ $cost }} credits)</button>
                <a class="btn btn-ghost" href="{{ route('brand-kits.index') }}">Cancel</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>What you get</h2>
        <ul class="list">
            <li><span>🎨 Logo mark in your colours</span></li>
            <li><span>🌈 5-colour palette with usage rules</span></li>
            <li><span>🔤 Heading and body font pair</span></li>
            <li><span>💬 Tagline, mission and brand voice</span></li>
            <li><span>🔁 Regenerate any part for 1 credit</span></li>
        </ul>
        <p class="help mt">AI engine: {{ config('services.ai.driver') === 'http' ? 'FastAPI AI service' : 'built-in offline generator' }}.</p>
    </div>
</div>
@endsection
