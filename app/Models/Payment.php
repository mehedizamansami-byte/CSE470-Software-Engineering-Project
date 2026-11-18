<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODEL layer: a simulated bKash / Nagad / card payment and its invoice (Feature 6).
 */
class Payment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_AWAITING_OTP = 'awaiting_otp';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PURPOSE_PLAN = 'plan';
    public const PURPOSE_CREDITS = 'credits';

    protected $fillable = [
        'user_id', 'invoice_no', 'purpose', 'plan_id', 'credits', 'description', 'amount', 'method',
        'account', 'transaction_id', 'status', 'otp_code', 'otp_expires_at', 'attempts', 'failure_reason', 'paid_at',
    ];

    protected $hidden = ['otp_code'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'credits' => 'integer',
            'attempts' => 'integer',
            'otp_expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function methodLabel(): string
    {
        return config("brandbondhu.payment_methods.{$this->method}.label", '—');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_AWAITING_OTP => 'Awaiting OTP',
            default => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'amber',
        };
    }
}
