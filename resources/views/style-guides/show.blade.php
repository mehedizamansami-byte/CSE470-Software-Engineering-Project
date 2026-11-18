@extends('layouts.app')
@section('title', $guide->title)

@push('head')
    <link rel="stylesheet" href="{{ $kit->googleFontsUrl() }}">
@endpush

@section('content')
@php
    $primary = $kit->color('primary');
    $onPrimary = \App\Support\Color::textOn($primary);
    $preset = $guide->preset;
    $type = $guide->rule('typography', []);
    $heading = $kit->headingFont();
    $body = $kit->bodyFont();
@endphp

<div class="page-head">
    <div>
        <h1>{{ $guide->title }}</h1>
        <p class="sub">Built from <a href="{{ route('brand-kits.show', $kit) }}">{{ $kit->name }}</a> · {{ ucfirst($preset) }} layout · updated {{ $guide->updated_at->diffForHumans() }}</p>
    </div>
    <div class="row">
        <a class="btn" href="{{ route('style-guides.pdf', $kit) }}"><x-icon name="download"/> Export PDF</a>
        <form method="POST" action="{{ route('style-guides.generate', $kit) }}" data-confirm="Regenerate the guide with AI? Your custom rules will be replaced. Uses {{ config('brandbondhu.credit_costs.style_guide') }} credits.">
            @csrf
            <input type="hidden" name="preset" value="{{ $preset }}">
            <button class="btn btn-secondary" type="submit"><x-icon name="refresh"/> Regenerate</button>
        </form>
    </div>
</div>

<div class="grid grid-main">
    {{-- The guide, styled by the chosen layout preset --}}
    <div class="sg sg-{{ $preset }}">
        <div class="sg-cover" style="{{ in_array($preset, ['modern', 'bold']) ? 'background:'.$primary.';color:'.$onPrimary : '' }}">
            <x-brand-logo :kit="$kit" :size="$preset === 'bold' ? 96 : 76"/>
            <div>
                <div class="title" style="font-family: '{{ $heading }}', sans-serif">{{ $guide->title }}</div>
                <div style="font-family: '{{ $body }}', sans-serif;opacity:.9;margin-top:4px">{{ $kit->tagline }}</div>
            </div>
        </div>

        @php($headStyle = $preset === 'modern' ? 'background:'.$kit->color('background').';color:'.$primary : ($preset === 'bold' ? 'color:'.$primary : ''))

        @if ($guide->shows('overview') && $guide->rule('overview'))
            <div class="sg-section"><h2 style="{{ $headStyle }}">Brand overview</h2><p>{{ $guide->rule('overview') }}</p>
                @if (! empty($kit->keywords)) <div class="chips">@foreach ($kit->keywords as $word) <span class="chip">{{ $word }}</span> @endforeach</div> @endif
            </div>
        @endif

        @if ($guide->shows('logo'))
            <div class="sg-section"><h2 style="{{ $headStyle }}">Logo usage</h2>
                <div class="row" style="gap:16px;margin-bottom:10px">
                    <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#fff"><x-brand-logo :kit="$kit" :size="64"/></div>
                    <div style="padding:14px;border-radius:12px;background: {{ $primary }}"><x-brand-logo :kit="$kit" :size="64"/></div>
                </div>
                <ul class="bullets">@foreach ($guide->ruleList('logo') as $rule) <li>{{ $rule }}</li> @endforeach</ul>
            </div>
        @endif

        @if ($guide->shows('colors'))
            <div class="sg-section"><h2 style="{{ $headStyle }}">Colour palette</h2>
                <div class="swatch-grid">
                    @foreach (\App\Models\BrandKit::COLOR_ROLES as $role)
                        @php($hex = $kit->color($role))
                        <div class="swatch">
                            <div class="color" style="background: {{ $hex }}; color: {{ \App\Support\Color::textOn($hex) }}; height:64px">{{ $hex }}</div>
                            <div class="meta"><strong>{{ $role }}</strong>{{ $guide->rule('colors.'.$role) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($guide->shows('typography'))
            <div class="sg-section"><h2 style="{{ $headStyle }}">Typography</h2>
                <p class="muted">Headings: <strong>{{ $heading }}</strong> · Body: <strong>{{ $body }}</strong></p>
                <div class="type-row"><span class="tag">H1 · {{ $type['h1'] ?? 32 }}px</span><span style="font-family: '{{ $heading }}', sans-serif; font-size: {{ $type['h1'] ?? 32 }}px; font-weight:700; line-height:1.2">{{ $kit->business_name }}</span></div>
                <div class="type-row"><span class="tag">H2 · {{ $type['h2'] ?? 24 }}px</span><span style="font-family: '{{ $heading }}', sans-serif; font-size: {{ $type['h2'] ?? 24 }}px; font-weight:700">New collection</span></div>
                <div class="type-row"><span class="tag">Body · {{ $type['body'] ?? 16 }}px</span><span style="font-family: '{{ $body }}', sans-serif; font-size: {{ $type['body'] ?? 16 }}px">Quality products, delivered all over Bangladesh.</span></div>
                <div class="type-row"><span class="tag">Small · {{ $type['small'] ?? 13 }}px</span><span style="font-family: '{{ $body }}', sans-serif; font-size: {{ $type['small'] ?? 13 }}px">Prices include VAT. Delivery in 2 to 3 days.</span></div>
            </div>
        @endif

        @if ($guide->shows('voice'))
            <div class="sg-section"><h2 style="{{ $headStyle }}">Tone of voice</h2>
                @if ($kit->voice) <p>{{ $kit->voice }}</p> @endif
                <div class="do-dont">
                    <div class="do"><strong>Do</strong><ul class="bullets">@foreach ($guide->ruleList('voice_do') as $rule) <li>{{ $rule }}</li> @endforeach</ul></div>
                    <div class="dont"><strong>Don't</strong><ul class="bullets">@foreach ($guide->ruleList('voice_dont') as $rule) <li>{{ $rule }}</li> @endforeach</ul></div>
                </div>
            </div>
        @endif

        @if ($guide->shows('imagery'))
            <div class="sg-section"><h2 style="{{ $headStyle }}">Imagery</h2><ul class="bullets">@foreach ($guide->ruleList('imagery') as $rule) <li>{{ $rule }}</li> @endforeach</ul></div>
        @endif

        @if ($guide->shows('social'))
            <div class="sg-section"><h2 style="{{ $headStyle }}">Social media rules</h2><ul class="bullets">@foreach ($guide->ruleList('social') as $rule) <li>{{ $rule }}</li> @endforeach</ul></div>
        @endif
    </div>

    {{-- Customisation panel --}}
    <div class="card">
        <h2>Customise</h2>
        <form method="POST" action="{{ route('style-guides.update', $kit) }}">
            @csrf @method('PUT')
            <div class="field"><label for="title">Title</label><input id="title" type="text" name="title" value="{{ old('title', $guide->title) }}" required></div>
            <div class="field">
                <label for="preset">Layout preset</label>
                <select id="preset" name="preset">
                    @foreach (\App\Models\StyleGuide::PRESETS as $key => $label) <option value="{{ $key }}" @selected(old('preset', $preset) === $key)>{{ $label }}</option> @endforeach
                </select>
            </div>
            <label>Sections to show</label>
            <div class="checks">
                @foreach (\App\Models\StyleGuide::SECTIONS as $key => $label)
                    <label><input type="checkbox" name="sections[]" value="{{ $key }}" @checked($guide->shows($key))> {{ $label }}</label>
                @endforeach
            </div>
            <div class="field"><label for="overview">Overview</label><textarea id="overview" name="overview">{{ old('overview', $guide->rule('overview')) }}</textarea></div>
            <div class="field"><label for="logo_rules">Logo rules <span class="muted">(one per line)</span></label><textarea id="logo_rules" name="logo_rules">{{ old('logo_rules', implode("\n", $guide->ruleList('logo'))) }}</textarea></div>
            <label>Colour usage notes</label>
            @foreach (\App\Models\BrandKit::COLOR_ROLES as $role)
                <input type="text" name="colors[{{ $role }}]" value="{{ old("colors.$role", $guide->rule('colors.'.$role)) }}" placeholder="{{ ucfirst($role) }}" style="margin-bottom:6px">
            @endforeach
            <label>Type sizes (px)</label>
            <div class="grid grid-4" style="gap:8px">
                @foreach (['h1' => 32, 'h2' => 24, 'body' => 16, 'small' => 13] as $key => $default)
                    <div><span class="small muted">{{ strtoupper($key) }}</span><input type="number" name="typography[{{ $key }}]" value="{{ old("typography.$key", $type[$key] ?? $default) }}" required></div>
                @endforeach
            </div>
            <div class="field"><label for="voice_do">Voice: do <span class="muted">(one per line)</span></label><textarea id="voice_do" name="voice_do">{{ old('voice_do', implode("\n", $guide->ruleList('voice_do'))) }}</textarea></div>
            <div class="field"><label for="voice_dont">Voice: don't</label><textarea id="voice_dont" name="voice_dont">{{ old('voice_dont', implode("\n", $guide->ruleList('voice_dont'))) }}</textarea></div>
            <div class="field"><label for="imagery">Imagery rules</label><textarea id="imagery" name="imagery">{{ old('imagery', implode("\n", $guide->ruleList('imagery'))) }}</textarea></div>
            <div class="field"><label for="social">Social media rules</label><textarea id="social" name="social">{{ old('social', implode("\n", $guide->ruleList('social'))) }}</textarea></div>
            <div class="actions"><button class="btn" type="submit" style="width:100%">Save style guide</button></div>
        </form>
    </div>
</div>
@endsection
