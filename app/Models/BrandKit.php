<?php

namespace App\Models;

use App\Support\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * MODEL layer: an AI-generated brand identity kit (Feature 8).
 */
class BrandKit extends Model
{
    public const COLOR_ROLES = ['primary', 'secondary', 'accent', 'neutral', 'background'];

    public const ASSETS = [
        'tagline' => 'Tagline',
        'colors' => 'Colour palette',
        'typography' => 'Typography',
        'logo' => 'Logo',
        'voice' => 'Voice, mission and keywords',
    ];

    protected $fillable = [
        'created_by', 'name', 'business_name', 'industry', 'audience', 'personality', 'tagline',
        'colors', 'typography', 'logo', 'voice', 'mission', 'keywords', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'colors' => 'array',
            'typography' => 'array',
            'logo' => 'array',
            'keywords' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function styleGuide(): HasOne
    {
        return $this->hasOne(StyleGuide::class);
    }

    /** A colour by role, always a safe #RRGGBB value. */
    public function color(string $role): string
    {
        $value = $this->colors[$role] ?? null;

        return Color::isHex($value) ? strtoupper($value) : '#4F46E5';
    }

    public function headingFont(): string
    {
        return $this->typography['heading'] ?? 'Poppins';
    }

    public function bodyFont(): string
    {
        return $this->typography['body'] ?? 'Inter';
    }

    /** Google Fonts stylesheet URL for previewing the kit's fonts. */
    public function googleFontsUrl(): string
    {
        $families = collect([$this->headingFont(), $this->bodyFont()])->unique()
            ->map(fn ($f) => 'family='.str_replace(' ', '+', $f))->implode('&');

        return 'https://fonts.googleapis.com/css2?'.$families.'&display=swap';
    }

    /** The data the AI service needs to work on this kit. */
    public function toAiArray(): array
    {
        return $this->only([
            'business_name', 'industry', 'audience', 'personality', 'tagline',
            'colors', 'typography', 'logo', 'voice', 'mission', 'keywords',
        ]);
    }
}
