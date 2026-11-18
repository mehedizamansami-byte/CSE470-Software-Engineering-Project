<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODEL layer: a social media post in the content manager (Feature 10).
 */
class Post extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';

    public const PLATFORMS = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'both' => 'Facebook + Instagram'];

    public const REMINDERS = [15 => '15 minutes before', 30 => '30 minutes before', 60 => '1 hour before', 120 => '2 hours before', 1440 => '1 day before'];

    protected $fillable = [
        'created_by', 'title', 'caption', 'hashtags', 'platform', 'image_path', 'status',
        'scheduled_at', 'remind_before', 'reminder_sent_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'published_at' => 'datetime',
            'remind_before' => 'integer',
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

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('uploads/'.$this->image_path) : null;
    }

    public function platformLabel(): string
    {
        return self::PLATFORMS[$this->platform] ?? ucfirst($this->platform);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'green',
            self::STATUS_SCHEDULED => 'blue',
            default => 'gray',
        };
    }

    /** When the reminder notification should go out, if any. */
    public function reminderDueAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->scheduled_at || ! $this->remind_before) {
            return null;
        }

        return $this->scheduled_at->copy()->subMinutes($this->remind_before);
    }
}
