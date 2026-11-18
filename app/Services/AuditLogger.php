<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * SERVICE layer: records who did what inside a seller's workspace (Feature 4).
 */
class AuditLogger
{
    public static function log(?User $workspaceOwner, string $action, string $description, ?User $actor = null): void
    {
        if (! $workspaceOwner) {
            return;
        }

        $actor ??= auth()->user();

        AuditLog::create([
            'seller_id' => $workspaceOwner->id,
            'user_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'System',
            'action' => $action,
            'description' => Str::limit($description, 250),
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
