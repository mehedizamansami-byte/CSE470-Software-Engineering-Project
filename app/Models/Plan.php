<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MODEL layer: a subscription plan (Free, Basic, Premium, Enterprise) (Feature 5).
 */
class Plan extends Model
{
    protected $fillable = ['name', 'slug', 'price', 'monthly_credits', 'team_limit', 'brand_kit_limit', 'features', 'sort_order'];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price' => 'integer',
            'monthly_credits' => 'integer',
            'team_limit' => 'integer',
            'brand_kit_limit' => 'integer',
        ];
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }
}
