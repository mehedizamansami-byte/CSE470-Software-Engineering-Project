<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODEL layer: one line in the AI credit ledger (Feature 5).
 */
class CreditTransaction extends Model
{
    protected $fillable = ['user_id', 'amount', 'balance_after', 'reason'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
