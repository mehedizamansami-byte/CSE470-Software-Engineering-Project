@extends('layouts.app')
@section('title', 'My portfolio')

@section('content')
<div class="page-head">
    <div>
        <h1>Designer portfolio</h1>
        <p class="sub">Sellers look at this page before hiring you. Show your skills, prices and best work.</p>
    </div>
    @if ($profile)
        <a class="btn btn-secondary" href="{{ route('designers.show', auth()->user()) }}">View public profile</a>
    @endif
</div>

<div class="grid grid-main">
    <div class="card">
        <h2>Profile</h2>
        <form method="POST" action="{{ route('portfolio.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="field full">
                    <label for="headline">Headline</label>
                    <input id="headline" type="text" name="headline" value="{{ old('headline', $profile?->headline) }}" placeholder="Logo and social media designer" required>
                </div>
                <div class="field full">
                    <label for="bio">About you</label>
                    <textarea id="bio" name="bio" placeholder="Your experience, style and the kind of clients you love working with.">{{ old('bio', $profile?->bio) }}</textarea>
                </div>
                <div class="field full">
                    <label for="skills">Skills</label>
                    <input id="skills" type="text" name="skills" value="{{ old('skills', implode(', ', $profile?->skills ?? [])) }}" placeholder="Logo design, Facebook banners, Packaging">
                    <p class="help">Separate skills with commas.</p>
                </div>
                <div class="field">
                    <label for="starting_price">Starting price (৳)</label>
                    <input id="starting_price" type="number" name="starting_price" min="0" value="{{ old('starting_price', $profile?->starting_price) }}" placeholder="1500">
                </div>
                <div class="field">
                    <label for="experience_years">Years of experience</label>
                    <input id="experience_years" type="number" name="experience_years" min="0" max="60" value="{{ old('experience_years', $profile?->experience_years) }}">
                </div>
                <div class="field">
                    <label for="location">Location</label>
                    <input id="location" type="text" name="location" value="{{ old('location', $profile?->location) }}" placeholder="Chattogram">
                </div>
                <div class="field">
                    <label for="portfolio_url">Behance / Dribbble / website</label>
                    <input id="portfolio_url" type="url" name="portfolio_url" value="{{ old('portfolio_url', $profile?->portfolio_url) }}" placeholder="https://">
                </div>
                <div class="field">
                    <label for="avatar">Profile photo</label>
                    <input id="avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <label class="check"><input type="checkbox" name="is_available" value="1" @checked(old('is_available', $profile?->is_available ?? true))> Available for new work</label>
                </div>
            </div>
            <div class="actions"><button class="btn" type="submit">Save profile</button></div>
        </form>
    </div>

    <div class="card">
        <h2>Add portfolio item</h2>
        @if ($profile)
            <form method="POST" action="{{ route('portfolio.items.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="field"><label for="title">Title</label><input id="title" type="text" name="title" value="{{ old('title') }}" required placeholder="Eid campaign for a clothing brand"></div>
                <div class="field"><label for="item_category">Category</label><input id="item_category" type="text" name="category" value="{{ old('category') }}" placeholder="Social media"></div>
                <div class="field"><label for="description">Short description</label><textarea id="description" name="description" style="min-height:70px">{{ old('description') }}</textarea></div>
                <div class="field"><label for="link">Link (optional)</label><input id="link" type="url" name="link" value="{{ old('link') }}" placeholder="https://"></div>
                <div class="field"><label for="image">Image</label><input id="image" type="file" name="image" accept="image/png,image/jpeg,image/webp"></div>
                <div class="actions"><button class="btn" type="submit"><x-icon name="plus"/> Add item</button></div>
            </form>
        @else
            <p class="muted">Save your profile first, then add your best work here.</p>
        @endif
    </div>
</div>

<div class="card mt">
    <h2>My work ({{ $items->count() }})</h2>
    @if ($items->isEmpty())
        <p class="muted">No portfolio items yet.</p>
    @else
        <div class="portfolio-grid">
            @foreach ($items as $item)
                <div class="portfolio-item">
                    <div class="thumb">@if ($item->imageUrl()) <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}"> @else {{ $item->category ?: 'Design' }} @endif</div>
                    <div class="body">
                        <strong>{{ $item->title }}</strong>
                        <p class="small muted">{{ $item->description }}</p>
                        <div class="row between">
                            @if ($item->link) <a class="small" href="{{ $item->link }}" target="_blank" rel="noopener">Open link</a> @else <span></span> @endif
                            <form method="POST" action="{{ route('portfolio.items.destroy', $item) }}" data-confirm="Remove this portfolio item?">
                                @csrf @method('DELETE')
                                <button class="link-btn" type="submit" style="color:var(--err)">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
