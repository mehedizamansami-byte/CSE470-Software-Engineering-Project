@props(['kit', 'size' => 96])
@php
    // Logo is drawn from saved settings (shape, style, initials, colours). No user SVG is ever rendered.
    $primary = $kit->color('primary');
    $secondary = $kit->color('secondary');
    $background = $kit->color('background');
    $textColor = \App\Support\Color::textOn($primary);
    $logo = is_array($kit->logo) ? $kit->logo : [];
    $shapes = [
        'circle' => '<circle cx="50" cy="50" r="46"/>',
        'rounded' => '<rect x="5" y="5" width="90" height="90" rx="22"/>',
        'hexagon' => '<polygon points="50,4 90,27 90,73 50,96 10,73 10,27"/>',
        'diamond' => '<polygon points="50,3 97,50 50,97 3,50"/>',
        'shield' => '<path d="M50 4 L90 18 V48 C90 72 72 88 50 96 C28 88 10 72 10 48 V18 Z"/>',
    ];
    $shape = $shapes[$logo['shape'] ?? 'circle'] ?? $shapes['circle'];
    $style = $logo['style'] ?? 'solid';
    $initials = mb_substr($logo['initials'] ?? 'B', 0, 2);
    $font = $kit->headingFont();
    $gradientId = 'g'.$kit->id.'_'.$size;
    $fontSize = mb_strlen($initials) > 1 ? 34 : 44;
@endphp
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 100 100" role="img" aria-label="{{ $kit->business_name }} logo" {{ $attributes }}>
    @if ($style === 'outline')
        <g fill="{{ $background }}" stroke="{{ $primary }}" stroke-width="6">{!! $shape !!}</g>
        <text x="50" y="50" text-anchor="middle" dominant-baseline="central" font-family="{{ $font }}, sans-serif" font-weight="700" font-size="{{ $fontSize }}" fill="{{ $primary }}">{{ $initials }}</text>
    @elseif ($style === 'split')
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="50%" stop-color="{{ $primary }}"/>
                <stop offset="50%" stop-color="{{ $secondary }}"/>
            </linearGradient>
        </defs>
        <g fill="url(#{{ $gradientId }})">{!! $shape !!}</g>
        <text x="50" y="50" text-anchor="middle" dominant-baseline="central" font-family="{{ $font }}, sans-serif" font-weight="700" font-size="{{ $fontSize }}" fill="{{ $textColor }}">{{ $initials }}</text>
    @else
        <g fill="{{ $primary }}">{!! $shape !!}</g>
        <text x="50" y="50" text-anchor="middle" dominant-baseline="central" font-family="{{ $font }}, sans-serif" font-weight="700" font-size="{{ $fontSize }}" fill="{{ $textColor }}">{{ $initials }}</text>
    @endif
</svg>
