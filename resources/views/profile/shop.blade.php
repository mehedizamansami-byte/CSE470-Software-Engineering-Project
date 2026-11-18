@extends('layouts.app')
@section('title', 'Shop profile')

@section('content')
<div class="page-head">
    <div>
        <h1>Shop profile</h1>
        <p class="sub">Tell customers and designers about your business. The AI also uses this to suggest your brand kit.</p>
    </div>
    @if ($shop->exists)
        <span class="badge badge-green">{{ $shop->completeness() }}% complete</span>
    @endif
</div>

<div class="grid grid-main">
    <div class="card">
        <form method="POST" action="{{ route('shop.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="field">
                    <label for="name">Shop name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $shop->name) }}" required>
                </div>
                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(old('category', $shop->category) === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field full">
                    <label for="description">About the shop</label>
                    <textarea id="description" name="description" placeholder="What do you sell? What makes you special?">{{ old('description', $shop->description) }}</textarea>
                </div>
                <div class="field">
                    <label for="phone">Shop phone</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone', $shop->phone) }}" placeholder="01712345678">
                </div>
                <div class="field">
                    <label for="email">Shop email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $shop->email) }}">
                </div>
                <div class="field">
                    <label for="address">Address</label>
                    <input id="address" type="text" name="address" value="{{ old('address', $shop->address) }}" placeholder="House, road, area">
                </div>
                <div class="field">
                    <label for="city">City</label>
                    <input id="city" type="text" name="city" value="{{ old('city', $shop->city) }}" placeholder="Dhaka">
                </div>
                <div class="field">
                    <label for="facebook_url">Facebook page link</label>
                    <input id="facebook_url" type="url" name="facebook_url" value="{{ old('facebook_url', $shop->facebook_url) }}" placeholder="https://facebook.com/yourshop">
                </div>
                <div class="field">
                    <label for="instagram_url">Instagram link</label>
                    <input id="instagram_url" type="url" name="instagram_url" value="{{ old('instagram_url', $shop->instagram_url) }}" placeholder="https://instagram.com/yourshop">
                </div>
                <div class="field">
                    <label for="website">Website</label>
                    <input id="website" type="url" name="website" value="{{ old('website', $shop->website) }}" placeholder="https://">
                </div>
                <div class="field">
                    <label for="logo">Logo image</label>
                    <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp">
                    <p class="help">PNG, JPG or WEBP, up to 2 MB.</p>
                </div>
            </div>
            <div class="actions"><button class="btn" type="submit">Save shop profile</button></div>
        </form>
    </div>

    <div class="card">
        <h2>Preview</h2>
        <div class="row">
            <span class="avatar lg">
                @if ($shop->logoUrl()) <img src="{{ $shop->logoUrl() }}" alt="Shop logo"> @else {{ mb_substr($shop->name ?: 'S', 0, 1) }} @endif
            </span>
            <div>
                <strong style="font-size:17px">{{ $shop->name ?: 'Your shop' }}</strong>
                <div class="muted small">{{ $shop->category ?: 'Category' }} @if ($shop->city) · {{ $shop->city }} @endif</div>
            </div>
        </div>
        <p class="mt muted">{{ $shop->description ?: 'Your shop description will appear here.' }}</p>
        <ul class="list">
            @if ($shop->phone) <li><span>📞 {{ $shop->phone }}</span></li> @endif
            @if ($shop->facebook_url) <li><a href="{{ $shop->facebook_url }}" target="_blank" rel="noopener">Facebook page</a></li> @endif
            @if ($shop->instagram_url) <li><a href="{{ $shop->instagram_url }}" target="_blank" rel="noopener">Instagram</a></li> @endif
        </ul>
    </div>
</div>
@endsection
