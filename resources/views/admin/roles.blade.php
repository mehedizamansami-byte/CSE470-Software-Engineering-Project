@extends('layouts.app')
@section('title', 'Roles & permissions')

@section('content')
<div class="page-head">
    <div>
        <h1>Roles and permissions</h1>
        <p class="sub">Every page checks the user's role and the permission for that feature before it opens.</p>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Feature</th>@foreach ($roles as $role) <th>{{ ucfirst($role) }}</th> @endforeach</tr>
            </thead>
            <tbody>
            @foreach ($permissions as $key => $label)
                <tr>
                    <td><strong>{{ $label }}</strong><div class="small muted"><code>permission:{{ $key }}</code></div></td>
                    @foreach ($roles as $role)
                        <td>
                            @if (in_array($key, $rolePermissions[$role] ?? [], true))
                                <span class="yes">✔ Yes</span>
                            @elseif ($role === 'collaborator' && in_array($key, $grantable, true))
                                <span class="badge badge-amber">If seller grants</span>
                            @else
                                <span class="no">✕</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-2 mt">
    <div class="card">
        <h2>How it works</h2>
        <ul class="bullets">
            <li><strong>Role check</strong>: routes use <code>role:admin</code> middleware (RoleMiddleware).</li>
            <li><strong>Feature check</strong>: routes use <code>permission:content</code> middleware (PermissionMiddleware).</li>
            <li><strong>In views</strong>: menus use <code>@@can('content')</code>, powered by a Gate in AppServiceProvider.</li>
            <li><strong>Collaborators</strong> only get what their seller ticks on the Team page.</li>
        </ul>
    </div>
    <div class="card">
        <h2>Role summary</h2>
        <ul class="list">
            <li><span><strong>Seller</strong>: owns a shop workspace, buys plans and AI credits.</span></li>
            <li><span><strong>Collaborator</strong>: staff invited by a seller, limited permissions.</span></li>
            <li><span><strong>Designer</strong>: freelancer with a public portfolio.</span></li>
            <li><span><strong>Admin</strong>: manages users and roles.</span></li>
        </ul>
    </div>
</div>
@endsection
