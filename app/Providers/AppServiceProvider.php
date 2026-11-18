<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Feature 2: every permission key works with @can('content') in Blade and Gate::allows().
        Gate::before(function (User $user, string $ability) {
            if (array_key_exists($ability, config('brandbondhu.all_permissions'))) {
                return $user->hasPermission($ability);
            }

            return null;
        });

        // Feature 5: the credits meter in the sidebar. Prepared here so views never query the database.
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $owner = $user?->workspaceOwner();

            if (! $owner) {
                $view->with('creditMeter', null);

                return;
            }

            $plan = app(SubscriptionService::class)->current($owner)->plan;
            $max = max($plan->monthly_credits, $owner->ai_credits, 1);

            $view->with('creditMeter', [
                'credits' => $owner->ai_credits,
                'plan' => $plan->name,
                'percent' => (int) min(100, round($owner->ai_credits / $max * 100)),
                'low' => $owner->ai_credits < (int) config('brandbondhu.low_credit_threshold'),
            ]);
        });
    }
}
