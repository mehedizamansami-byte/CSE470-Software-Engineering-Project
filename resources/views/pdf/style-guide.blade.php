<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $guide->title }}</title>
@php
    $primary = $kit->color('primary');
    $secondary = $kit->color('secondary');
    $accent = $kit->color('accent');
    $neutral = $kit->color('neutral');
    $background = $kit->color('background');
    $onPrimary = \App\Support\Color::textOn($primary);
    $preset = $guide->preset;
    $serif = $preset === 'classic';
    $logo = is_array($kit->logo) ? $kit->logo : [];
    $radius = ['circle' => '50%', 'rounded' => '18px', 'hexagon' => '12px', 'diamond' => '6px', 'shield' => '10px'][$logo['shape'] ?? 'circle'] ?? '50%';
    $type = $guide->rule('typography', []);
@endphp
<style>
    @page { margin: 30px 36px; }
    body { font-family: {{ $serif ? '"DejaVu Serif", serif' : '"DejaVu Sans", sans-serif' }}; font-size: 11.5px; color: #1f2433; }
    h2 { font-size: {{ $preset === 'bold' ? '18px' : '15px' }}; margin: 0 0 10px; {{ $preset === 'minimal' ? 'text-transform: uppercase; letter-spacing: 2px;' : '' }}
         color: {{ $preset === 'minimal' ? '#111111' : $primary }}; }
    .cover { padding: 26px; {{ $preset === 'minimal' || $preset === 'classic' ? 'border: 2px solid '.$primary.';' : 'background: '.$primary.'; color: '.$onPrimary.';' }} }
    .title { font-size: {{ $preset === 'bold' ? '28px' : '22px' }}; font-weight: bold; }
    .section { padding: 14px 0; border-bottom: 1px solid #e4e7ef; page-break-inside: avoid; }
    .logo { width: 70px; height: 70px; border-radius: {{ $radius }}; background: {{ $primary }}; color: {{ $onPrimary }}; text-align: center; line-height: 70px; font-size: 28px; font-weight: bold; }
    .swatch { height: 46px; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; }
    .muted { color: #667085; }
    ul { margin: 4px 0; padding-left: 16px; }
    li { margin: 3px 0; }
    .do { border-left: 4px solid #12b76a; padding-left: 10px; }
    .dont { border-left: 4px solid #f04438; padding-left: 10px; }
</style>
</head>
<body>
<div class="cover">
    <table>
        <tr>
            <td style="width:90px"><div class="logo" style="{{ $preset === 'minimal' || $preset === 'classic' ? '' : 'border:2px solid '.$onPrimary.';' }}">{{ mb_substr($logo['initials'] ?? 'B', 0, 2) }}</div></td>
            <td>
                <div class="title">{{ $guide->title }}</div>
                <div style="font-size:14px;margin-top:4px">{{ $kit->tagline }}</div>
                <div style="margin-top:6px;font-size:10px">{{ $kit->industry }} · {{ ucfirst($kit->personality) }} personality · {{ ucfirst($preset) }} layout</div>
            </td>
        </tr>
    </table>
</div>

@if ($guide->shows('overview') && $guide->rule('overview'))
    <div class="section">
        <h2>Brand overview</h2>
        <p>{{ $guide->rule('overview') }}</p>
        @if (! empty($kit->keywords))
            <p class="muted">Keywords: {{ implode(', ', $kit->keywords) }}</p>
        @endif
    </div>
@endif

@if ($guide->shows('logo'))
    <div class="section">
        <h2>Logo usage</h2>
        <ul>@foreach ($guide->ruleList('logo') as $rule) <li>{{ $rule }}</li> @endforeach</ul>
    </div>
@endif

@if ($guide->shows('colors'))
    <div class="section">
        <h2>Colour palette</h2>
        <table>
            <tr>
                @foreach (\App\Models\BrandKit::COLOR_ROLES as $role)
                    <td style="width:20%;padding:0 4px">
                        <div class="swatch" style="background: {{ $kit->color($role) }}; border:1px solid #e4e7ef"></div>
                        <strong>{{ ucfirst($role) }}</strong><br>
                        <span class="muted">{{ $kit->color($role) }}</span><br>
                        <span style="font-size:9.5px">{{ $guide->rule('colors.'.$role) }}</span>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>
@endif

@if ($guide->shows('typography'))
    <div class="section">
        <h2>Typography</h2>
        <p>Heading font: <strong>{{ $kit->headingFont() }}</strong> &nbsp; · &nbsp; Body font: <strong>{{ $kit->bodyFont() }}</strong> <span class="muted">(free on Google Fonts)</span></p>
        <table>
            <tr><td style="width:80px" class="muted">Heading 1</td><td style="font-size:{{ $type['h1'] ?? 32 }}px;font-weight:bold">{{ $kit->business_name }}</td><td class="muted" style="width:50px">{{ $type['h1'] ?? 32 }} px</td></tr>
            <tr><td class="muted">Heading 2</td><td style="font-size:{{ $type['h2'] ?? 24 }}px;font-weight:bold">New collection</td><td class="muted">{{ $type['h2'] ?? 24 }} px</td></tr>
            <tr><td class="muted">Body</td><td style="font-size:{{ $type['body'] ?? 16 }}px">Quality products, delivered all over Bangladesh.</td><td class="muted">{{ $type['body'] ?? 16 }} px</td></tr>
            <tr><td class="muted">Small</td><td style="font-size:{{ $type['small'] ?? 13 }}px">Prices include VAT. Delivery in 2 to 3 days.</td><td class="muted">{{ $type['small'] ?? 13 }} px</td></tr>
        </table>
    </div>
@endif

@if ($guide->shows('voice'))
    <div class="section">
        <h2>Tone of voice</h2>
        @if ($kit->voice) <p>{{ $kit->voice }}</p> @endif
        <table>
            <tr>
                <td style="width:50%;padding-right:8px"><div class="do"><strong>Do</strong><ul>@foreach ($guide->ruleList('voice_do') as $rule) <li>{{ $rule }}</li> @endforeach</ul></div></td>
                <td style="width:50%;padding-left:8px"><div class="dont"><strong>Don't</strong><ul>@foreach ($guide->ruleList('voice_dont') as $rule) <li>{{ $rule }}</li> @endforeach</ul></div></td>
            </tr>
        </table>
    </div>
@endif

@if ($guide->shows('imagery'))
    <div class="section">
        <h2>Imagery</h2>
        <ul>@foreach ($guide->ruleList('imagery') as $rule) <li>{{ $rule }}</li> @endforeach</ul>
    </div>
@endif

@if ($guide->shows('social'))
    <div class="section">
        <h2>Social media rules</h2>
        <ul>@foreach ($guide->ruleList('social') as $rule) <li>{{ $rule }}</li> @endforeach</ul>
    </div>
@endif

<p class="muted" style="margin-top:18px;font-size:9.5px">Generated with BrandBondhu on {{ now()->format('d M Y') }}.</p>
</body>
</html>
