<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MODEL layer: a designer's public portfolio profile (Feature 3).
 */
class DesignerProfile extends Model
{
    protected $fillable = [
        'headline', 'bio', 'skills', 'starting_price', 'experience_years',
        'location', 'portfolio_url', 'is_available', 'avatar_path',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'is_available' => 'boolean',
            'starting_price' => 'integer',
            'experience_years' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PortfolioItem::class)->latest();
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('uploads/'.$this->avatar_path) : null;
    }

    /** How complete the portfolio is, from 0 to 100. */
    public function completeness(): int
    {
        $score = collect(['headline', 'bio', 'starting_price', 'experience_years', 'location', 'avatar_path'])
            ->filter(fn ($f) => filled($this->{$f}))->count();
        $score += ! empty($this->skills) ? 1 : 0;
        $score += $this->items()->exists() ? 1 : 0;

        return (int) round($score / 8 * 100);
    }
}
