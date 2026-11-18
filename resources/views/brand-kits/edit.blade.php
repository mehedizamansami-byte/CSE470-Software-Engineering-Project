@extends('layouts.app')
@section('title', 'Edit '.$kit->name)

@section('content')
<div class="page-head">
    <div>
        <h1>Edit brand kit</h1>
        <p class="sub">Fine-tune anything the AI made. Editing is free; only regenerating uses credits.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('brand-kits.show', $kit) }}">Back to kit</a>
</div>

<form method="POST" action="{{ route('brand-kits.update', $kit) }}">
    @csrf @method('PUT')
    <div class="grid grid-2">
        <div class="card">
            <h2>Basics</h2>
            <div class="field"><label for="name">Kit name</label><input id="name" type="text" name="name" value="{{ old('name', $kit->name) }}" required></div>
            <div class="field"><label for="business_name">Business name</label><input id="business_name" type="text" name="business_name" value="{{ old('business_name', $kit->business_name) }}" required></div>
            <div class="field">
                <label for="industry">Industry</label>
                <select id="industry" name="industry">
                    @foreach ($industries as $industry) <option value="{{ $industry }}" @selected(old('industry', $kit->industry) === $industry)>{{ $industry }}</option> @endforeach
                </select>
            </div>
            <div class="field"><label for="audience">Audience</label><input id="audience" type="text" name="audience" value="{{ old('audience', $kit->audience) }}"></div>
            <div class="field">
                <label for="personality">Personality</label>
                <select id="personality" name="personality">
                    @foreach ($personalities as $key => $label) <option value="{{ $key }}" @selected(old('personality', $kit->personality) === $key)>{{ $label }}</option> @endforeach
                </select>
            </div>
            <div class="field"><label for="tagline">Tagline</label><input id="tagline" type="text" name="tagline" value="{{ old('tagline', $kit->tagline) }}"></div>
        </div>

        <div class="card">
            <h2>Colours</h2>
            @foreach (\App\Models\BrandKit::COLOR_ROLES as $role)
                <div class="field">
                    <label for="color_{{ $role }}">{{ ucfirst($role) }}</label>
                    <div class="row" style="flex-wrap:nowrap">
                        <input type="color" value="{{ old("colors.$role", $kit->color($role)) }}" data-color-input="{{ $role }}">
                        <input id="color_{{ $role }}" type="text" name="colors[{{ $role }}]" value="{{ old("colors.$role", $kit->color($role)) }}" data-color-text="{{ $role }}" pattern="#[0-9A-Fa-f]{6}" required>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card">
            <h2>Logo and fonts</h2>
            <div class="form-grid">
                <div class="field"><label for="initials">Initials</label><input id="initials" type="text" name="logo[initials]" maxlength="2" value="{{ old('logo.initials', $kit->logo['initials'] ?? '') }}" required></div>
                <div class="field">
                    <label for="shape">Shape</label>
                    <select id="shape" name="logo[shape]">
                        @foreach (['circle', 'rounded', 'hexagon', 'diamond', 'shield'] as $shape) <option value="{{ $shape }}" @selected(old('logo.shape', $kit->logo['shape'] ?? '') === $shape)>{{ ucfirst($shape) }}</option> @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="style">Style</label>
                    <select id="style" name="logo[style]">
                        @foreach (['solid', 'outline', 'split'] as $style) <option value="{{ $style }}" @selected(old('logo.style', $kit->logo['style'] ?? '') === $style)>{{ ucfirst($style) }}</option> @endforeach
                    </select>
                </div>
                <div class="field"><label>&nbsp;</label><x-brand-logo :kit="$kit" :size="56"/></div>
                <div class="field"><label for="heading">Heading font</label><input id="heading" type="text" name="typography[heading]" value="{{ old('typography.heading', $kit->headingFont()) }}" required></div>
                <div class="field"><label for="body">Body font</label><input id="body" type="text" name="typography[body]" value="{{ old('typography.body', $kit->bodyFont()) }}" required></div>
            </div>
            <p class="help">Use any font name from fonts.google.com, for example Poppins or Hind Siliguri.</p>
        </div>

        <div class="card">
            <h2>Voice</h2>
            <div class="field"><label for="voice">Brand voice</label><textarea id="voice" name="voice">{{ old('voice', $kit->voice) }}</textarea></div>
            <div class="field"><label for="mission">Mission</label><textarea id="mission" name="mission" style="min-height:70px">{{ old('mission', $kit->mission) }}</textarea></div>
            <div class="field"><label for="keywords">Keywords</label><input id="keywords" type="text" name="keywords" value="{{ old('keywords', implode(', ', $kit->keywords ?? [])) }}"><p class="help">Separate with commas.</p></div>
        </div>
    </div>
    <div class="actions">
        <button class="btn" type="submit">Save changes</button>
        <a class="btn btn-ghost" href="{{ route('brand-kits.show', $kit) }}">Cancel</a>
    </div>
</form>
@endsection
