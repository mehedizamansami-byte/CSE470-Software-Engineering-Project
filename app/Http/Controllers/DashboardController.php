<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Post;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CONTROLLER layer: role-based dashboard.
 */
class DashboardController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions): View
    {
        $user = $request->user();
        $data = [
            'user' => $user,
            'notifications' => $user->notifications()->limit(5)->get(),
        ];

        if ($owner = $user->workspaceOwner()) {
            $data += [
                'owner' => $owner,
                'shop' => $owner->shop,
                'subscription' => $subscriptions->current($owner),
                'activeKit' => $owner->brandKits()->where('is_active', true)->with('styleGuide')->first(),
                'stats' => [
                    'kits' => $owner->brandKits()->count(),
                    'scheduled' => $owner->posts()->where('status', Post::STATUS_SCHEDULED)->where('scheduled_at', '>=', now())->count(),
                    'drafts' => $owner->posts()->where('status', Post::STATUS_DRAFT)->count(),
                    'team' => $owner->teamMembers()->where('status', TeamMember::STATUS_ACTIVE)->count(),
                ],
                'upcoming' => $owner->posts()->where('status', Post::STATUS_SCHEDULED)
                    ->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->limit(5)->get(),
                'activity' => AuditLog::where('seller_id', $owner->id)->latest('id')->limit(6)->get(),
            ];
        } elseif ($user->hasRole(User::ROLE_DESIGNER)) {
            $data['profile'] = $user->designerProfile()->withCount('items')->first();
        } elseif ($user->hasRole(User::ROLE_ADMIN)) {
            $data += [
                'roleCounts' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
                'revenue' => (int) Payment::where('status', Payment::STATUS_PAID)->sum('amount'),
                'paidCount' => Payment::where('status', Payment::STATUS_PAID)->count(),
                'latestUsers' => User::latest('id')->limit(5)->get(),
            ];
        }

        return view('dashboard.index', $data);
    }
}
