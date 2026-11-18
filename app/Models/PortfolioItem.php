<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODEL layer: one piece of work in a designer's portfolio (Feature 3).
 */
class PortfolioItem extends Model
{
    protected $fillable = ['title', 'category', 'description', 'image_path', 'link'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(DesignerProfile::class, 'designer_profile_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('uploads/'.$this->image_path) : null;
    }
}
