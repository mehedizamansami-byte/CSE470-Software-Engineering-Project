<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\DB;

/**
 * SERVICE layer: Free, Basic, Premium and Enterprise subscriptions (Feature 5).
 */
class SubscriptionService
{
    public function __construct(private CreditService $credits) {}

    /** The seller's active subscription. Expired paid plans fall back to Free. */
    public function current(User $owner): Subscription
    {
        $subscription = $owner->subscriptions()->with('plan')
            ->where('status', Subscription::STATUS_ACTIVE)
            ->latest('id')
            ->first();

        if ($subscription && $subscription->ends_at && $subscription->ends_at->isPast() && ! $subscription->plan->isFree()) {
            $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
            $owner->notify(new AppNotification(
                'Your plan has expired',
                "Your {$subscription->plan->name} plan ended. You are now on the Free plan.",
                route('billing.index', [], false),
                'warning',
            ));
            $subscription = null;
        }

        return $subscription ?? $this->startFree($owner);
    }

    public function startFree(User $owner, bool $grantCredits = false): Subscription
    {
        $free = Plan::where('slug', 'free')->firstOrFail();

        $subscription = $owner->subscriptions()->create([
            'plan_id' => $free->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => null,
        ]);

        if ($grantCredits && $free->monthly_credits > 0) {
            $this->credits->add($owner, $free->monthly_credits, 'Welcome credits (Free plan)');
        }

        return $subscription->setRelation('plan', $free);
    }

    /** Switch to a plan. Paid plans run for one month and add their monthly credits. */
    public function activate(User $owner, Plan $plan): Subscription
    {
        return DB::transaction(function () use ($owner, $plan) {
            $owner->subscriptions()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_CANCELLED]);

            $subscription = $owner->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'ends_at' => $plan->isFree() ? null : now()->addMonth(),
            ]);

            if (! $plan->isFree() && $plan->monthly_credits > 0) {
                $this->credits->add($owner, $plan->monthly_credits, "{$plan->name} plan: monthly credits");
            }

            return $subscription->setRelation('plan', $plan);
        });
    }
}
