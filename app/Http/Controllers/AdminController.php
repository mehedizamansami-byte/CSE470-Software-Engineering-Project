<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTROLLER layer: admin views for users, roles and permissions (Feature 2).
 */
class AdminController extends Controller
{
    public function users(Request $request): View
    {
        $role = in_array($request->query('role'), User::ROLES, true) ? $request->query('role') : null;

        return view('admin.users', [
            'users' => User::when($role, fn ($q) => $q->where('role', $role))->orderBy('id')->paginate(20)->withQueryString(),
            'role' => $role,
        ]);
    }

    /** Change a user's role. Collaborators are only created through team invitations. */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_SELLER, User::ROLE_DESIGNER, User::ROLE_ADMIN])],
        ]);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        if ($user->role === User::ROLE_COLLABORATOR) {
            return back()->with('error', 'Collaborator roles are managed by their seller on the Team page.');
        }

        $user->update(['role' => $data['role']]);
        $user->notify(new AppNotification('Your role changed', 'An administrator changed your role to '.ucfirst($data['role']).'.', route('dashboard', [], false)));

        return back()->with('success', "{$user->name} is now a ".ucfirst($data['role']).'.');
    }

    /** Read-only matrix of which role can use which feature. */
    public function roles(): View
    {
        return view('admin.roles', [
            'permissions' => config('brandbondhu.all_permissions'),
            'rolePermissions' => config('brandbondhu.role_permissions'),
            'grantable' => array_keys(config('brandbondhu.workspace_permissions')),
            'roles' => User::ROLES,
        ]);
    }
}
