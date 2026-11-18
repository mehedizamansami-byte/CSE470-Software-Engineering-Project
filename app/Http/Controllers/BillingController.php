<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Notifications\AppNotification;
use App\Services\AuditLogger;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTROLLER layer: subscription plans and AI credits (Feature 5). Seller only.
 */
class BillingController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions): View
    {
        $owner = $request->user();

        return view('billing.index', [
            'subscription' => $subscriptions->current($owner),
            'plans' => Plan::orderBy('sort_order')->get(),
            'packs' => config('brandbondhu.credit_packs'),
            'costs' => config('brandbondhu.credit_costs'),
            'credits' => $owner->ai_credits,
            'threshold' => (int) config('brandbondhu.low_credit_threshold'),
            'history' => $owner->creditTransactions()->latest('id')->limit(12)->get(),
        ]);
    }

    public function subscribe(Request $request, Plan $plan, SubscriptionService $subscriptions, PaymentService $payments): RedirectResponse
    {
        $owner = $request->user();

        if ((int) $subscriptions->current($owner)->plan_id === $plan->id) {
            return back()->with('error', "You are already on the {$plan->name} plan.");
        }

        if ($plan->isFree()) {
            $subscriptions->activate($owner, $plan);
            AuditLogger::log($owner, 'billing.plan', 'Switched to the Free plan');
            $owner->notify(new AppNotification('Plan changed', 'You are now on the Free plan.', route('billing.index', [], false)));

            return back()->with('success', 'You are now on the Free plan. Your remaining credits stay in your wallet.');
        }

        return redirect()->route('checkout.show', $payments->createForPlan($owner, $plan));
    }

    public function buyCredits(Request $request, PaymentService $payments): RedirectResponse
    {
        $data = $request->validate([
            'pack' => ['required', Rule::in(array_keys(config('brandbondhu.credit_packs')))],
        ]);

        return redirect()->route('checkout.show', $payments->createForCredits($request->user(), $data['pack']));
    }

    public function transactions(Request $request): View
    {
        return view('billing.transactions', [
            'payments' => $request->user()->payments()->latest('id')->paginate(15),
        ]);
    }
}
