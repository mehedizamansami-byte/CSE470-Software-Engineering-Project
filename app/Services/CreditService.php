<?php

namespace App\Services;

use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\DB;

/**
 * SERVICE layer: the AI credit wallet of a seller workspace (Feature 5).
 */
class CreditService
{
    public function cost(string $action): int
    {
        return (int) config("brandbondhu.credit_costs.{$action}", 1);
    }

    /** Stop early, before calling the AI, when the balance is too low. */
    public function ensure(User $owner, string $action): void
    {
        $balance = (int) User::whereKey($owner->id)->value('ai_credits');

        if ($balance < $this->cost($action)) {
            throw new InsufficientCreditsException($this->cost($action), $balance);
        }
    }

    /** Take credits for an action. Throws when the balance is too low. */
    public function charge(User $owner, string $action, string $reason): int
    {
        $cost = $this->cost($action);

        return DB::transaction(function () use ($owner, $cost, $reason) {
            $fresh = User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
            $before = $fresh->ai_credits;

            if ($before < $cost) {
                throw new InsufficientCreditsException($cost, $before);
            }

            $fresh->forceFill(['ai_credits' => $before - $cost])->save();
            $owner->ai_credits = $fresh->ai_credits;

            CreditTransaction::create([
                'user_id' => $owner->id,
                'amount' => -$cost,
                'balance_after' => $fresh->ai_credits,
                'reason' => $reason,
            ]);

            $this->warnIfLow($fresh, $before);

            return $cost;
        });
    }

    public function add(User $owner, int $amount, string $reason): void
    {
        DB::transaction(function () use ($owner, $amount, $reason) {
            $fresh = User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
            $fresh->forceFill(['ai_credits' => $fresh->ai_credits + $amount])->save();
            $owner->ai_credits = $fresh->ai_credits;

            CreditTransaction::create([
                'user_id' => $owner->id,
                'amount' => $amount,
                'balance_after' => $fresh->ai_credits,
                'reason' => $reason,
            ]);
        });
    }

    /** Low-credit warning, sent once when the balance crosses the threshold. */
    private function warnIfLow(User $owner, int $before): void
    {
        $threshold = (int) config('brandbondhu.low_credit_threshold', 10);
        $after = $owner->ai_credits;

        if ($after === 0 && $before > 0) {
            $owner->notify(new AppNotification(
                'You are out of AI credits',
                'AI features are paused until you upgrade your plan or buy a credit pack.',
                route('billing.index', [], false),
                'warning',
            ));
        } elseif ($after < $threshold && $before >= $threshold) {
            $owner->notify(new AppNotification(
                'AI credits are running low',
                "Only {$after} AI credits are left. Top up to keep generating content.",
                route('billing.index', [], false),
                'warning',
            ));
        }
    }
}
