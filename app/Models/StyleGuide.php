<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODEL layer: a brand style guide built from a brand kit (Feature 9).
 */
class StyleGuide extends Model
{
    public const PRESETS = [
        'classic' => 'Classic: serif headings, framed sections',
        'modern' => 'Modern: bold colour header, rounded cards',
        'minimal' => 'Minimal: black and white, lots of space',
        'bold' => 'Bold: full colour blocks, large type',
    ];

    public const SECTIONS = [
        'overview' => 'Brand overview',
        'logo' => 'Logo usage',
        'colors' => 'Colour palette',
        'typography' => 'Typography',
        'voice' => 'Tone of voice',
        'imagery' => 'Imagery',
        'social' => 'Social media rules',
    ];

    protected $fillable = ['brand_kit_id', 'title', 'preset', 'sections', 'rules'];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'rules' => 'array',
        ];
    }

    public function brandKit(): BelongsTo
    {
        return $this->belongsTo(BrandKit::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function shows(string $section): bool
    {
        return (bool) ($this->sections[$section] ?? false);
    }

    /** @return list<string> */
    public function ruleList(string $key): array
    {
        $value = $this->rules[$key] ?? [];

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    public function rule(string $key, mixed $default = null): mixed
    {
        return data_get($this->rules, $key, $default);
    }
}
