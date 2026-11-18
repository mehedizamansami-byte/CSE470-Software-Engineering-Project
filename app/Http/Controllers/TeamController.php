<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TeamMember;
use App\Notifications\AppNotification;
use App\Services\AuditLogger;
use App\Services\EmailService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTROLLER layer: team and collaborators with an audit log (Feature 4). Seller only.
 */
class TeamController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions): View
    {
        $owner = $request->user();

        return view('team.index', [
            'members' => $owner->teamMembers()->with('user')->orderByDesc('status')->latest('id')->get(),
            'permissions' => config('brandbondhu.workspace_permissions'),
            'plan' => $subscriptions->current($owner)->plan,
            'logs' => AuditLog::where('seller_id', $owner->id)->latest('id')->limit(8)->get(),
        ]);
    }

    public function invite(Request $request, SubscriptionService $subscriptions, EmailService $email): RedirectResponse
    {
        $owner = $request->user();

        $data = $request->validate([
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('team_members', 'email')->where('seller_id', $owner->id),
                Rule::unique('users', 'email'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(config('brandbondhu.workspace_permissions')))],
        ], [
            'email.unique' => 'This email is already invited or already has a BrandBondhu account.',
        ]);

        $limit = $subscriptions->current($owner)->plan->team_limit;

        if ($limit !== null && $owner->teamMembers()->count() >= $limit) {
            return back()->withInput()->with('error', $limit === 0
                ? 'The Free plan does not include team members. Upgrade to Basic or higher to invite staff.'
                : "Your plan allows {$limit} team member(s). Upgrade your plan to invite more.");
        }

        $member = $owner->teamMembers()->create([
            'email' => strtolower($data['email']),
            'permissions' => array_values($data['permissions'] ?? []),
            'status' => TeamMember::STATUS_PENDING,
            'token' => Str::random(48),
            'invited_at' => now(),
        ]);

        $email->send(
            $member->email,
            "{$owner->name} invited you to BrandBondhu",
            "Hello,\n\n{$owner->name} invited you to join their team on BrandBondhu.\nAccept the invitation here: {$member->inviteUrl()}\n",
        );

        AuditLogger::log($owner, 'team.invited', "Invited {$member->email} with access to: {$member->permissionLabels()}");

        return back()->with('success', "Invitation created for {$member->email}. Copy the invite link below and send it by WhatsApp or Messenger.");
    }

    public function update(Request $request, TeamMember $member): RedirectResponse
    {
        $owner = $this->ownMember($request, $member);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(config('brandbondhu.workspace_permissions')))],
        ]);

        $member->update(['permissions' => array_values($data['permissions'] ?? [])]);

        AuditLogger::log($owner, 'team.permissions', "Changed access for {$member->email} to: {$member->permissionLabels()}");

        $member->user?->notify(new AppNotification(
            'Your access was updated',
            "{$owner->name} changed your access to: {$member->permissionLabels()}.",
            route('dashboard', [], false),
        ));

        return back()->with('success', "Permissions updated for {$member->email}.");
    }

    public function destroy(Request $request, TeamMember $member): RedirectResponse
    {
        $owner = $this->ownMember($request, $member);
        $label = $member->name ?: $member->email;

        DB::transaction(function () use ($member) {
            if ($member->user) {
                // Collaborator accounts belong to one team only, so removing them deletes the account.
                $member->user->notifications()->delete();
                $member->user->delete();
            }

            $member->delete();
        });

        AuditLogger::log($owner, 'team.removed', "Removed {$label} from the team");

        return back()->with('success', "{$label} was removed from your team.");
    }

    public function auditLog(Request $request): View
    {
        $owner = $request->user();
        $area = $request->query('area');

        $logs = AuditLog::where('seller_id', $owner->id)
            ->when($area, fn ($q) => $q->where('action', 'like', $area.'.%'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('team.audit-log', [
            'logs' => $logs,
            'area' => $area,
            'areas' => ['team' => 'Team', 'shop' => 'Shop', 'billing' => 'Billing', 'brand_kit' => 'Brand kits', 'style_guide' => 'Style guide', 'post' => 'Posts'],
        ]);
    }

    private function ownMember(Request $request, TeamMember $member)
    {
        abort_unless((int) $member->seller_id === $request->user()->id, 404);

        return $request->user();
    }
}
