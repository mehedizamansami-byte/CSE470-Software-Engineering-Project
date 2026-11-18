<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODEL layer: a seller's shop profile (Feature 3).
 */
class Shop extends Model
{
    public const CATEGORIES = [
        'Fashion & Clothing',
        'Food & Bakery',
        'Beauty & Cosmetics',
        'Handicrafts',
        'Electronics & Gadgets',
        'Home & Living',
        'Jewellery & Accessories',
        'Books & Stationery',
        'Health & Wellness',
        'Other',
    ];

    protected $fillable = [
        'name', 'category', 'description', 'phone', 'email', 'address', 'city',
        'facebook_url', 'instagram_url', 'website', 'logo_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('uploads/'.$this->logo_path) : null;
    }

    /** How complete the profile is, from 0 to 100. */
    public function completeness(): int
    {
        $fields = ['name', 'category', 'description', 'phone', 'address', 'city', 'facebook_url', 'logo_path'];
        $filled = collect($fields)->filter(fn ($f) => filled($this->{$f}))->count();

        return (int) round($filled / count($fields) * 100);
    }
}
