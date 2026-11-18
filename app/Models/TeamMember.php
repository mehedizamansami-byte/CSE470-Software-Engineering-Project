<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODEL layer: a collaborator invited to a seller's team (Feature 4).
 */
class TeamMember extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';

    protected $fillable = [
        'seller_id', 'user_id', 'email', 'name', 'permissions', 'status', 'token', 'invited_at', 'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function inviteUrl(): ?string
    {
        return $this->token ? route('invitations.show', $this->token) : null;
    }

    /** Human readable list of granted permissions. */
    public function permissionLabels(): string
    {
        $labels = config('brandbondhu.workspace_permissions');

        return collect($this->permissions ?? [])->map(fn ($p) => $labels[$p] ?? $p)->implode(', ') ?: 'No access';
    }
}
