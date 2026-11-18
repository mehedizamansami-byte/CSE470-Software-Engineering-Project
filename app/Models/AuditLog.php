<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODEL layer: one entry in a workspace's audit log (Feature 4).
 */
class AuditLog extends Model
{
    protected $fillable = ['seller_id', 'user_id', 'actor_name', 'action', 'description', 'ip_address'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The first part of the action, e.g. "team" for "team.invited". */
    public function area(): string
    {
        return explode('.', $this->action)[0];
    }
}
